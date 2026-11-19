export function connectGraphToolbar(container, graph, globe, camera) {
    let spherical = true;
    const saved = new Map();
    const toolbar = document.createElement('div');
    toolbar.setAttribute('role', 'group');
    toolbar.setAttribute('aria-label', 'Vista del grafo');
    Object.assign(toolbar.style, { position: 'absolute', top: '12px', right: '12px', display: 'flex', gap: '8px', zIndex: '2' });
    const button = text => {
        const element = document.createElement('button');
        element.type = 'button'; element.textContent = text;
        Object.assign(element.style, { background: '#111827', color: '#cffafe', border: '1px solid #475569', borderRadius: '8px', padding: '8px 12px', cursor: 'pointer' });
        toolbar.append(element);
        return element;
    };
    const mode = button('Vista 2D');
    mode.setAttribute('aria-pressed', 'false');
    const maximize = button('Maximizar');
    const status = document.createElement('span');
    status.setAttribute('role', 'status');
    Object.assign(status.style, { background: '#111827', color: '#e2e8f0', maxWidth: '220px' });
    toolbar.append(status);
    container.append(toolbar);
    const toggleMode = () => {
        saved.set(spherical, { rotation: globe.quaternion.clone(), position: camera.position.clone(), zoom: camera.zoom });
        spherical = !spherical;
        const state = saved.get(spherical);
        if (state) {
            globe.quaternion.copy(state.rotation);
            camera.position.copy(state.position);
            camera.zoom = state.zoom;
        } else {
            globe.quaternion.identity();
            camera.position.set(0, 0, 2);
            camera.zoom = 1;
        }
        camera.updateProjectionMatrix();
        graph.setSpherical(spherical);
        mode.textContent = spherical ? 'Vista 2D' : 'Vista 3D';
        mode.setAttribute('aria-pressed', String(!spherical));
    };
    const syncFullscreen = () => {
        const expanded = document.fullscreenElement === container;
        maximize.textContent = expanded ? 'Restaurar' : 'Maximizar';
        maximize.setAttribute('aria-pressed', String(expanded));
        container.style.backgroundColor = expanded ? '#080e19' : '';
    };
    const toggleFullscreen = async () => {
        status.textContent = '';
        try {
            if (document.fullscreenElement === container) await document.exitFullscreen();
            else await container.requestFullscreen();
        } catch {
            status.textContent = 'No se pudo activar pantalla completa en este navegador.';
        }
    };
    mode.addEventListener('click', toggleMode);
    maximize.addEventListener('click', toggleFullscreen);
    document.addEventListener('fullscreenchange', syncFullscreen);
    syncFullscreen();
    return { isSpherical: () => spherical, dispose() {
        document.removeEventListener('fullscreenchange', syncFullscreen);
        toolbar.remove();
    } };
}
