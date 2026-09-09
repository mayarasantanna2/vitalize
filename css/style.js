'use strict';
const toggle = document.querySelector('.menu-toggle');
toggle?.addEventListener('click', () => {
  const open = toggle.getAttribute('aria-expanded') !== 'true';
  toggle.setAttribute('aria-expanded', String(open));
  document.getElementById('menu').classList.toggle('open', open);
});
// Remove the legacy shared-device value without reading or displaying it.
try { localStorage.removeItem('vitalize_restricoes_alimentares'); } catch (_) {}
const wellness = document.getElementById('wellness');
wellness?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const button = document.getElementById('gerarPlano');
  if (button.disabled) return;
  const result = document.getElementById('resultadoIA');
  button.disabled = true;
  result.textContent = 'Preparando sua mensagem…';
  const data = new FormData(wellness);
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 35000);
  try {
    const response = await fetch(wellness.dataset.endpoint, {
      method: 'POST', credentials: 'same-origin', signal: controller.signal,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
      body: JSON.stringify({ humor: data.get('humor'), sintomas: data.getAll('sintomas[]'), restricoes: data.get('restricoes'), consentimento: data.get('consentimento') === '1' })
    });
    const payload = await response.json();
    if (!response.ok) throw new Error(payload.erro || 'Não foi possível gerar a mensagem.');
    if (!Array.isArray(payload.itens) || typeof payload.mensagem !== 'string') throw new Error('Resposta inesperada. Tente novamente.');
    result.replaceChildren();
    const title = document.createElement('h3'); title.textContent = payload.titulo;
    const text = document.createElement('p'); text.textContent = payload.mensagem;
    result.append(title, text);
    for (const item of payload.itens) { const p = document.createElement('p'); p.textContent = `${item.nome} — ${item.quantidade}`; result.append(p); }
    if (payload.preparo) { const p = document.createElement('p'); p.textContent = payload.preparo; result.append(p); }
    const note = document.createElement('small'); note.textContent = payload.observacao; result.append(note);
  } catch (error) {
    result.textContent = error.name === 'AbortError' ? 'O serviço demorou a responder. Tente novamente em instantes.' : error.message;
  } finally { clearTimeout(timeout); button.disabled = false; }
});
