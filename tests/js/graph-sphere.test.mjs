import test from 'node:test';
import assert from 'node:assert/strict';
import { createSphericalProjection } from '../../resources/js/graph-sphere.js';
import { createPlanarSimulation } from '../../resources/js/graph-forces.js';

test('equal-area sphere covers both poles without a clamped latitude ring', () => {
    const bodies = Array.from({ length: 1000 }, (_, i) => ({ anchorX: i % 40 / 20 - 1, anchorY: Math.floor(i / 40) / 12 - 1 }));
    bodies.forEach(body => Object.assign(body, { x: body.anchorX, y: body.anchorY }));
    const project = createSphericalProjection(bodies);
    const points = bodies.map(body => project(body, 1));
    assert.ok(Math.max(...points.map(p => p.y)) > 0.998);
    assert.ok(Math.min(...points.map(p => p.y)) < -0.998);
    for (let band = 0; band < 10; band++) {
        assert.equal(points.filter(p => p.y >= -1 + band / 5 && p.y < -1 + (band + 1) / 5).length, 100);
    }
    points.forEach(p => assert.ok(Math.abs(p.length() - 1) < 1e-12));
});

test('projection remains finite during motion, repulsion and aspect changes', () => {
    const simulation = createPlanarSimulation(Array.from({ length: 4 }, (_, id) => ({ id, nodes: Array.from({ length: 25 }, (_, i) => ({ id: id * 25 + i })) })), [], 1);
    const project = createSphericalProjection(simulation.bodies.values());
    simulation.repel(new Set([0]));
    for (const aspect of [1, 0.5, 2]) {
        simulation.resize(aspect);
        for (let frame = 0; frame < 60; frame++) {
            simulation.step(0.04);
            project.step(0.04);
        }
        for (const body of simulation.bodies.values()) {
            const point = project(body, aspect);
            assert.ok(Number.isFinite(point.x + point.y + point.z));
            assert.ok(Math.abs(point.length() - 1) < 1e-12);
        }
    }
    assert.ok(Number.isFinite(project({ x: 0, y: 0 }, 1).length()));
});

test('real links attract on the sphere while community centers repel', () => {
    const make = (community) => Array.from({ length: 2 }, (_, i) => ({ community: community(i), anchorX: i, anchorY: i, x: i, y: i }));
    const linked = make(() => 0);
    const attraction = createSphericalProjection(linked, [[linked[0], linked[1]]]);
    const before = attraction(linked[0], 1).distanceTo(attraction(linked[1], 1));
    for (let frame = 0; frame < 100; frame++) attraction.step(0.04);
    assert.ok(attraction(linked[0], 1).distanceTo(attraction(linked[1], 1)) < before - 0.1);
    const separate = make(i => i);
    const repulsion = createSphericalProjection(separate);
    const initial = repulsion(separate[0], 1).distanceTo(repulsion(separate[1], 1));
    for (let frame = 0; frame < 100; frame++) repulsion.step(0.04);
    assert.ok(repulsion(separate[0], 1).distanceTo(repulsion(separate[1], 1)) > initial);
    separate.forEach(body => assert.ok(Math.abs(repulsion(body, 1).length() - 1) < 1e-12));
});
