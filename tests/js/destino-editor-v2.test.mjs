// Tela do destino 2.3.0 — assets/roteiro-editor.js: escopo (nacional esconde os campos de internacional) e foto de um item
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';

const AQUI = path.dirname(fileURLToPath(import.meta.url));
const JS = fs.readFileSync(path.resolve(AQUI, '../../traveltec-roteiros/assets/roteiro-editor.js'), 'utf8');

// HTML no formato que includes/destino-editor.php produz (só o que o JS usa)
function montar({ escopo = 'internacional', foto = '' } = {}) {
  const html = `<div data-tt-rot>
    <select data-tt-escopo><option value=""></option><option value="nacional"${escopo === 'nacional' ? ' selected' : ''}>N</option><option value="internacional"${escopo === 'internacional' ? ' selected' : ''}>I</option></select>
    <div data-tt-so-inter><input name="tt_destinos[moeda]"></div>
    <span data-tt-foto-unica>
      <img src="${foto ? 'https://x.test/t.jpg' : ''}" alt="" data-tt-foto-previa${foto ? '' : ' hidden'}>
      <input type="hidden" name="tt_destinos[pontos_turisticos][0][foto]" value="${foto}" data-tt-foto-id>
      <button type="button" data-tt-foto-escolher>${foto ? 'Trocar foto' : 'Escolher foto'}</button>
      <button type="button" data-tt-foto-tirar${foto ? '' : ' hidden'}>Tirar</button>
    </span></div>`;
  const dom = new JSDOM(`<!doctype html><body>${html}</body>`, { runScripts: 'outside-only' });
  const j = dom.window;
  j.__quadros = [];
  j.wp = {
    editor: { initialize() {} },
    media(cfg) {
      const quadro = {
        cfg, eventos: {}, abriu: 0,
        on(ev, cb) { this.eventos[ev] = cb; },
        open() { this.abriu++; },
        state: () => ({ get: () => ({ first: () => ({ toJSON: () => j.__escolhida }) }) }),
      };
      j.__quadros.push(quadro);
      return quadro;
    },
  };
  j.eval(JS);
  j.TTRoteiroEditor.iniciar(j.document.querySelector('[data-tt-rot]'));
  return j;
}

const clicar = (el) => el.dispatchEvent(new el.ownerDocument.defaultView.Event('click', { bubbles: true }));

test('escopo: nacional esconde documentação, moeda, idioma e fuso; internacional mostra', () => {
  const j = montar({ escopo: 'nacional' });
  const bloco = j.document.querySelector('[data-tt-so-inter]');
  const sel = j.document.querySelector('[data-tt-escopo]');
  assert.equal(bloco.hidden, true);
  sel.value = 'internacional';
  sel.dispatchEvent(new j.Event('change', { bubbles: true }));
  assert.equal(bloco.hidden, false);
  sel.value = '';
  sel.dispatchEvent(new j.Event('change', { bubbles: true }));
  assert.equal(bloco.hidden, false, 'sem escopo (destino antigo): mostra');
});

test('foto do item: escolher na Biblioteca grava o ID e mostra a miniatura; tirar limpa', () => {
  const j = montar();
  const caixa = j.document.querySelector('[data-tt-foto-unica]');
  const id = caixa.querySelector('[data-tt-foto-id]');
  const img = caixa.querySelector('[data-tt-foto-previa]');
  const tirar = caixa.querySelector('[data-tt-foto-tirar]');
  const escolher = caixa.querySelector('[data-tt-foto-escolher]');
  clicar(escolher);
  assert.equal(j.__quadros.length, 1);
  assert.equal(j.__quadros[0].cfg.multiple, false, 'uma foto só');
  j.__escolhida = { id: 201, url: 'https://x.test/grande.jpg', sizes: { thumbnail: { url: 'https://x.test/mini.jpg' } } };
  j.__quadros[0].eventos.select();
  assert.equal(id.value, '201');
  assert.equal(img.src, 'https://x.test/mini.jpg');
  assert.equal(img.hidden, false);
  assert.equal(tirar.hidden, false);
  assert.equal(escolher.textContent, 'Trocar foto');
  clicar(escolher);
  assert.equal(j.__quadros.length, 1, 'a janela de mídia é reaproveitada');
  clicar(tirar);
  assert.equal(id.value, '');
  assert.equal(img.hidden, true);
  assert.equal(tirar.hidden, true);
  assert.equal(escolher.textContent, 'Escolher foto');
});
