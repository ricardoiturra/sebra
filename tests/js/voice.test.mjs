import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { spawnSync } from 'node:child_process';
import { randomUUID } from 'node:crypto';
import { chromium, expect } from '@playwright/test';

let browser, server, base;

before(async () => {
    const compiledViews = `/tmp/sebra-voice-fixture-${randomUUID()}`;
    const render = `
        $compiled = getenv("VIEW_COMPILED_PATH");
        if (!is_dir($compiled)) { mkdir($compiled, 0700, true); }
        try {
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
            if (config("database.default") !== "sqlite" || config("database.connections.sqlite.database") !== ":memory:" || config("database.connections.sqlite.url") || config("view.compiled") !== $compiled) {
                throw new RuntimeException("Voice fixtures require isolated database and compiled views.");
            }
            echo view("components.voice-controls", ["project" => null])->render();
        } finally {
            foreach (glob($compiled."/*.php") ?: [] as $view) { unlink($view); }
            rmdir($compiled);
        }
    `;
    const rendered = process.env.SEBRA_VOICE_FIXTURE_HTML
        ? { status: 0, stdout: await readFile(process.env.SEBRA_VOICE_FIXTURE_HTML, 'utf8'), stderr: '' }
        : spawnSync('docker', [
        'compose', 'exec', '-T',
        '-e', 'APP_ENV=testing', '-e', 'DB_CONNECTION=sqlite', '-e', 'DB_DATABASE=:memory:', '-e', 'DB_URL=',
        '-e', 'CACHE_STORE=array', '-e', 'SESSION_DRIVER=array', '-e', 'LOG_CHANNEL=stderr',
        '-e', `APP_CONFIG_CACHE=${compiledViews}/unused-config.php`,
        '-e', `VIEW_COMPILED_PATH=${compiledViews}`,
        'web', 'php', '-r', render,
    ], { encoding: 'utf8', timeout: 30000 });
    assert.equal(rendered.status, 0, rendered.stderr);
    const manifest = JSON.parse(await readFile('public/build/manifest.json', 'utf8'));
    const css = '/build/' + manifest['resources/css/app.css'].file;
    const html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="fixture-token"><link rel="stylesheet" href="${css}"><script type="importmap">{"imports":{"three":"/three.module.js"}}</script></head>
<body class="min-h-screen bg-slate-950 p-5 text-slate-100"><main class="mx-auto max-w-xl rounded-xl bg-slate-900 p-5">
<h1 class="mb-4 text-xl">SeBra · Consulta de conocimiento</h1>
<form id="graph-query" data-url="/ask"><label for="graph-question">Pregunta sobre el grafo</label><div class="mt-2 flex flex-wrap gap-2">
<input id="graph-question" name="question" required maxlength="500" class="min-w-0 flex-1 rounded-lg bg-slate-950 p-2">
<button id="voice-record" type="button" aria-label="Iniciar grabación" disabled class="rounded-lg border border-cyan-700 px-3 py-2">Micrófono</button>
<button type="submit" class="rounded-lg bg-cyan-300 px-4 py-2 text-slate-950">Consultar a la IA</button></div><p id="graph-query-status" role="status"></p></form>
${rendered.stdout}
<details id="graph-answer" hidden class="mt-4"><summary>Respuesta</summary><button id="graph-copy-answer">Copiar</button><pre id="graph-answer-text" class="whitespace-pre-wrap break-words"></pre></details>
</main><script type="module">
import {connectQuery} from '/resources/js/graph-query.js'; import {connectVoice} from '/resources/js/voice-controls.js';
const form = document.querySelector('#graph-query'), root = document.querySelector('[data-voice-config]');
const config = JSON.parse(root.dataset.voiceConfig), params = new URLSearchParams(location.search);
config.stt = params.get('stt') || 'browser'; config.tts = params.get('tts') || 'browser'; config.transcribeUrl = '/transcribe'; config.speakUrl = '/speak';
root.dataset.voiceConfig = JSON.stringify(config); connectQuery(form); connectVoice(form, root); window.fixtureReady = true;
</script></body></html>`;
    server = createServer(async (request, response) => {
        const path = new URL(request.url, 'http://localhost').pathname;
        if (path === '/') { response.setHeader('Content-Type', 'text/html; charset=utf-8'); response.end(html); return; }
        if (['/three.module.js', '/three.core.js'].includes(path)) {
            response.setHeader('Content-Type', 'text/javascript');
            response.end(await readFile('node_modules/three/build' + path)); return;
        }
        if (!path.startsWith('/resources/js/') && !path.startsWith('/build/assets/')) { response.writeHead(404).end(); return; }
        try {
            const file = path.startsWith('/build/') ? 'public' + path : '.' + path;
            response.setHeader('Content-Type', path.endsWith('.css') ? 'text/css' : 'text/javascript');
            response.end(await readFile(file));
        } catch { response.writeHead(404).end(); }
    });
    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    base = `http://127.0.0.1:${server.address().port}`;
    browser = await chromium.launch({ headless: true, args: ['--use-angle=swiftshader', '--enable-unsafe-swiftshader'] });
});
after(async () => { await browser?.close(); if (server) await new Promise((resolve) => server.close(resolve)); });

