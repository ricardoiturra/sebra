import { Vector3 } from 'three';

export function connectGraphControls(canvas, camera, globe, isSpherical) {
    const horizontal = new Vector3(0, 1, 0);
    const vertical = new Vector3(1, 0, 0);
    let hover = false;
    let pointer = null;
    let previousX;
    let previousY;

    function zoom(amount) {
        camera.zoom = Math.max(0.55, Math.min(5, camera.zoom * Math.exp(amount)));
        camera.updateProjectionMatrix();
    }

    function move(dx, dy) {
        if (isSpherical()) {
            globe.rotateOnWorldAxis(horizontal, dx * 0.008);
            globe.rotateOnWorldAxis(vertical, dy * 0.008);
            return;
        }

        const scale = (camera.top - camera.bottom) / (camera.zoom * canvas.clientHeight);
        camera.position.x -= dx * scale;
        camera.position.y += dy * scale;
    }

    canvas.tabIndex = 0;
    canvas.setAttribute('aria-label', 'Grafo interactivo');
    canvas.style.touchAction = 'none';
    canvas.style.cursor = 'grab';

    canvas.addEventListener('pointerenter', () => { hover = true; });
    canvas.addEventListener('pointerleave', () => { hover = false; });
    canvas.addEventListener('pointerdown', (event) => {
        if (pointer !== null || event.button !== 0) return;
        pointer = event.pointerId;
        previousX = event.clientX;
        previousY = event.clientY;
        canvas.setPointerCapture(pointer);
        canvas.style.cursor = 'grabbing';
    });
    canvas.addEventListener('pointermove', (event) => {
        if (pointer !== event.pointerId) return;
        move(event.clientX - previousX, event.clientY - previousY);
        previousX = event.clientX;
        previousY = event.clientY;
    });
    canvas.addEventListener('lostpointercapture', () => {
        pointer = null;
        canvas.style.cursor = 'grab';
    });
    canvas.addEventListener('wheel', (event) => {
        event.preventDefault();
        const units = event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? canvas.clientHeight : 1;
        zoom(Math.min(0.55, Math.max(-0.55, -event.deltaY * units * 0.0015)));
    }, { passive: false });
    canvas.addEventListener('keydown', (event) => {
        const arrows = { ArrowLeft: [-24, 0], ArrowRight: [24, 0], ArrowUp: [0, -24], ArrowDown: [0, 24] };
        if (Object.hasOwn(arrows, event.key)) move(...arrows[event.key]);
        else if (event.key === '+' || event.key === '=') zoom(0.24);
        else if (event.key === '-') zoom(-0.24);
        else return;
        event.preventDefault();
    });
    return { get paused() { return hover || pointer !== null; } };
}
