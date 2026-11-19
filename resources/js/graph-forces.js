const goldenAngle = Math.PI * (3 - Math.sqrt(5));

export function createPlanarSimulation(communities, rawLinks, aspect) {
    const bodies = new Map();
    const columns = Math.max(1, Math.ceil(Math.sqrt(communities.length * aspect)));
    const rows = Math.max(1, Math.ceil(communities.length / columns));
    const cellWidth = 1.85 * aspect / columns;
    const cellHeight = 1.85 / rows;
    const patch = Math.min(cellWidth, cellHeight) * 0.43;

    communities.forEach((community, index) => {
        const row = Math.floor(index / columns);
        const countInRow = Math.min(columns, communities.length - row * columns);
        const x = (index % columns - (countInRow - 1) / 2) * cellWidth;
        const y = ((rows - 1) / 2 - row) * cellHeight;

        for (const [i, node] of community.nodes.entries()) {
            const angle = i * goldenAngle;
            const radius = patch * Math.sqrt(i / community.nodes.length);
            const anchorX = x + Math.cos(angle) * radius;
            const anchorY = y + Math.sin(angle) * radius;
            bodies.set(node.id, {
                community: community.id, anchorX, anchorY, x: anchorX, y: anchorY,
                vx: 0, vy: 0, fx: 0, fy: 0, phase: (bodies.size + 1) * goldenAngle,
            });
        }
    });

    const nodes = [...bodies.values()];
    const links = rawLinks.map(([a, b]) => [bodies.get(a), bodies.get(b)])
        .filter(([a, b]) => a && b && a !== b);
    let width = aspect;
    const separation = Math.min(0.075, Math.sqrt(4 * aspect / Math.max(1, nodes.length)) * 0.76);
    const separationSquared = separation ** 2;
    const degree = new Map(nodes.map(node => [node, 0]));
    for (const [a, b] of links) {
        degree.set(a, degree.get(a) + 1);
        degree.set(b, degree.get(b) + 1);
    }
    const centers = new Map();
    for (const node of nodes) {
        if (!centers.has(node.community) || degree.get(node) > degree.get(centers.get(node.community))) centers.set(node.community, node);
    }
    const clusters = [...centers].map(([id, center]) => ({
        center, members: nodes.filter(node => node.community === id), fx: 0, fy: 0,
    }));

    function randomize(random = Math.random) {
        for (const node of nodes) {
            node.x = (random() * 2 - 1) * width * 0.94;
            node.y = (random() * 2 - 1) * 0.94;
            node.vx = node.vy = 0;
        }
    }

    function resize(nextAspect) {
        const ratio = nextAspect / width;
        for (const node of nodes) {
            node.x *= ratio;
            node.anchorX *= ratio;
            node.vx *= ratio;
        }
        width = nextAspect;
    }

    function repel(selected) {
        const sources = nodes.filter((node) => selected.has(node.community));
        if (sources.length === 0) return;

        for (const node of nodes) {
            if (selected.has(node.community)) continue;

            let distance = Infinity;
            let dx = 0;
            let dy = 0;
            for (const source of sources) {
                const x = node.x - source.x;
                const y = node.y - source.y;
                const squared = x * x + y * y;
                if (squared < distance) {
                    distance = squared;
                    dx = x;
                    dy = y;
                }
            }

            const angle = distance > 0 ? Math.atan2(dy, dx) : node.phase;
            const strength = 0.78 / (1 + Math.sqrt(distance));
            node.vx += Math.cos(angle) * strength;
            node.vy += Math.sin(angle) * strength;
        }
    }

    function step(seconds) {
        seconds = Math.max(0, Math.min(seconds, 0.05));
        for (const node of nodes) {
            node.fx = -node.x * 0.08;
            node.fy = -node.y * 0.08;
        }

        for (const cluster of clusters) { cluster.fx = 0; cluster.fy = 0; }
        for (let i = 0; i < clusters.length; i++) {
            const a = clusters[i];
            for (let j = i + 1; j < clusters.length; j++) {
                const b = clusters[j];
                const dx = b.center.x - a.center.x;
                const dy = b.center.y - a.center.y;
                const distance = Math.hypot(dx, dy);
                const strength = Math.min(2, 0.35 / Math.max(0.04, distance * distance));
                const angle = a.center.phase + b.center.phase;
                const x = distance > 0 ? strength * dx / distance : strength * Math.cos(angle);
                const y = distance > 0 ? strength * dy / distance : strength * Math.sin(angle);
                a.fx -= x; a.fy -= y;
                b.fx += x; b.fy += y;
            }
        }
        for (const { center, members, fx, fy } of clusters) {
            for (const node of members) {
                node.fx += fx;
                node.fy += fy;
                if (node === center) continue;
                const dx = center.x - node.x;
                const dy = center.y - node.y;
                const distance = Math.hypot(dx, dy);
                const strength = 3 * Math.max(0, 1 - separation / Math.max(distance, 1e-8));
                node.fx += dx * strength;
                node.fy += dy * strength;
                center.fx -= dx * strength / members.length;
                center.fy -= dy * strength / members.length;
            }
        }

        for (const [a, b] of links) {
            const dx = b.x - a.x;
            const dy = b.y - a.y;
            const length = Math.hypot(dx, dy);
            if (length <= separation) continue;
            const strength = (a.community === b.community ? 0.8 : 0.06) * (1 - separation / length) / Math.sqrt(Math.max(degree.get(a), degree.get(b), 1));
            a.fx += dx * strength;
            a.fy += dy * strength;
            b.fx -= dx * strength;
            b.fy -= dy * strength;
        }

        for (let i = 0; i < nodes.length; i++) {
            const a = nodes[i];
            for (let j = i + 1; j < nodes.length; j++) {
                const b = nodes[j];
                const dx = b.x - a.x;
                const dy = b.y - a.y;
                const squared = dx * dx + dy * dy;
                if (squared >= separationSquared) continue;
                const distance = Math.sqrt(squared);
                const strength = (separation - distance) * 20;
                const angle = a.phase + b.phase;
                const x = distance > 0 ? strength * dx / distance : strength * Math.cos(angle);
                const y = distance > 0 ? strength * dy / distance : strength * Math.sin(angle);
                a.fx -= x;
                a.fy -= y;
                b.fx += x;
                b.fy += y;
            }
        }

        const damping = Math.exp(-2.8 * seconds);
        for (const node of nodes) {
            node.vx = (node.vx + node.fx * seconds) * damping;
            node.vy = (node.vy + node.fy * seconds) * damping;
            node.x += node.vx * seconds;
            node.y += node.vy * seconds;
            const maxX = width * 0.97;
            if (Math.abs(node.x) > maxX) {
                node.x = Math.sign(node.x) * maxX;
                node.vx *= -0.5;
            }
            if (Math.abs(node.y) > 0.97) {
                node.y = Math.sign(node.y) * 0.97;
                node.vy *= -0.5;
            }
        }
    }

    return { bodies, links, randomize, repel, resize, step };
}