async function pageFor(t) {
    const page = await browser.newPage();
    page.setDefaultTimeout(5000);
    t.after(() => page.close());
    return page;
}
async function open(page, query = '') { await page.goto(base + query); await page.waitForFunction(() => window.fixtureReady); }
async function queryReply(page, answer = 'Los equipos agrupan usuarios. Fuente: app/Models/Team.php') {
    await page.route('**/ask', (route) => route.fulfill({ json: { answer, speech_text: 'Los equipos agrupan usuarios. Referencia en pantalla.' } }));
}
async function browserRecognition(page) {
    await page.addInitScript(() => {
        window.recordings = 0;
        window.SpeechRecognition = class {
            constructor() { window.recognition = this; }
            start() { window.recordings++; }
            stop() { this.onend?.(); }
            abort() { window.aborted = true; this.onend?.(); }
        };
    });
}
async function mediaRecorder(page) {
    await page.addInitScript(() => {
        window.micRequests = 0;
        Object.defineProperty(navigator, 'mediaDevices', { value: { getUserMedia: async () => {
            window.micRequests++;
            return { getTracks: () => [{ stop: () => { window.trackStopped = true; } }] };
        } } });
        window.MediaRecorder = class {
            static isTypeSupported(type) { return type === 'audio/mp4'; }
            constructor(stream, options) { window.recorder = this; window.recordedFormat = options.mimeType; this.state = 'inactive'; }
            start() { this.state = 'recording'; }
            stop() {
                this.state = 'inactive';
                this.ondataavailable?.({ data: new Blob(['fixture recording'], { type: 'audio/mp4' }) });
                this.onstop?.();
            }
        };
    });
}

test('optional voice never starts automatically and cancelling discards delayed recognition', async (t) => {
    const page = await pageFor(t);
    await browserRecognition(page);
    await open(page);
    assert.equal(await page.evaluate(() => window.recordings), 0);
    await expect(page.locator('#voice-record')).toBeDisabled();
    await page.locator('#graph-question').fill('Texto anterior');
    await page.locator('#voice-consent').check();
    await page.locator('#voice-record').click();
    await expect(page.locator('[data-voice-config]')).toHaveAttribute('data-state', 'recording');
    await page.locator('#voice-cancel').click();
    await page.evaluate(() => { window.recognition.onresult({ results: [{ isFinal: true, 0: { transcript: 'Creá un nodo' } }] }); window.recognition.onend(); });
    await expect(page.locator('#graph-question')).toHaveValue('Texto anterior');
    assert.equal(await page.evaluate(() => window.aborted), true);
    await expect(page.locator('#voice-status')).toContainText('cancelada');
});

