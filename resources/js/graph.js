import {
    BufferAttribute, BufferGeometry, DynamicDrawUsage, Group, LineBasicMaterial,
    LineSegments, Points, PointsMaterial, Quaternion, Vector3,
} from 'three';
import { communityPalette } from './graph-palette.js';
import { createPlanarSimulation } from './graph-forces.js';
import { createSphericalProjection } from './graph-sphere.js';
import { createInnerNode, driftOnInnerSphere } from './inner-sphere.js';

export function drawGraph(graph, scene, aspect) {
    let spherical = false;
    const sphericalUniform = { value: 0 };
    const projected = new Map();
    const from = new Vector3();
    const to = new Vector3();
    const point = new Vector3();
    const turn = new Quaternion();
    const rotation = new Quaternion();
    const innerNodes = new Map();
    const groups = new Map();
    for (const node of graph.nodes) {
        if (!groups.has(node.community)) groups.set(node.community, { id: node.community, name: node.name, nodes: [] });
        groups.get(node.community).nodes.push(node);
    }
    const communities = [...groups.values()].sort((a, b) => String(a.id).localeCompare(String(b.id), 'en', { numeric: true }));
    const palette = communityPalette(communities.length);
    const simulation = createPlanarSimulation(communities, graph.links, aspect);
    const projectSphere = createSphericalProjection(simulation.bodies.values(), simulation.links);
    simulation.randomize();
    const hyperedges = graph.hyperedges.map((edge) => edge.map((id) => simulation.bodies.get(id)).filter(Boolean));
    const edgeCapacity = simulation.links.length + hyperedges.reduce((total, edge) => total + edge.length, 0);
    const degrees = new Map(graph.nodes.map(({ id }) => [id, 0]));
    for (const [source, target] of graph.links) {
        for (const id of new Set([source, target])) {
            if (degrees.has(id)) degrees.set(id, degrees.get(id) + 1);
        }
    }
    for (const edge of graph.hyperedges) {
        for (const id of new Set(edge)) {
            if (degrees.has(id)) degrees.set(id, degrees.get(id) + 1);
        }
    }
    const maxDegree = [...degrees.values()].reduce((max, degree) => Math.max(max, degree), 1);
    const layer = new Group();
    const ordinary = makeLines('#8ba4be', 0.23);
    const bright = makeLines('#b9fbff', 0.9);
    const lineGroups = [ordinary, bright];
    let active = new Set();

    function makeLines(color, opacity) {
        const geometry = new BufferGeometry();
        const attribute = new BufferAttribute(new Float32Array(edgeCapacity * 72), 3).setUsage(DynamicDrawUsage);
        geometry.setAttribute('position', attribute);
        const material = new LineBasicMaterial({ color, opacity, transparent: true, depthWrite: false });
        const lines = new LineSegments(geometry, material);
        lines.frustumCulled = false;
        layer.add(lines);
        return { geometry, attribute, material, count: 0 };
    }

    function appendLine(a, b, isBright) {
        const line = isBright ? bright : ordinary;
        if (!spherical) {
            line.attribute.setXYZ(line.count++, a.x, a.y, 0);
            line.attribute.setXYZ(line.count++, b.x, b.y, 0);
            return;
        }

        from.copy(projected.get(a) ?? spherePoint(a).multiplyScalar(1.025));
        to.copy(projected.get(b) ?? spherePoint(b).multiplyScalar(1.025));
        const startRadius = from.length();
        const endRadius = to.length();
        from.divideScalar(startRadius);
        to.divideScalar(endRadius);
        turn.setFromUnitVectors(from, to);
        for (let i = 0; i <= 12; i++) {
            const progress = i / 12;
            const radius = startRadius + (endRadius - startRadius) * progress;
            point.copy(from).applyQuaternion(rotation.identity().slerp(turn, progress)).multiplyScalar(radius);
            line.attribute.setXYZ(line.count++, point.x, point.y, point.z);
            if (i > 0 && i < 12) line.attribute.setXYZ(line.count++, point.x, point.y, point.z);
        }
    }

    function spherePoint(body) {
        return projectSphere(body, aspect);
    }

    const items = communities.map((community, index) => {
        const geometry = new BufferGeometry();
        const attribute = new BufferAttribute(new Float32Array(community.nodes.length * 3), 3).setUsage(DynamicDrawUsage);
        geometry.setAttribute('position', attribute);
        const sizes = community.nodes.map(({ id }) => 3 + 8 * Math.log1p(degrees.get(id)) / Math.log1p(maxDegree));
        const pointSizes = new BufferAttribute(new Float32Array(sizes), 1).setUsage(DynamicDrawUsage);
        geometry.setAttribute('nodeSize', pointSizes);
        const material = new PointsMaterial({ color: palette[index], size: 1, transparent: true, depthWrite: false });
        material.onBeforeCompile = (shader) => {
            shader.uniforms.graphSpherical = sphericalUniform;
            shader.vertexShader = 'uniform float graphSpherical;\nattribute float nodeSize;\nvarying float vDepthOpacity;\n' + shader.vertexShader.replace(
                'gl_PointSize = size;',
                `gl_PointSize = size * nodeSize;
                float relativeDepth = mvPosition.z - (modelViewMatrix * vec4(0.0, 0.0, 0.0, 1.0)).z;
                vDepthOpacity = mix(1.0, mix(0.25, 1.0, smoothstep(-0.2, 0.2, relativeDepth)), graphSpherical);`,
            );
            shader.fragmentShader = 'varying float vDepthOpacity;\n' + shader.fragmentShader.replace(
                '#include <clipping_planes_fragment>',
                '#include <clipping_planes_fragment>\nif (distance(gl_PointCoord, vec2(0.5)) > 0.5) discard;',
            ).replace('#include <opaque_fragment>', 'diffuseColor.a *= vDepthOpacity;\n#include <opaque_fragment>');
        };
        const points = new Points(geometry, material);
        points.frustumCulled = false;
        points.renderOrder = 1;
        layer.add(points);

        const bodies = community.nodes.map(({ id }) => simulation.bodies.get(id));
        return { id: community.id, nodes: community.nodes, material, attribute, bodies, sizes, pointSizes, points, depth: 0 };
    });

    function updateGeometry() {
        for (const { attribute, bodies, depth } of items) {
            const inset = depth * depth * (3 - 2 * depth);
            bodies.forEach((body, i) => {
                if (spherical) {
                    const mapped = spherePoint(body);
                    const inner = innerNodes.get(body);
                    if (inner) {
                        turn.setFromUnitVectors(mapped, inner.position);
                        mapped.applyQuaternion(rotation.identity().slerp(turn, inset));
                    }
                    mapped.multiplyScalar(1.025 - 0.47 * inset);
                    projected.set(body, mapped);
                    attribute.setXYZ(i, mapped.x, mapped.y, mapped.z);
                } else {
                    attribute.setXYZ(i, body.x, body.y, 0);
                }
            });
            attribute.needsUpdate = true;
        }

        const emphasized = new Set(items.filter(({ id, depth }) => active.has(id) || depth > 0).map(({ id }) => id));
        ordinary.count = 0;
        bright.count = 0;
        for (const [a, b] of simulation.links) {
            appendLine(a, b, emphasized.has(a.community) && emphasized.has(b.community));
        }

        for (const nodes of hyperedges) {
            if (nodes.length < 2) continue;
            const hub = { x: 0, y: 0 };
            for (const node of nodes) {
                hub.x += node.x / nodes.length;
                hub.y += node.y / nodes.length;
            }
            const highlighted = nodes.filter((node) => emphasized.has(node.community));
            if (spherical) {
                const members = highlighted.length >= 2 ? highlighted : nodes;
                const center = new Vector3();
                for (const member of members) center.add(projected.get(member));
                if (center.lengthSq() > 1e-8) {
                    const radius = members.reduce((sum, member) => sum + projected.get(member).length(), 0) / members.length;
                    projected.set(hub, center.normalize().multiplyScalar(radius));
                }
            }
            const internal = new Set(highlighted.length >= 2 ? highlighted : []);
            for (const node of nodes) appendLine(hub, node, internal.has(node));
            projected.delete(hub);
        }

        for (const { attribute, geometry, count } of lineGroups) {
            geometry.setDrawRange(0, count);
            if (count === 0) continue;
            attribute.clearUpdateRanges();
            attribute.addUpdateRange(0, count * 3);
            attribute.needsUpdate = true;
        }
    }

    function updateOpacity() {
        const deepest = items.reduce((max, entry) => Math.max(max, entry.depth), 0);
        const dim = active.size ? 1 : deepest * deepest * (3 - 2 * deepest);
        ordinary.material.opacity = 0.23 - 0.165 * dim;
        bright.material.opacity = 0.23 + 0.67 * dim;
        for (const entry of items) {
            const lit = active.has(entry.id);
            entry.material.opacity = lit || entry.depth > 0 ? 1 : 1 - 0.82 * dim;
            entry.points.renderOrder = lit || entry.depth > 0 ? 2 : 1;
        }
    }

    function highlight(ids, repulse) {
        for (const entry of items) {
            const { id, sizes, pointSizes } = entry;
            const lit = ids.has(id);
            sizes.forEach((size, index) => pointSizes.setX(index, lit ? Math.max(size, Math.min(24, size * 1.45)) : size));
            pointSizes.needsUpdate = true;
        }
        active = ids;
        if (repulse) simulation.repel(ids);
        updateOpacity();
        updateGeometry();
    }

    scene.add(layer);
    updateGeometry();

    return {
        highlight,
        labelNodes() {
            return items.flatMap(({ nodes, attribute }) => nodes.map((node, index) => ({
                id: node.id, text: String(node.label ?? node.id),
                position: new Vector3().fromBufferAttribute(attribute, index),
            })));
        },
        setSpherical(value) {
            spherical = value;
            sphericalUniform.value = value ? 1 : 0;
            projected.clear();
            updateGeometry();
        },
        idsForName: (name) => communities.filter((community) => community.name === name).map(({ id }) => id),
        resize(nextAspect) {
            aspect = nextAspect;
            simulation.resize(aspect);
            updateGeometry();
        },
        step(time) {
            if (spherical) projectSphere.step(time);
            else simulation.step(time);
            const drifting = [];
            for (const entry of items) {
                if (active.has(entry.id)) {
                    entry.depth = Math.min(1, entry.depth + time / 0.7);
                    if (entry.depth < 1) continue;

                    for (const body of entry.bodies) {
                        if (!innerNodes.has(body)) {
                            innerNodes.set(body, createInnerNode(spherePoint(body), body.phase));
                        }
                        drifting.push(innerNodes.get(body));
                    }
                } else if (entry.depth > 0) {
                    entry.depth = Math.max(0, entry.depth - time / 0.9);
                    if (entry.depth === 0) {
                        for (const body of entry.bodies) innerNodes.delete(body);
                    }
                }
            }
            driftOnInnerSphere(drifting, time);
            updateOpacity();
            updateGeometry();
        },
        dispose() {
            scene.remove(layer);
            for (const child of layer.children) {
                child.geometry.dispose();
                child.material.dispose();
            }
        },
    };
}

