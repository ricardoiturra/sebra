import { Box3, Quaternion, Vector3 } from 'three';

export function verifiedSelection(payload, graph) {
    if (payload?.version !== 1 || payload.status !== 'matched' || !Array.isArray(payload.display_node_ids)) return null;
    const available = new Set(graph.nodes.map(({ id }) => id));
    const ids = [...new Set(payload.display_node_ids.filter((id) => typeof id === 'string' && available.has(id)))].slice(0, 200);
    if (!ids.length) return null;
    const selected = new Set(ids);
    const relations = new Map((graph.relations || []).map((edge) => [edge.id, edge]));
    const edges = (Array.isArray(payload.relations) ? payload.relations : []).filter((edge) => {
        const stored = relations.get(edge.id);
        return stored && stored.source === edge.source && stored.target === edge.target && stored.relation === edge.relation && stored.directed === edge.directed
            && (stored.confidence || '') === (edge.confidence || '')
            && selected.has(stored.source) && selected.has(stored.target);
    }).slice(0, 400);
    return {
        ids, edges,
        focus: (Array.isArray(payload.focus_ids) ? payload.focus_ids : []).filter((id) => selected.has(id)),
        message: payload.message || '', truncated: payload.truncated === true,
    };
}

export function focusSelection(camera, globe, positions) {
    if (!positions.length) return false;
    const center = positions.reduce((sum, point) => sum.add(point), new Vector3()).divideScalar(positions.length);
    if (center.lengthSq() > 1e-6) {
        globe.quaternion.copy(new Quaternion().setFromUnitVectors(center.normalize(), new Vector3(0, 0, 1)));
    }
    globe.updateMatrixWorld(true);
    const bounds = new Box3().setFromPoints(positions.map((point) => point.clone().applyMatrix4(globe.matrixWorld)));
    const target = bounds.getCenter(new Vector3());
    const size = bounds.getSize(new Vector3());
    camera.position.set(target.x, target.y, 2);
    camera.lookAt(target.x, target.y, 0);
    camera.zoom = Math.max(0.55, Math.min(5, 0.8 * Math.min(
        (camera.right - camera.left) / Math.max(0.35, size.x),
        (camera.top - camera.bottom) / Math.max(0.35, size.y),
    )));
    camera.updateProjectionMatrix();
    return true;
}

export function restoreCamera(camera, globe) {
    globe.quaternion.identity();
    camera.position.set(0, 0, 2);
    camera.quaternion.identity();
    camera.zoom = 1;
    camera.updateProjectionMatrix();
}
