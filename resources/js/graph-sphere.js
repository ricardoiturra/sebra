import { Vector3 } from 'three';

const goldenAngle = Math.PI * (3 - Math.sqrt(5));

// Equal-area samples avoid the empty caps and crowded latitude boundaries of
// projecting the bounded planar simulation directly onto a sphere.
export function createSphericalProjection(bodies, links = []) {
    const nodes = [...bodies];
    const samples = nodes.map((_, index) => {
        const y = 1 - 2 * (index + 0.5) / nodes.length;
        const longitude = index * goldenAngle;
        const radius = Math.sqrt(1 - y * y);
        return new Vector3(radius * Math.sin(longitude), y, radius * Math.cos(longitude));
    });
    nodes.sort((a, b) => b.anchorY - a.anchorY || a.anchorX - b.anchorX);
    const anchors = new Map();
    const bandSize = Math.max(1, Math.round(Math.sqrt(nodes.length * 2)));
    for (let start = 0; start < nodes.length; start += bandSize) {
        const band = nodes.slice(start, start + bandSize).sort((a, b) => a.anchorX - b.anchorX);
        const positions = samples.slice(start, start + bandSize).sort((a, b) => Math.atan2(a.x, a.z) - Math.atan2(b.x, b.z));
        band.forEach((body, index) => {
            const position = positions[index];
            const east = new Vector3(position.z, 0, -position.x).normalize();
            anchors.set(body, { position, east, home: position.clone(), velocity: new Vector3(), force: new Vector3() });
        });
    }

    const degrees = new Map(nodes.map(node => [node, 0]));
    for (const [a, b] of links) {
        if (degrees.has(a)) degrees.set(a, degrees.get(a) + 1);
        if (degrees.has(b)) degrees.set(b, degrees.get(b) + 1);
    }
    const centers = new Map();
    for (const node of nodes) {
        const previous = centers.get(node.community);
        if (!previous || degrees.get(node) > degrees.get(previous)) centers.set(node.community, node);
    }
    const representatives = [...centers.values()];
    const offset = new Vector3();
    const spacing = Math.min(0.18, Math.sqrt(4 * Math.PI / Math.max(1, nodes.length)) * 0.6);
    const project = (body, aspect) => {
        const anchor = anchors.get(body);
        if (!anchor) return new Vector3(body.x / aspect, body.y, 1).normalize();
        return anchor.position.clone();
    };
    project.representatives = new Set(representatives);
    project.step = (seconds) => {
        const dt = Math.max(0, Math.min(seconds, 0.05));
        for (const anchor of anchors.values()) {
            anchor.force.subVectors(anchor.home, anchor.position).multiplyScalar(0.12);
        }
        for (const [a, b] of links) {
            const source = anchors.get(a);
            const target = anchors.get(b);
            if (!source || !target) continue;
            offset.subVectors(target.position, source.position);
            const distance = offset.length();
            if (distance <= spacing) continue;
            const strength = (a.community === b.community ? 0.9 : 0.06) * (1 - spacing / distance);
            source.force.addScaledVector(offset, strength);
            target.force.addScaledVector(offset, -strength);
        }
        const repel = (a, b, strength) => {
            offset.subVectors(a.position, b.position);
            const distance = offset.length();
            if (distance < 1e-8) offset.copy(a.east);
            else offset.divideScalar(distance);
            a.force.addScaledVector(offset, strength);
            b.force.addScaledVector(offset, -strength);
        };
        for (let i = 0; i < representatives.length; i++) {
            const a = anchors.get(representatives[i]);
            for (let j = i + 1; j < representatives.length; j++) {
                const b = anchors.get(representatives[j]);
                repel(a, b, Math.min(1.5, 0.12 / Math.max(0.01, a.position.distanceToSquared(b.position))));
            }
        }
        // Local spacing keeps linked nodes from collapsing onto their center.
        for (let i = 0; i < nodes.length; i++) {
            const a = anchors.get(nodes[i]);
            for (let j = i + 1; j < nodes.length; j++) {
                const b = anchors.get(nodes[j]);
                const distance = a.position.distanceTo(b.position);
                if (distance < spacing) repel(a, b, (spacing - distance) * 12);
            }
        }
        for (const { position, force, velocity } of anchors.values()) {
            force.addScaledVector(position, -force.dot(position));
            velocity.addScaledVector(force, dt).multiplyScalar(Math.exp(-2.8 * dt)).clampLength(0, 0.6);
            position.addScaledVector(velocity, dt).normalize();
            velocity.addScaledVector(position, -velocity.dot(position));
        }
    };
    return project;
}
