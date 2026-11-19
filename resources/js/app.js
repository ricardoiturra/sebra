import { connectQuery } from './graph-query.js';

const queryForm = document.querySelector('#graph-query');
if (queryForm) {
    connectQuery(queryForm);
    import('./voice-controls.js').then(({ connectVoice }) => connectVoice(queryForm, document.querySelector('[data-voice-config]'))).catch(() => {
        document.querySelector('#voice-status').textContent = 'No se pudo iniciar la voz. Podés usar el teclado.';
    });
}

const graphMount = document.querySelector('[data-graph-events]');

if (graphMount) {
    import('./graph-view').then(({ mountGraph }) => mountGraph(graphMount)).catch(() => {
        document.querySelector('#graph-query-status').textContent = 'No se pudo iniciar el visualizador.';
    });
}