export function connectGraph(container, scene, events, reducedMotion) {
    const queryStatus = document.querySelector('#graph-query-status');
    const emptyGraph = document.querySelector('#project-graph-empty');
    let aspect = 1;
    let spherical = false;
    let view;
    let request;
    let pending = 0;
    let selection = [];
    let deadline = 0;
    let lastSelectionEvent = null;

    function applyHighlight(repulse) {
        const ids = selection.flatMap((name) => view?.idsForName(name) ?? []);
        view?.highlight(new Set(ids), repulse && !reducedMotion.matches);
    }

    function select(names) {
        const previous = new Set(selection);
        const changed = names.length !== selection.length || names.some((name) => !previous.has(name));
        const added = names.some((name) => !previous.has(name));
        selection = names;
        if (changed) applyHighlight(added);
    }

    async function refresh() {
        request?.abort();
        const controller = new AbortController();
        request = controller;
        try {
            const response = await fetch(container.dataset.graphUrl, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error();
            const graph = await response.json();
            if (controller.signal.aborted) return;
            view?.dispose();
            view = drawGraph(graph, scene, aspect);
            if (emptyGraph) emptyGraph.hidden = graph.nodes.length > 0;
            if (spherical) view.setSpherical(true);
            if (selection.length) applyHighlight(true);
        } catch {
            if (!controller.signal.aborted) {
                queryStatus.textContent = view ? 'No se pudo actualizar el grafo.' : 'El grafo no está disponible.';
            }
        }
    }

    events.addEventListener('open', refresh);
    events.addEventListener('sphere-graph', refresh);
    const ingestionStatus = document.querySelector('#project-ingestion-status');
    events.addEventListener('sphere-ingestion', (event) => {
        if (ingestionStatus) ingestionStatus.textContent = JSON.parse(event.data).ingestion;
    });
    function applyLiveSelection(active, id) {
        if (lastSelectionEvent !== null && id.localeCompare(lastSelectionEvent, 'en', { numeric: true }) <= 0) return;
        lastSelectionEvent = id;
        const remaining = Math.min(15000, Number.parseInt(id, 10) + 15000 - Date.now());
        deadline = remaining > 0 && active.length ? performance.now() + remaining : 0;
        select(deadline ? active : []);
    }

    events.addEventListener('sphere-active', (event) => {
        const { active } = JSON.parse(event.data);
        applyLiveSelection(active, event.lastEventId);
    });
    refresh();

    return {
        labelNodes: () => view?.labelNodes() ?? [],
        isSpherical: () => spherical,
        setSpherical(value) {
            spherical = value;
            view?.setSpherical(value);
        },
        resize(value) {
            aspect = value;
            view?.resize(value);
        },
        animate(elapsed) {
            if (deadline && deadline <= performance.now()) {
                deadline = 0;
                select([]);
            }
            if (reducedMotion.matches) return;
            pending += elapsed / 1000;
            if (pending < 0.04) return;
            view?.step(pending);
            pending = 0;
        },
        dispose() {
            request?.abort();
            view?.dispose();
        },
    };
}
