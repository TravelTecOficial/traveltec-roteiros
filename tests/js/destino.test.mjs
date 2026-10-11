// Página do destino: o campo Destino do formulário de cotação já vem preenchido — assets/destino.js (jsdom)
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';

const AQUI = path.dirname(fileURLToPath(import.meta.url));
const JS = fs.readFileSync(path.resolve(AQUI, '../../traveltec-roteiros/assets/destino.js'), 'utf8');

// No jsdom o documento ainda está "carregando" quando o script roda: espera o DOMContentLoaded, como no navegador.
async function montar(html, dados) {
  const dom = new JSDOM(`<!doctype html><body>${html}</body>`, { runScripts: 'outside-only' });
  if (dados !== undefined) dom.window.ttDestino = dados;
  dom.window.eval(JS);
  if (dom.window.document.readyState === 'loading') {
    await new Promise((pronto) => dom.window.document.addEventListener('DOMContentLoaded', pronto));
  }
  return dom.window;
}

test('preenche o campo Destino vazio com o nome do destino', async () => {
  const w = await montar('<form><input name="field_destino" value=""></form>', { nome: 'Fernando de Noronha' });
  assert.equal(w.document.querySelector('[name="field_destino"]').value, 'Fernando de Noronha');
});

test('não troca o que a pessoa já escreveu', async () => {
  const w = await montar('<form><input name="field_destino" value="Recife"></form>', { nome: 'Fernando de Noronha' });
  assert.equal(w.document.querySelector('[name="field_destino"]').value, 'Recife');
});

test('avisa o formulário (evento input) e conta os campos preenchidos', async () => {
  const w = await montar('<form><input name="field_destino" value=""></form><form><input name="field_destino" value=""></form>');
  let avisos = 0;
  w.document.addEventListener('input', async () => { avisos++; });
  assert.equal(w.TTDestino.preencher(w.document, 'Tóquio'), 2);
  assert.equal(avisos, 2);
});

test('sem nome ou sem formulário, não faz nada e não dá erro', async () => {
  const w = await montar('<p>sem formulário</p>', { nome: 'Sardenha' });
  assert.equal(w.TTDestino.preencher(w.document, ''), 0);
  assert.equal((await montar('<form><input name="field_destino" value=""></form>')).document.querySelector('[name="field_destino"]').value, '');
});
