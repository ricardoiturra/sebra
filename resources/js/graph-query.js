export function connectQuery(form) {
    const status = document.querySelector('#graph-query-status');
    const submit = form.querySelector('button[type="submit"]');
    const answer = document.querySelector('#graph-answer');
    const text = document.querySelector('#graph-answer-text');
    let busy = false;
    document.querySelector('#graph-copy-answer').addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(text.textContent);
            status.textContent = 'Respuesta copiada.';
        } catch {
            status.textContent = 'El navegador no permitió copiar la respuesta.';
        }
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        submit.disabled = true;
        form.setAttribute('aria-busy', 'true');
        form.dispatchEvent(new CustomEvent('sebra:query-start'));
        status.textContent = 'Consultando el grafo…';
        // Keep the previous written answer visible until a new answer arrives.
        try {
            const response = await fetch(form.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ question: form.elements.question.value }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'No se pudo completar la consulta.');
            form.queryResult = result;
            text.textContent = result.answer ?? '';
            answer.hidden = !result.answer;
            answer.open = Boolean(result.answer);
            status.textContent = result.message || '';
            form.dispatchEvent(new CustomEvent('sebra:query-answer', { detail: result }));
        } catch (error) {
            status.textContent = error.message || 'No se pudo completar la consulta.';
            form.dispatchEvent(new CustomEvent('sebra:query-error'));
        } finally {
            busy = false;
            submit.disabled = false;
            form.setAttribute('aria-busy', 'false');
        }
    });
}
