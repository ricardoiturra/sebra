import { Vector3 } from 'three';

// Mollweide unfolds the sphere into an equal-area oval without stacking poles.
export function unfoldSphere(position, aspect = 1) {
    const radius = position.length();
    const y = radius > 0 ? Math.max(-1, Math.min(1, position.y / radius)) : 0;
    let theta = Math.asin(y);
    if (Math.abs(y) < 0.999999) {
        for (let i = 0; i < 12; i++) {
            const delta = (2 * theta + Math.sin(2 * theta) - Math.PI * y) / (2 + 2 * Math.cos(2 * theta));
            theta -= delta;
            if (Math.abs(delta) < 1e-8) break;
        }
    } else theta = Math.sign(y) * Math.PI / 2;
    return new Vector3(aspect * Math.atan2(position.x, position.z) / Math.PI * Math.cos(theta), Math.sin(theta), 0);
}
