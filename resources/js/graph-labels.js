import { Vector3 } from 'three';

export function visibleGraphLabels(nodes, globe, camera, width, height, measure, spherical = true) {
    if (camera.zoom < 1.6) return [];
    globe.updateWorldMatrix(true, false);
    camera.updateMatrixWorld();
    const center = globe.getWorldPosition(new Vector3()).applyMatrix4(camera.matrixWorldInverse);
    const candidates = nodes.map(node => {
        const world = globe.localToWorld(node.position.clone());
        const depth = world.clone().applyMatrix4(camera.matrixWorldInverse).z - center.z;
        const screen = world.project(camera);
        return { text: node.text, depth, x: (screen.x + 1) * width / 2 + 7, y: (1 - screen.y) * height / 2,
            z: screen.z };
    }).filter(p => (!spherical || p.depth > 0) && p.z >= -1 && p.z <= 1 && p.x > 0 && p.x < width && p.y > 12 && p.y < height - 12)
        .sort((a, b) => b.depth - a.depth);
    const visible = [];
    for (const label of candidates) {
        const textWidth = measure(label.text);
        label.right = label.x + textWidth + 8;
        if (label.right > width) continue;
        if (visible.some(other => label.x < other.right && label.right > other.x && Math.abs(label.y - other.y) < 20)) continue;
        visible.push(label);
        if (visible.length === 70) break;
    }
    return visible;
}

export function createGraphLabels(container) {
    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    Object.assign(canvas.style, { position: 'absolute', inset: '0', pointerEvents: 'none', width: '100%', height: '100%' });
    container.appendChild(canvas);
    const context = canvas.getContext('2d');
    return {
        update(nodes, globe, camera, spherical = true) {
            const width = container.clientWidth;
            const height = container.clientHeight;
            const ratio = Math.min(window.devicePixelRatio || 1, 2);
            if (canvas.width !== Math.round(width * ratio) || canvas.height !== Math.round(height * ratio)) {
                canvas.width = Math.round(width * ratio);
                canvas.height = Math.round(height * ratio);
            }
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.clearRect(0, 0, width, height);
            context.font = '12px sans-serif';
            context.textBaseline = 'middle';
            for (const label of visibleGraphLabels(nodes, globe, camera, width, height, text => context.measureText(text).width, spherical)) {
                context.fillStyle = 'rgba(8, 14, 25, 0.82)';
                context.fillRect(label.x - 3, label.y - 9, label.right - label.x, 18);
                context.fillStyle = '#e2f5ff';
                context.fillText(label.text, label.x, label.y);
            }
        },
        dispose: () => canvas.remove(),
    };
}
