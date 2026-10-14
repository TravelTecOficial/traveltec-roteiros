// Modelos do Elementor dos destinos — gerados por build_v2.py em traveltec-roteiros/templates/v2/
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const V2 = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../traveltec-roteiros/templates/v2');
const ler = (nome) => fs.readFileSync(path.join(V2, `${nome}.json`), 'utf8');
const CHAVES = ['destino-card', 'destinos-lista', 'destino-single'];

function widgets(no, saida = []) {
  if (Array.isArray(no)) no.forEach((x) => widgets(x, saida));
  else if (no && typeof no === 'object') {
    if (no.elType === 'widget') saida.push(no);
    widgets(no.elements || [], saida);
  }
  return saida;
}

test('os três modelos estão no índice e têm tipo e condição certos', () => {
  const indice = JSON.parse(ler('_indice'));
  for (const chave of CHAVES) assert.ok(indice.docs.some((d) => d.chave === chave), `índice: ${chave}`);
  assert.ok(indice.imagens.includes('banner-destinos.jpg'), 'o banner da lista entra nas imagens instaladas');
  const [card, lista, single] = CHAVES.map((c) => JSON.parse(ler(c)));
  assert.equal(card.template_type, 'loop-item');
  assert.equal(lista.template_type, 'archive');
  assert.deepEqual(lista.condicoes, ['include/archive/destinos_archive']);
  assert.equal(single.template_type, 'single-page');
  assert.deepEqual(single.condicoes, ['include/singular/destinos']);
  for (const doc of [card, lista, single]) assert.equal(doc.tipo, 'elementor_library');
});

test('nada do site-modelo sobra nos modelos', () => {
  for (const chave of CHAVES) {
    const txt = ler(chave);
    assert.doesNotMatch(txt, /yellowgreen|hostingersite/i, `${chave}: endereço do site-modelo`);
    assert.doesNotMatch(txt, /globals\/(colors|typography)/, `${chave}: cor ou fonte global do Kit do site-modelo`);
    assert.doesNotMatch(txt, /__globals__/, `${chave}: referência a globais`);
    assert.doesNotMatch(txt, /form_id=\\?"99\\?"/, `${chave}: formulário do site-modelo`);
    assert.doesNotMatch(txt, /tt_whatsapp_link/, `${chave}: shortcode que só existe no site-modelo`);
    assert.doesNotMatch(txt, /Zizi|Noronha/i, `${chave}: conteúdo de exemplo`);
  }
});

test('lista: grade com o card do plugin, só destinos publicados, sem o filtro da página antiga', () => {
  const lista = JSON.parse(ler('destinos-lista'));
  const grade = widgets(lista.data).find((w) => w.widgetType === 'loop-grid');
  assert.equal(grade.settings.template_id, '{{DOC:destino-card}}');
  assert.equal(grade.settings.post_query_post_type, 'destinos');
  assert.deepEqual(grade.settings.post_query_post_status, ['publish']);
  // no site-modelo a grade usava a consulta 'tt_destinos' (páginas-filhas de Destinos): com ela a lista sai vazia
  assert.equal('post_query_query_id' in grade.settings, false);
  assert.match(ler('destinos-lista'), /\{\{IMG:banner-destinos\.jpg\}\}/);
  assert.match(ler('destinos-lista'), /https:\/\/tt\.token\/url_cotacao/);
  assert.match(ler('destinos-lista'), /https:\/\/wa\.me\/%tt:whatsapp_digitos%/);
});

test('card: sem data nem autor (isso é de post de blog) e com a subchamada logo abaixo do nome', () => {
  const card = JSON.parse(ler('destino-card'));
  const ws = widgets(card.data);
  assert.equal(ws.some((w) => w.widgetType === 'post-info'), false);
  const i = ws.findIndex((w) => w.widgetType === 'theme-post-title');
  assert.equal(ws[i + 1].widgetType, 'shortcode');
  assert.equal(ws[i + 1].settings.shortcode, '[tt_destino_subchamada estilo="card"]');
});

test('página do destino (v2): hero, as seções da spec por shortcode e o formulário no fim', () => {
  const single = JSON.parse(ler('destino-single'));
  const ws = widgets(single.data);
  const codigos = ws.filter((w) => w.widgetType === 'shortcode').map((w) => w.settings.shortcode);
  assert.deepEqual(codigos, [
    '[tt_destino_subchamada estilo="hero"]',
    '[tt_destino_visao_geral titulo="Visão geral"]',
    '[tt_destino_praticas titulo="Informações práticas"]',
    '[tt_destino_o_que_fazer titulo="O que fazer"]',
    '[tt_destino_onde_ficar titulo="Onde ficar"]',
    '[tt_destino_galeria titulo="Galeria"]',
    '[tt_destino_faq titulo="Perguntas frequentes"]',
    '[tt_destino_atualizado]',
    '[tt_destino_form titulo="Solicite sua cotação para este destino"]',
  ]);
  assert.equal(ws.some((w) => w.widgetType === 'theme-post-content'), false, 'o conteúdo do post não é usado');
  // hero: nome (h1), subchamada e botão — com a foto de capa de fundo
  const hero = single.data[0];
  assert.match(hero.settings.__dynamic__.background_image, /name="post-featured-image"/);
  assert.deepEqual(hero.elements.map((e) => e.widgetType), ['theme-post-title', 'shortcode', 'button']);
  const botao = hero.elements[2];
  assert.equal(botao.settings.text, 'Solicite sua cotação');
  assert.equal(botao.settings.link.url, '#tt-destino-cotacao');
  assert.equal('__dynamic__' in botao.settings, false);
  assert.equal(ws.filter((w) => w.widgetType === 'button').length, 1, 'um botão só (o do hero)');
  const corpo = single.data.find((c) => (c.elements || []).some((e) => e.widgetType === 'shortcode' && e.settings.shortcode.includes('tt_destino_visao_geral')));
  assert.equal(corpo.settings.flex_gap.size, 40, 'as seções do corpo têm espaço entre si');
  assert.equal(corpo.settings.flex_direction, 'column');
  assert.match(ler('destino-single'), /"_element_id": "tt-destino-cotacao"/);
  const ids = ws.map((w) => w.id);
  assert.equal(new Set(ids).size, ids.length, 'ids dos widgets não se repetem');
});
