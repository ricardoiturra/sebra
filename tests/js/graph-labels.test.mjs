import test from 'node:test';
import assert from 'node:assert/strict';
import { Group, OrthographicCamera, Vector3 } from 'three';
import { visibleGraphLabels } from '../../resources/js/graph-labels.js';

test('zoom reveals front-facing names, hides rear nodes and follows rotation', () => {
    const globe = new Group();
    const camera = new OrthographicCamera(-1.2, 1.2, 1.2, -1.2, 0.1, 10);
    camera.position.z = 2;
    const nodes = [{ text: 'User', position: new Vector3(0, 0, 1) }, { text: 'Team', position: new Vector3(0, 0, -1) }];
    const labels = () => visibleGraphLabels(nodes, globe, camera, 600, 600, text => text.length * 7);
    assert.deepEqual(labels(), []);
    camera.zoom = 2;
    camera.updateProjectionMatrix();
    assert.deepEqual(labels().map(p => p.text), ['User']);
    globe.rotateY(Math.PI);
    assert.deepEqual(labels().map(p => p.text), ['Team']);
});

test('overlapping names are filtered and labels stay within the viewport', () => {
    const camera = new OrthographicCamera(-1, 1, 1, -1, 0.1, 10);
    camera.position.z = 2;
    camera.zoom = 2;
    camera.updateProjectionMatrix();
    const nodes = Array.from({ length: 100 }, () => ({ text: 'User', position: new Vector3(0, 0, 1) }));
    const labels = visibleGraphLabels(nodes, new Group(), camera, 600, 600, () => 30);
    assert.equal(labels.length, 1);
    assert.ok(labels[0].right < 600);
});

test('flat mode shows names on the z=0 plane', () => {
    const camera = new OrthographicCamera(-1, 1, 1, -1, 0.1, 10);
    camera.position.z = 2; camera.zoom = 2; camera.updateProjectionMatrix();
    const labels = visibleGraphLabels([{ text: 'Flat node', position: new Vector3(0, 0, 0) }], new Group(), camera, 600, 600, () => 70, false);
    assert.equal(labels[0].text, 'Flat node');
});
