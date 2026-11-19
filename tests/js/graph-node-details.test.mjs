import test from 'node:test';
import assert from 'node:assert/strict';
import { Group, OrthographicCamera, Vector3 } from 'three';
import { pickGraphNode } from '../../resources/js/graph-node-details.js';

test('double-click picking uses real visible positions, respects rotation and misses empty space', () => {
    const camera = new OrthographicCamera(-1, 1, 1, -1, 0.1, 10);
    camera.position.z = 2;
    const globe = new Group();
    const nodes = [{ id: 'front', position: new Vector3(0, 0, 1) }, { id: 'back', position: new Vector3(0, 0, -1) }];
    const pick = (x, y) => pickGraphNode(nodes, globe, camera, 600, 600, x, y);
    assert.equal(pick(300, 300).id, 'front');
    assert.equal(pick(10, 10), null);
    globe.rotateY(Math.PI);
    assert.equal(pick(300, 300).id, 'back');
    camera.zoom = 3;
    camera.updateProjectionMatrix();
    assert.equal(pick(300, 300).id, 'back');
});
