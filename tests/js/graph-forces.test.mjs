import test from 'node:test';
import assert from 'node:assert/strict';
import { createPlanarSimulation } from '../../resources/js/graph-forces.js';

function random() {
    let seed = 42;
    return () => { seed = (1664525 * seed + 1013904223) >>> 0; return seed / 2 ** 32; };
}
test('random initialization covers the panel independently of communities', () => {
    const sim = createPlanarSimulation([{ id: 0, nodes: Array.from({ length: 200 }, (_, id) => ({ id })) }], [], 2);
    sim.randomize(random());
    const nodes = [...sim.bodies.values()];
    assert.ok(Math.min(...nodes.map(n => n.x)) < -1.6);
    assert.ok(Math.max(...nodes.map(n => n.x)) > 1.6);
    assert.ok(Math.min(...nodes.map(n => n.y)) < -0.8);
    assert.ok(Math.max(...nodes.map(n => n.y)) > 0.8);
});
test('linked nodes attract from random positions; disconnected nodes repel and stay finite', () => {
    const make = (links, separate = false) => {
        const communities = separate ? [{ id: 0, nodes: [{ id: 'a' }] }, { id: 1, nodes: [{ id: 'b' }] }] : [{ id: 0, nodes: [{ id: 'a' }, { id: 'b' }] }];
        const sim = createPlanarSimulation(communities, links, 1);
        sim.randomize(random());
        return sim;
    };
    const linked = make([['a', 'b']]);
    const loose = make([], true);
    const distance = sim => { const [a, b] = [...sim.bodies.values()]; return Math.hypot(a.x - b.x, a.y - b.y); };
    const initial = distance(linked);
    for (let i = 0; i < 300; i++) { linked.step(0.04); loose.step(0.04); }
    assert.ok(distance(linked) < initial);
    assert.ok(distance(loose) > distance(linked));
    linked.resize(0.5);
    for (let i = 0; i < 100; i++) linked.step(0.04);
    for (const node of linked.bodies.values()) {
        assert.ok(Number.isFinite(node.x + node.y));
        assert.ok(Math.abs(node.x) <= 0.485 && Math.abs(node.y) <= 0.97);
    }
});

test('communities attract all their members and repel as separate clusters', () => {
    const communities = Array.from({ length: 3 }, (_, id) => ({ id, nodes: Array.from({ length: 12 }, (_, i) => ({ id: `${id}:${i}` })) }));
    const links = communities.flatMap(c => c.nodes.slice(1).map(n => [c.nodes[0].id, n.id]));
    const sim = createPlanarSimulation(communities, links, 1);
    sim.randomize(random());
    const radius = () => communities.reduce((sum, c) => {
        const center = sim.bodies.get(c.nodes[0].id);
        return sum + c.nodes.slice(1).reduce((total, n) => { const node = sim.bodies.get(n.id); return total + Math.hypot(node.x - center.x, node.y - center.y); }, 0);
    }, 0) / 33;
    const before = radius();
    for (let i = 0; i < 500; i++) sim.step(0.04);
    assert.ok(radius() < before * 0.4);
    for (let i = 0; i < communities.length; i++) {
        const a = sim.bodies.get(`${i}:0`);
        for (let j = i + 1; j < communities.length; j++) {
            const b = sim.bodies.get(`${j}:0`);
            assert.ok(Math.hypot(a.x - b.x, a.y - b.y) > 0.5);
        }
    }
});
