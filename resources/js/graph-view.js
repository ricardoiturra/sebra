import { Group, OrthographicCamera, Scene, Vector3, WebGLRenderer } from 'three';
import { connectGraphToolbar } from './graph-toolbar.js';
import { connectNodeDetails } from './graph-node-details.js';
import { createGraphLabels } from './graph-labels.js';
import { connectGraph } from './graph.js';
import { connectGraphControls } from './graph-controls.js';

export function mountGraph(container) {
    const scene = new Scene();
    const camera = new OrthographicCamera(-1, 1, 1, -1, 0.1, 10);
    camera.position.z = 2;
    const renderer = new WebGLRenderer({ alpha: true, antialias: true });
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);
    const globe = new Group();
    const axis = new Vector3(0, 1, 0);
    scene.add(globe);
    let direction = 1;
    const controls = connectGraphControls(renderer.domElement, camera, globe, () => toolbar.isSpherical());

    const events = new EventSource(container.dataset.graphEvents);
    const graph = connectGraph(container, globe, events, reducedMotion);
    graph.setSpherical(true);
    const toolbar = connectGraphToolbar(container, graph, globe, camera);
    const labels = createGraphLabels(container);
    const details = connectNodeDetails(container, renderer.domElement, graph, globe, camera);

    function updateCamera(aspect) {
        const fit = 1.16 * Math.max(1, 1 / aspect);
        camera.left = -aspect * fit;
        camera.right = aspect * fit;
        camera.top = fit;
        camera.bottom = -fit;
        camera.updateProjectionMatrix();
    }

    events.addEventListener('sphere-direction', (event) => {
        direction = JSON.parse(event.data).direction;
    });
    const resize = new ResizeObserver(() => {
        const { clientWidth: width, clientHeight: height } = container;
        if (!width || !height) return;
        const aspect = width / height;
        updateCamera(aspect);
        renderer.setSize(width, height);
        graph.resize(aspect);
    });
    resize.observe(container);

    let previousTime;
    renderer.setAnimationLoop((time) => {
        const elapsed = Math.min(time - (previousTime ?? time), 50);
        previousTime = time;
        const turn = toolbar.isSpherical() && !controls.paused && !reducedMotion.matches ? elapsed * 0.00055 * direction : 0;
        graph.animate(elapsed);
        if (turn) globe.rotateOnWorldAxis(axis, turn);
        renderer.render(scene, camera);
        labels.update(camera.zoom >= 1.6 ? graph.labelNodes() : [], globe, camera, toolbar.isSpherical());
    });

    window.addEventListener('pagehide', (event) => {
        if (event.persisted) return;
        events.close();
        graph.dispose();
        labels.dispose();
        details.dispose();
        toolbar.dispose();
        resize.disconnect();
        renderer.setAnimationLoop(null);
        renderer.dispose();
    });
}
