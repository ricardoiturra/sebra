import { Vector3 } from 'three';

export function pickGraphNode(nodes, globe, camera, width, height, x, y, spherical = true) {
    globe.updateWorldMatrix(true, false);
    camera.updateMatrixWorld();
    const center = globe.getWorldPosition(new Vector3()).applyMatrix4(camera.matrixWorldInverse);
    let closest = null;
    let distance = 14 ** 2;
    for (const node of nodes) {
        const world = globe.localToWorld(node.position.clone());
        if (spherical && world.clone().applyMatrix4(camera.matrixWorldInverse).z < center.z) continue;
        const screen = world.project(camera);
        if (screen.z < -1 || screen.z > 1) continue;
        const squared = ((screen.x + 1) * width / 2 - x) ** 2 + ((1 - screen.y) * height / 2 - y) ** 2;
        if (squared < distance) { closest = node; distance = squared; }
    }
    return closest;
}

export function connectNodeDetails(container, canvas, graph, globe, camera) {
    const dialog = document.createElement('dialog');
    dialog.setAttribute('aria-label', 'Detalle del nodo');
    Object.assign(dialog.style, { background: '#111827', color: '#e2e8f0', border: '1px solid #475569', borderRadius: '12px', padding: '24px', width: 'min(720px, 90vw)', maxHeight: '85vh', overflow: 'auto' });
    const close = document.createElement('button');
    close.type = 'button';
    close.textContent = 'Cerrar';
    Object.assign(close.style, { float: 'right', padding: '6px 12px', border: '1px solid #64748b', borderRadius: '6px' });
    const content = document.createElement('div');
    content.setAttribute('aria-live', 'polite');
    dialog.append(close, content);
    container.appendChild(dialog);
    let request;
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => { request?.abort(); canvas.focus(); });
    const section = (title, value) => {
        const heading = document.createElement('h3');
        heading.textContent = title;
        Object.assign(heading.style, { fontWeight: 'bold', marginTop: '18px', marginBottom: '8px' });
        const text = document.createElement('pre');
        Object.assign(text.style, { whiteSpace: 'pre-wrap', overflowWrap: 'anywhere', font: '13px/1.6 sans-serif' });
        text.textContent = value;
        content.append(heading, text);
    };
    async function open(event) {
        const rect = canvas.getBoundingClientRect();
        const node = pickGraphNode(graph.labelNodes(), globe, camera, rect.width, rect.height, event.clientX - rect.left, event.clientY - rect.top, graph.isSpherical?.() ?? true);
        if (!node || !container.dataset.nodeUrl) return;
        event.preventDefault();
        request?.abort();
        const controller = new AbortController();
        request = controller;
        content.replaceChildren();
        section(node.text, 'Cargando información del grafo…');
        dialog.showModal();
        try {
            const url = new URL(container.dataset.nodeUrl, window.location.href);
            url.searchParams.set('id', node.id);
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(response.status === 404 ? 'El nodo ya no está disponible. Actualiza el grafo.' : 'No se pudo cargar el detalle. Cierra y vuelve a intentarlo.');
            const detail = await response.json();
            if (controller.signal.aborted) return;
            content.replaceChildren();
            section(String(detail.node.label ?? detail.node.id), Object.entries(detail.node)
                .map(([key, value]) => `${key}: ${typeof value === 'object' ? JSON.stringify(value, null, 2) : value}`).join('\n'));
            const names = new Map(detail.neighbors.map(other => [other.id, other.label]));
            names.set(detail.node.id, detail.node.label ?? detail.node.id);
            section('Relaciones verificadas', detail.relations.length ? detail.relations.map(edge =>
                `${names.get(edge.source) ?? edge.source} ${edge.directed ? '→' : '↔'} ${names.get(edge.target) ?? edge.target}: ${edge.relation}`).join('\n') : 'No hay relaciones directas registradas.');
            if (detail.hyperedges.length) section('Relaciones de grupo', JSON.stringify(detail.hyperedges, null, 2));
            section('Alcance', 'Se muestran los datos y referencias registrados en este grafo. No se añaden inferencias ni se consulta un LLM.');
        } catch (error) {
            if (controller.signal.aborted) return;
            content.replaceChildren();
            section('No se pudo abrir el nodo', error.message);
        }
    }
    canvas.addEventListener('dblclick', open);
    return { dispose() { request?.abort(); canvas.removeEventListener('dblclick', open); dialog.remove(); } };
}
