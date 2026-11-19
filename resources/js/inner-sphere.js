import { Vector3 } from 'three';

export function createInnerNode(position, phase) {
    return {
        position,
        velocity: new Vector3(),
        force: new Vector3(),
        driftAxis: new Vector3(Math.cos(phase), Math.sin(phase * 0.71), Math.sin(phase)).normalize(),
    };
}

export function driftOnInnerSphere(nodes, seconds) {
    const offset = new Vector3();
    const strength = 12 / Math.max(1, nodes.length);

    for (const node of nodes) {
        node.force.crossVectors(node.driftAxis, node.position).multiplyScalar(0.9);
    }

    for (let i = 0; i < nodes.length; i++) {
        const a = nodes[i];
        for (let j = i + 1; j < nodes.length; j++) {
            const b = nodes[j];
            offset.subVectors(a.position, b.position);
            const distance = Math.max(0.025, offset.lengthSq());
            a.force.addScaledVector(offset, strength / (distance * Math.sqrt(distance)));
            b.force.addScaledVector(offset, -strength / (distance * Math.sqrt(distance)));
        }
    }

    const damping = Math.exp(-1.5 * seconds);
    for (const { position, velocity, force } of nodes) {
        force.addScaledVector(position, -force.dot(position));
        velocity.addScaledVector(force, seconds).multiplyScalar(damping).clampLength(0, 1.2);
        position.addScaledVector(velocity, seconds).normalize();
        velocity.addScaledVector(position, -velocity.dot(position));
    }
}