test('automatic submission is opt-in and always targets the existing read query', async (t) => {
    const page = await pageFor(t);
    await browserRecognition(page);
    let questions = [];
    await page.route('**/ask', (route) => { questions.push(route.request().postDataJSON()); return route.fulfill({ status: 422, json: { message: 'Esta consulta es de solo lectura.' } }); });
    await open(page);
    await page.locator('#voice-consent').check();
    await page.locator('#voice-auto-send').check();
    await page.locator('#voice-record').click();
    await page.evaluate(() => { window.recognition.onresult({ results: [{ isFinal: true, 0: { transcript: 'Creá una nota' } }] }); window.recognition.onend(); });
    await expect(page.locator('#graph-query-status')).toContainText('solo lectura');
    assert.deepEqual(questions, [{ question: 'Creá una nota' }]);
    await expect(page.locator('#graph-question')).toHaveValue('Creá una nota');
});

test('MediaRecorder detects format, stops tracks, and lets users edit before submitting', async (t) => {
    const page = await pageFor(t);
    await mediaRecorder(page);
    let questions = [], uploads = 0;
    await page.route('**/transcribe', async (route) => { uploads++; assert.match(route.request().postDataBuffer().toString(), /recording\.mp4/); await route.fulfill({ json: { text: 'usuarios' } }); });
    await page.route('**/ask', (route) => { questions.push(route.request().postDataJSON()); return route.fulfill({ json: { answer: 'Respuesta escrita', speech_text: 'Respuesta escrita' } }); });
    await open(page, '?stt=compatible&tts=disabled');
    assert.equal(await page.evaluate(() => window.micRequests), 0);
    await page.locator('#voice-consent').check();
    await page.locator('#voice-record').click();
    await expect(page.locator('#voice-record')).toHaveAttribute('aria-pressed', 'true');
    await page.locator('#voice-record').click();
    await expect(page.locator('#graph-question')).toHaveValue('usuarios');
    assert.equal(uploads, 1);
    assert.equal(questions.length, 0);
    assert.equal(await page.evaluate(() => window.recordedFormat), 'audio/mp4');
    assert.equal(await page.evaluate(() => window.trackStopped), true);
    await page.locator('#graph-question').fill('¿Cómo se administran los usuarios?');
    await page.getByRole('button', { name: 'Consultar a la IA' }).click();
    await expect(page.locator('#graph-answer-text')).toHaveText('Respuesta escrita');
    assert.deepEqual(questions, [{ question: '¿Cómo se administran los usuarios?' }]);
});

test('cancelling transcription aborts upload and never auto-submits a late result', async (t) => {
    const page = await pageFor(t);
    await mediaRecorder(page);
    let uploads = 0, asks = 0, release;
    const pending = new Promise((resolve) => { release = resolve; });
    await page.route('**/transcribe', async (route) => { uploads++; await pending; await route.fulfill({ json: { text: 'late answer' } }).catch(() => {}); });
    page.on('request', (request) => { if (request.url().endsWith('/ask')) asks++; });
    await open(page, '?stt=compatible');
    await page.locator('#voice-consent').check();
    await page.locator('#voice-auto-send').check();
    await page.locator('#voice-record').click();
    await page.locator('#voice-record').click();
    await expect.poll(() => uploads).toBe(1);
    await expect(page.locator('[data-voice-config]')).toHaveAttribute('data-state', 'transcribing');
    await page.locator('#voice-cancel').click();
    release();
    await page.locator('#graph-question').fill('Sigue funcionando');
    await expect(page.locator('#voice-status')).toContainText('cancelada');
    assert.equal(asks, 0);
    assert.equal(await page.evaluate(() => window.trackStopped), true);
});

