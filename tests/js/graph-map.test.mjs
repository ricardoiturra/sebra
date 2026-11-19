import test from 'node:test';
import assert from 'node:assert/strict';
import { Vector3 } from 'three';
import { unfoldSphere } from '../../resources/js/graph-map.js';

test('sphere unfolds into an oval map with finite poles and longitude preserved', () => {
    assert.deepEqual(unfoldSphere(new Vector3(0, 0, 1)).toArray(), [0, 0, 0]);
    assert.ok(Math.abs(unfoldSphere(new Vector3(1, 0, 0)).x - 0.5) < 1e-8);
    for (let i = 0; i <= 100; i++) {
        const y = -1 + i / 50;
        const point = unfoldSphere(new Vector3(Math.sqrt(1 - y * y), y, 0), 2);
        assert.ok(Number.isFinite(point.x + point.y));
        assert.ok((point.x / 2) ** 2 + point.y ** 2 <= 1 + 1e-8);
        assert.equal(point.z, 0);
    }
    assert.ok(Math.abs(unfoldSphere(new Vector3(0, 1, 0)).y - 1) < 1e-8);
});
