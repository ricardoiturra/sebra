import { test } from 'node:test';
import assert from 'node:assert/strict';
import { Group, OrthographicCamera, Vector3 } from 'three';
import { drawGraph } from '../../resources/js/graph.js';
import { focusSelection, restoreCamera, verifiedSelection } from '../../resources/js/graph-selection.js';

const graph = {
    nodes: [
        { id: 'a', label: 'User', community: 0, name: 'Accounts' },
        { id: 'b', label: 'Team', community: 0, name: 'Accounts' },
        { id: 'c', label: 'Project', community: 1, name: 'Projects' },
    ], links: [['a', 'b'], ['b', 'c']], hyperedges: [],
    relations: [{ id: 'edge:ab', source: 'a', target: 'b', relation: 'belongs to', directed: false }],
};
const payload = {
    version: 1, status: 'matched', display_node_ids: ['a', 'b'], focus_ids: ['a'],
    relations: graph.relations,
};

test('selection rejects unknown IDs and mismatched edges without interpreting answer text', () => {
    const result = verifiedSelection({ ...payload, display_node_ids: ['a', 'b', 'FOREIGN'],
        answer: 'Please select c', relations: [...graph.relations, { id: 'edge:ab', source: 'a', target: 'c', relation: 'invented' }] }, graph);
    assert.deepEqual(result.ids, ['a', 'b']);
    assert.deepEqual(result.edges, graph.relations);
    assert.equal(verifiedSelection({ ...payload, display_node_ids: ['FOREIGN'] }, graph), null);
    assert.equal(verifiedSelection({ ...payload, status: 'none' }, graph), null);
});

test('restored sphere highlights whole communities and clearing restores normal opacity', () => {
    const scene = new Group();
    const view = drawGraph(graph, scene, 1);
    view.setSpherical(true);
    const points = scene.children[0].children.filter((item) => item.isPoints);
    const originalSize = points[0].geometry.getAttribute('nodeSize').getX(0);
    view.highlight(new Set([0]), false);
    assert.equal(points[0].material.opacity, 1);
    assert.ok(points[1].material.opacity < 0.3);
    assert.ok(points[0].geometry.getAttribute('nodeSize').getX(0) > originalSize);
    assert.equal(points[0].geometry.getAttribute('nodeAlpha'), undefined);
    view.highlight(new Set(), false);
    assert.ok(points.every((item) => item.material.opacity === 1));
    assert.equal(points[0].geometry.getAttribute('nodeSize').getX(0), originalSize);
    view.dispose();
    assert.equal(scene.children.length, 0);
});

test('restored sphere keeps buffers while its simulation continues', () => {
    const scene = new Group();
    const view = drawGraph(graph, scene, 1);
    const geometry = scene.children[0].children[0].geometry;
    view.highlight(new Set([0]), false);
    view.step(0.1);
    assert.equal(scene.children[0].children[0].geometry, geometry);
    assert.equal(typeof view.selectNodes, 'undefined');
    view.dispose();
});

test('focus and restore are immediate, bounded and allow subsequent manual rotation', () => {
    const camera = new OrthographicCamera(-1.16, 1.16, 1.16, -1.16, 0.1, 10);
    camera.position.z = 2;
    const globe = new Group();
    assert.equal(focusSelection(camera, globe, [new Vector3(-1, 0, 0)]), true);
    assert.equal(camera.zoom, 5);
    const rotation = globe.quaternion.clone();
    globe.rotateY(0.2);
    assert.notDeepEqual(globe.quaternion.toArray(), rotation.toArray());
    const unchanged = camera.position.clone();
    assert.equal(focusSelection(camera, globe, []), false);
    assert.deepEqual(camera.position.toArray(), unchanged.toArray());
    restoreCamera(camera, globe);
    assert.equal(camera.zoom, 1);
    assert.deepEqual(camera.position.toArray(), [0, 0, 2]);
    assert.deepEqual(globe.quaternion.toArray(), [0, 0, 0, 1]);
});

test('all nodes use connection degree for size without a community bonus', () => {
    const scene = new Group();
    const view = drawGraph(graph, scene, 1);
    const points = scene.children[0].children.filter(item => item.isPoints);
    assert.equal(points[0].geometry.getAttribute('nodeGlow'), undefined);
    assert.equal(points[1].geometry.getAttribute('nodeGlow'), undefined);
    const sizes = points[0].geometry.getAttribute('nodeSize');
    const representativeSize = sizes.getX(1);
    assert.equal(representativeSize, 11);
    const sameDegreeSize = points[1].geometry.getAttribute('nodeSize').getX(0);
    assert.equal(sizes.getX(0), sameDegreeSize);
    assert.ok(representativeSize > sameDegreeSize);
    view.highlight(new Set([0]), false);
    view.setSpherical(true);
    view.step(0.04);
    assert.ok(sizes.getX(1) >= representativeSize);
    view.highlight(new Set(), false);
    assert.equal(sizes.getX(1), representativeSize);
    assert.equal(points[0].geometry.getAttribute('nodeGlow'), undefined);
    view.dispose();
});

test('2D mode keeps the same node IDs and planar geometry, then restores spherical geometry', () => {
    const scene = new Group();
    const view = drawGraph(graph, scene, 1);
    const ids = view.labelNodes().map(node => node.id);
    assert.ok(view.labelNodes().every(node => node.position.z === 0));
    view.setSpherical(true);
    assert.ok(view.labelNodes().some(node => node.position.z !== 0));
    view.setSpherical(false);
    view.step(0.04);
    assert.deepEqual(view.labelNodes().map(node => node.id), ids);
    assert.ok(view.labelNodes().every(node => node.position.z === 0));
    view.dispose();
});