test('failed STT leaves the keyboard available', async (t) => {
    const page = await pageFor(t);
    await mediaRecorder(page);
    await page.route('**/transcribe', (route) => route.fulfill({ status: 502, json: { message: 'STT no disponible' } }));
    await queryReply(page);
    await open(page, '?stt=compatible');
    await page.locator('#voice-consent').check();
    await page.locator('#voice-record').click();
    await page.locator('#voice-record').click();
    await expect(page.locator('#voice-status')).toContainText('STT no disponible');
    await page.locator('#graph-question').fill('equipos');
    await page.getByRole('button', { name: 'Consultar a la IA' }).click();
    await expect(page.locator('#graph-answer-text')).toContainText('Los equipos agrupan usuarios');
});

test('TTS failure never removes the written answer or prevents the next text query', async (t) => {
    const page = await pageFor(t);
    let asks = 0;
    page.on('request', (request) => { if (request.url().endsWith('/ask')) asks++; });
    await queryReply(page);
    await page.route('**/speak', (route) => route.fulfill({ status: 502, json: { message: 'TTS no disponible' } }));
    await open(page, '?stt=disabled&tts=compatible');
    await expect(page.locator('#voice-record')).toBeDisabled();
    await page.locator('#voice-consent').check();
    await page.locator('#voice-auto-read').check();
    await page.locator('#graph-question').fill('equipos');
    await page.getByRole('button', { name: 'Consultar a la IA' }).click();
    await expect(page.locator('#voice-status')).toContainText('TTS no disponible');
    await expect(page.locator('#graph-answer-text')).toContainText('app/Models/Team.php');
    await page.locator('#graph-question').fill('usuarios');
    await page.getByRole('button', { name: 'Consultar a la IA' }).click();
    await expect.poll(() => asks).toBe(2);
    await expect(page.locator('#graph-answer')).toBeVisible();
});

test('browser synthesis supports pause, resume, stop and uses the oral text', async (t) => {
    const page = await pageFor(t);
    await page.addInitScript(() => {
        window.playback = { pauses: 0, resumes: 0, stops: 0 };
        window.SpeechSynthesisUtterance = class { constructor(text) { this.text = text; } };
        Object.defineProperty(window, 'speechSynthesis', { value: {
            getVoices: () => [{ lang: 'es-CL', localService: true }],
            speak: (utterance) => { window.spoken = utterance.text; utterance.onstart(); },
            pause: () => { window.playback.pauses++; }, resume: () => { window.playback.resumes++; }, cancel: () => { window.playback.stops++; },
        } });
    });
    await queryReply(page);
    await open(page);
    await page.locator('#graph-question').fill('equipos');
    await page.getByRole('button', { name: 'Consultar a la IA' }).click();
    await expect(page.locator('#graph-answer')).toBeVisible();
    await page.locator('#voice-consent').check();
    await page.locator('#voice-play').click();
    await expect(page.locator('#voice-status')).toContainText('Reproduciendo');
    assert.equal(await page.evaluate(() => window.spoken), 'Los equipos agrupan usuarios. Referencia en pantalla.');
    await page.locator('#voice-pause').click();
    await page.locator('#voice-play').click();
    await page.locator('#voice-stop').click();
    assert.deepEqual(await page.evaluate(() => window.playback), { pauses: 1, resumes: 1, stops: 1 });
    await expect(page.locator('#voice-status')).toContainText('detenida');
    await page.screenshot({ path: '/tmp/sebra-voice-desktop.png', fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({ path: '/tmp/sebra-voice-mobile.png', fullPage: true });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
});


test('restored sphere keeps community events and manual controls without automatic query focus', async (t) => {
    const page = await pageFor(t);
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });
    const graph = {
        nodes: [{id:'a', label:'User', name:'Accounts', community:0}, {id:'b', label:'Team', name:'Accounts', community:0}, {id:'c', label:'Project', name:'Projects', community:1}],
        links:[['a','b'],['b','c']], hyperedges:[],
        relations:[{id:'edge:ab',source:'a',target:'b',relation:'belongs to',directed:false}],
    };
    const selection = {version:1,status:'matched',display_node_ids:['a','b'],focus_ids:['a'],node_ids:['a','b'],relations:graph.relations};
    let release;
    const pending = new Promise((resolve) => {release = resolve;});
    await page.route('**/fixture-graph', (route) => route.fulfill({json:graph}));
    await page.route('**/ask', async (route) => {await pending; await route.fulfill({json:{answer:'User pertenece a Team.', speech_text:'User pertenece a Team.', selection}});});
    await open(page, '?stt=disabled&tts=disabled');
    await page.evaluate(async () => {
        const three = await import('three');
        const {connectGraph} = await import('/resources/js/graph.js');
        const {connectGraphControls} = await import('/resources/js/graph-controls.js');
        const scene = new three.Scene(), globe = new three.Group(); scene.add(globe);
        const camera = new three.OrthographicCamera(-1.16,1.16,1.16,-1.16,0.1,10);camera.position.z=2;
        const renderer = new three.WebGLRenderer({antialias:true});renderer.setSize(360,360);document.body.append(renderer.domElement);
        const container=document.createElement('div');container.dataset.graphUrl='/fixture-graph';document.body.append(container);
        const events = new EventTarget();
        const graph=connectGraph(container,globe,events,matchMedia('(prefers-reduced-motion: reduce)'));
        graph.setSpherical(true);
        connectGraphControls(renderer.domElement,camera,globe,()=>true);
        window.visual={graph,globe,camera,renderer,events,frames:0};
        renderer.setAnimationLoop(()=>{window.visual.frames++;graph.animate(40);renderer.render(scene,camera);});
    });
    await page.waitForFunction(() => window.visual.globe.children.length > 0);
    const before = await page.evaluate(() => window.visual.frames);
    await page.locator('#graph-question').fill('Muéstrame los usuarios');
    await page.getByRole('button',{name:'Consultar a la IA'}).click();
    await expect.poll(() => page.evaluate(() => window.visual.frames)).toBeGreaterThan(before+2);
    release();
    await expect(page.locator('#graph-answer-text')).toHaveText('User pertenece a Team.');
    assert.equal(await page.evaluate(() => window.visual.camera.zoom),1);
    assert.equal(await page.evaluate(() => window.visual.graph.hasSelection),undefined);
    await page.evaluate(() => window.visual.events.dispatchEvent(new MessageEvent('sphere-active',{data:JSON.stringify({active:['Accounts']}),lastEventId:Date.now()+'-0'})));
    assert.ok(await page.evaluate(() => window.visual.globe.children[0].children.filter((item)=>item.isPoints)[1].material.opacity) < 0.3);
    await page.evaluate(() => window.visual.renderer.domElement.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowRight'})));
    const rotated = await page.evaluate(() => window.visual.globe.quaternion.toArray());
    assert.notDeepEqual(rotated,[0,0,0,1]);
    await page.evaluate(() => document.querySelector('#graph-query').dispatchEvent(new CustomEvent('sebra:query-answer',{detail:{answer:'No hay resultados',selection:{version:1,status:'none',display_node_ids:[]}}})));
    assert.deepEqual(await page.evaluate(() => window.visual.globe.quaternion.toArray()),rotated);
    await page.evaluate(() => window.visual.events.dispatchEvent(new MessageEvent('sphere-active',{data:JSON.stringify({active:[]}),lastEventId:(Date.now()+1)+'-0'})));
    assert.equal(await page.evaluate(() => window.visual.globe.children[0].children.filter((item)=>item.isPoints).every((item)=>item.material.opacity===1)),true);
    await page.evaluate(() => {window.visual.renderer.setAnimationLoop(null);window.visual.graph.dispose();window.visual.renderer.dispose();});
    assert.deepEqual(errors,[]);
});

test('double click opens verified node details safely and closing restores the graph', async (t) => {
    const page = await pageFor(t);
    await open(page);
    await page.route('**/node?id=*', route => {
        assert.equal(new URL(route.request().url()).searchParams.get('id'), 'real/node');
        return route.fulfill({ json: { node: { id: 'real/node', label: '<img src=x onerror=alert(1)>', source_file: 'app/User.php' },
            neighbors: [{ id: 'team', label: 'Team' }], relations: [{ source: 'real/node', target: 'team', relation: 'belongs_to', directed: true }], hyperedges: [] } });
    });
    await page.evaluate(async () => {
        const { Group, OrthographicCamera, Vector3 } = await import('three');
        const { connectNodeDetails } = await import('/resources/js/graph-node-details.js');
        const container = document.createElement('div');
        container.dataset.nodeUrl = '/node';
        const canvas = document.createElement('canvas');
        canvas.tabIndex = 0;
        canvas.style.width = '300px'; canvas.style.height = '300px';
        canvas.id = 'detail-canvas'; container.append(canvas); document.body.prepend(container);
        const camera = new OrthographicCamera(-1, 1, 1, -1, 0.1, 10); camera.position.z = 2;
        window.nodeDetails = connectNodeDetails(container, canvas, { labelNodes: () => [{ id: 'real/node', text: 'User', position: new Vector3(0, 0, 1) }] }, new Group(), camera);
    });
    const canvas = page.locator('#detail-canvas');
    await canvas.dblclick({ position: { x: 10, y: 10 } });
    await expect(page.getByRole('dialog')).not.toBeVisible();
    await canvas.dblclick({ position: { x: 150, y: 150 } });
    const dialog = page.getByRole('dialog');
    await expect(dialog).toContainText('belongs_to');
    await expect(dialog).toContainText('app/User.php');
    assert.equal(await dialog.locator('img').count(), 0);
    await dialog.getByRole('button', { name: 'Cerrar' }).click();
    await expect(dialog).not.toBeVisible();
    await expect(canvas).toBeFocused();
    await page.evaluate(() => window.nodeDetails.dispose());
    assert.equal(await page.getByRole('dialog').count(), 0);
});

test('graph toolbar toggles 2D, restores 3D orientation and maximizes the graph', async (t) => {
    const page = await pageFor(t);
    await open(page);
    await page.evaluate(async () => {
        const { Group, OrthographicCamera } = await import('three');
        const { connectGraphToolbar } = await import('/resources/js/graph-toolbar.js');
        const container = document.createElement('div');
        container.id = 'toolbar-graph';
        Object.assign(container.style, { position: 'relative', width: '400px', height: '400px' });
        document.body.prepend(container);
        const globe = new Group(); globe.rotateY(0.7);
        const camera = new OrthographicCamera(-1, 1, 1, -1, 0.1, 10);
        camera.position.z = 2; camera.zoom = 2;
        window.toolbarFixture = { globe, camera, calls: [] };
        window.toolbarFixture.toolbar = connectGraphToolbar(container, { setSpherical: value => window.toolbarFixture.calls.push(value) }, globe, camera);
    });
    await page.getByRole('button', { name: 'Vista 2D', exact: true }).click();
    assert.deepEqual(await page.evaluate(() => ({ zoom: toolbarFixture.camera.zoom, rotation: toolbarFixture.globe.rotation.y, spherical: toolbarFixture.toolbar.isSpherical() })), { zoom: 1, rotation: 0, spherical: false });
    await page.getByRole('button', { name: 'Vista 3D', exact: true }).click();
    assert.equal(await page.evaluate(() => toolbarFixture.camera.zoom), 2);
    assert.ok(Math.abs(await page.evaluate(() => toolbarFixture.globe.rotation.y) - 0.7) < 1e-10);
    await page.getByRole('button', { name: 'Maximizar', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Restaurar', exact: true })).toBeVisible();
    assert.equal(await page.evaluate(() => document.fullscreenElement.id), 'toolbar-graph');
    await page.getByRole('button', { name: 'Restaurar', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Maximizar', exact: true })).toBeVisible();
    assert.equal(await page.evaluate(() => document.fullscreenElement), null);
    await page.evaluate(() => toolbarFixture.toolbar.dispose());
    assert.equal(await page.getByRole('group', { name: 'Vista del grafo' }).count(), 0);
});
