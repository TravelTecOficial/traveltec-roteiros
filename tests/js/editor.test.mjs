// Comportamento da tela de edição do roteiro — assets/roteiro-editor.js (jsdom, sem WordPress)
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';

const AQUI = path.dirname(fileURLToPath(import.meta.url));
const CSS = fs.readFileSync(path.resolve(AQUI, '../../traveltec-roteiros/assets/roteiro-editor.css'), 'utf8');
const JS = fs.readFileSync(path.resolve(AQUI, '../../traveltec-roteiros/assets/roteiro-editor.js'), 'utf8');

const FOTOS = [{ url: 'https://x.test/a.jpg', alt: '' }, { url: 'https://x.test/b.jpg', alt: 'Praia' }, { url: 'https://x.test/c.jpg', alt: '' }];

// HTML no formato que includes/roteiro-editor.php produz (só o que o JS usa)
function pagina({ modo = 'campos', fotos = FOTOS } = {}) {
  const abas = ['resumo', 'preco', 'fotos', 'inclui']
    .map((id, i) => `<button type="button" data-tt-aba="${id}" class="tt-rot__aba${i === 0 ? ' is-ativa' : ''}" aria-selected="${i === 0}">${id}</button>`).join('');
  const paineis = ['resumo', 'preco', 'fotos', 'inclui']
    .map((id, i) => `<div data-tt-painel="${id}"${i === 0 ? '' : ' hidden'}>${
      id === 'resumo' ? '<textarea id="tt_rot_texto_de_apresentacao" data-tt-editor></textarea>' :
      id === 'inclui' ? '<textarea id="tt_rot_inclui" data-tt-editor></textarea><textarea id="tt_rot_nao_inclui" data-tt-editor></textarea>' :
      id === 'preco' ? `
        <div data-tt-preco>
          <input type="hidden" name="tt_preco[alterado]" value="0" data-tt-alterado>
          <input type="hidden" name="tt_preco[modo]" value="${modo}" data-tt-preco-modo>
          <div data-tt-modo-campos${modo === 'livre' ? ' hidden' : ''}>
            <input name="tt_preco[prefixo]" value="A partir de">
            <select name="tt_preco[moeda]"><option value="USD" selected>USD</option><option value="EUR">EUR</option><option value="BRL">BRL</option></select>
            <input name="tt_preco[valor]" value="1.044">
            <input name="tt_preco[complemento]" value="por pessoa em duplo">
          </div>
          <div data-tt-modo-livre${modo === 'livre' ? '' : ' hidden'}><input name="tt_preco[livre]" value="Sob consulta"></div>
          <strong data-tt-previa></strong>
          <button type="button" data-tt-trocar-modo>Usar texto livre</button>
        </div>` :
      `<div data-tt-fotos data-urls='${JSON.stringify(fotos)}'>
          <input type="hidden" name="tt_galeria[alterado]" value="0" data-tt-alterado>
          <input type="hidden" name="tt_galeria[urls]" value="" data-tt-fotos-json>
          <ul data-tt-lista></ul>
          <button type="button" data-tt-add-fotos>Adicionar</button><span data-tt-contagem></span>
        </div>`}</div>`).join('');
  return `<div data-tt-rot><div role="tablist">${abas}</div>${paineis}</div>`;
}

function montar(opcoes = {}, extra = {}) {
  const dom = new JSDOM(`<!doctype html><body>${pagina(opcoes)}</body>`, { runScripts: 'outside-only', pretendToBeVisual: true });
  const janela = dom.window;
  janela.__iniciados = [];
  janela.wp = extra.wp === undefined ? {
    editor: { initialize: (id, cfg) => janela.__iniciados.push({ id, cfg }) },
    media: null,
  } : extra.wp;
  janela.eval(JS);
  // o script liga sozinho no DOMContentLoaded; nos testes chamamos na hora (iniciar é idempotente)
  janela.TTRoteiroEditor.iniciar(janela.document.querySelector('[data-tt-rot]'));
  return janela;
}

const valorDe = (j, nome) => j.document.querySelector(`[name="${nome}"]`).value;
const clicar = (el) => el.dispatchEvent(new el.ownerDocument.defaultView.Event('click', { bubbles: true }));
const digitar = (el, v) => { el.value = v; el.dispatchEvent(new el.ownerDocument.defaultView.Event('input', { bubbles: true })); };

test('abas: só o painel da aba ativa fica visível e o editor da aba aberta inicia uma vez', () => {
  const j = montar();
  const paineis = [...j.document.querySelectorAll('[data-tt-painel]')];
  assert.deepEqual(paineis.map((p) => p.hidden), [false, true, true, true]);
  assert.deepEqual(j.__iniciados.map((x) => x.id), ['tt_rot_texto_de_apresentacao'], 'editor da aba inicial inicia na abertura');
  const botaoInclui = j.document.querySelector('[data-tt-aba="inclui"]');
  clicar(botaoInclui);
  assert.deepEqual(paineis.map((p) => p.hidden), [true, true, true, false]);
  assert.deepEqual(j.__iniciados.map((x) => x.id), ['tt_rot_texto_de_apresentacao', 'tt_rot_inclui', 'tt_rot_nao_inclui']);
  clicar(j.document.querySelector('[data-tt-aba="resumo"]'));
  clicar(botaoInclui);
  assert.equal(j.__iniciados.length, 3, 'reabrir a aba não reinicia o editor');
  assert.equal(botaoInclui.getAttribute('aria-selected'), 'true');
});

test('abas: setas do teclado trocam de aba', () => {
  const j = montar();
  const primeira = j.document.querySelector('[data-tt-aba="resumo"]');
  primeira.dispatchEvent(new j.KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));
  assert.equal(j.document.querySelector('[data-tt-aba="preco"]').classList.contains('is-ativa'), true);
  j.document.querySelector('[data-tt-aba="preco"]').dispatchEvent(new j.KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));
  assert.equal(j.document.querySelector('[data-tt-aba="resumo"]').classList.contains('is-ativa'), true);
});

test('editor: usa barra enxuta, sem botão de mídia, com a aba Texto', () => {
  const j = montar();
  const cfg = j.__iniciados[0].cfg;
  assert.equal(cfg.mediaButtons, false);
  assert.equal(cfg.quicktags, true);
  assert.match(cfg.tinymce.toolbar1, /bullist,numlist/);
  assert.equal(cfg.tinymce.wpautop, false, 'wpautop ligado faria o TinyMCE tirar os <p> ao salvar (o site mostra o campo sem autop)');
});

test('sem wp.editor (falha ao carregar) a tela não quebra', () => {
  const j = montar({}, { wp: undefined === 1 ? null : {} });
  assert.equal(j.__iniciados.length, 0);
  assert.equal(j.document.querySelector('[data-tt-painel="resumo"]').hidden, false);
});

test('preço: "alterado" só liga depois de mexer; prévia acompanha os campos', () => {
  const j = montar();
  assert.equal(valorDe(j, 'tt_preco[alterado]'), '0');
  assert.equal(j.document.querySelector('[data-tt-previa]').textContent, 'A partir de USD 1.044, por pessoa em duplo');
  digitar(j.document.querySelector('[name="tt_preco[valor]"]'), '2.500');
  assert.equal(valorDe(j, 'tt_preco[alterado]'), '1');
  assert.equal(j.document.querySelector('[data-tt-previa]').textContent, 'A partir de USD 2.500, por pessoa em duplo');
  const moeda = j.document.querySelector('[name="tt_preco[moeda]"]');
  moeda.value = 'BRL';
  moeda.dispatchEvent(new j.Event('change', { bubbles: true }));
  assert.equal(j.document.querySelector('[data-tt-previa]').textContent, 'A partir de R$ 2.500, por pessoa em duplo');
});

test('preço: trocar para texto livre esconde os campos e liga "alterado"', () => {
  const j = montar();
  clicar(j.document.querySelector('[data-tt-trocar-modo]'));
  assert.equal(valorDe(j, 'tt_preco[modo]'), 'livre');
  assert.equal(j.document.querySelector('[data-tt-modo-campos]').hidden, true);
  assert.equal(j.document.querySelector('[data-tt-modo-livre]').hidden, false);
  assert.equal(valorDe(j, 'tt_preco[alterado]'), '1');
  assert.equal(j.document.querySelector('[data-tt-previa]').textContent, 'Sob consulta');
  clicar(j.document.querySelector('[data-tt-trocar-modo]'));
  assert.equal(valorDe(j, 'tt_preco[modo]'), 'campos');
});

test('preço: abrir em modo livre mostra o texto e não marca "alterado"', () => {
  const j = montar({ modo: 'livre' });
  assert.equal(valorDe(j, 'tt_preco[alterado]'), '0');
  assert.equal(j.document.querySelector('[data-tt-previa]').textContent, 'Sob consulta');
});

test('fotos: abre com a lista, contagem e JSON, sem marcar "alterado"', () => {
  const j = montar();
  assert.equal(j.document.querySelectorAll('.tt-rot__foto').length, 3);
  assert.equal(j.document.querySelector('[data-tt-contagem]').textContent, '3 fotos');
  assert.deepEqual(JSON.parse(valorDe(j, 'tt_galeria[urls]')), FOTOS);
  assert.equal(valorDe(j, 'tt_galeria[alterado]'), '0');
});

test('fotos: mover e remover atualizam a lista, o JSON e ligam "alterado"', () => {
  const j = montar();
  const acoes = (i) => j.document.querySelectorAll('.tt-rot__foto')[i].querySelectorAll('button');
  assert.equal(acoes(0)[0].disabled, true, 'a primeira não pode subir');
  assert.equal(acoes(2)[1].disabled, true, 'a última não pode descer');
  clicar(acoes(0)[1]); // primeira -> depois
  assert.deepEqual(JSON.parse(valorDe(j, 'tt_galeria[urls]')).map((f) => f.url), ['https://x.test/b.jpg', 'https://x.test/a.jpg', 'https://x.test/c.jpg']);
  assert.equal(valorDe(j, 'tt_galeria[alterado]'), '1');
  clicar(acoes(1)[2]); // remove a que agora é a segunda (a.jpg)
  assert.deepEqual(JSON.parse(valorDe(j, 'tt_galeria[urls]')).map((f) => f.url), ['https://x.test/b.jpg', 'https://x.test/c.jpg']);
  assert.equal(j.document.querySelector('[data-tt-contagem]').textContent, '2 fotos');
});

test('fotos: sem nenhuma foto a lista fica vazia e a contagem diz 0 fotos', () => {
  const j = montar({ fotos: [] });
  assert.equal(j.document.querySelectorAll('.tt-rot__foto').length, 0);
  assert.equal(j.document.querySelector('[data-tt-contagem]').textContent, '0 fotos');
  assert.equal(valorDe(j, 'tt_galeria[urls]'), '[]');
});

test('fotos: Biblioteca de Mídia adiciona (tamanho large, senão o original) e liga "alterado"', () => {
  let aoEscolher;
  const escolhidas = [
    { toJSON: () => ({ id: 41, url: 'https://x.test/orig1.jpg', alt: 'Um', sizes: { large: { url: 'https://x.test/large1.jpg' } } }) },
    { toJSON: () => ({ url: 'https://x.test/orig2.jpg', alt: '' }) },
  ];
  const quadro = { on: (evento, fn) => { if (evento === 'select') { aoEscolher = fn; } }, open: () => {}, state: () => ({ get: () => ({ each: (fn) => escolhidas.forEach(fn) }) }) };
  const j = montar({ fotos: [] }, { wp: { editor: { initialize() {} }, media: () => quadro } });
  clicar(j.document.querySelector('[data-tt-add-fotos]'));
  aoEscolher();
  assert.deepEqual(JSON.parse(valorDe(j, 'tt_galeria[urls]')), [{ id: 41, url: 'https://x.test/large1.jpg', alt: 'Um' }, { id: 0, url: 'https://x.test/orig2.jpg', alt: '' }]);
  assert.equal(valorDe(j, 'tt_galeria[alterado]'), '1');
  assert.equal(j.document.querySelectorAll('.tt-rot__foto').length, 2);
});

test('fotos: sem wp.media o botão não faz nada nem quebra', () => {
  const j = montar({}, { wp: {} });
  clicar(j.document.querySelector('[data-tt-add-fotos]'));
  assert.equal(valorDe(j, 'tt_galeria[alterado]'), '0');
});

test('css: [hidden] dentro da caixa vence qualquer display do autor (senão a grade do preço aparece no modo texto livre)', () => {
  // O jsdom não reproduz a cascata por origem (folha do navegador x do autor), então a regra é conferida no texto do CSS:
  // sem `!important`, `.tt-rot__grade { display: grid }` ganha de `[hidden]` e o bloco escondido continua na tela.
  assert.match(CSS, /\.tt-rot\s+\[hidden\]\s*\{[^}]*display:\s*none\s*!important/);
});

/* ---------------------------------------------------------------- destino: lista de itens, contador, foto com ID */
function paginaDestino() {
  return `<div class="tt-rot tt-dest" data-tt-rot>
    <button type="button" data-tt-aba="resumo" class="is-ativa"></button>
    <div data-tt-painel="resumo"><div class="tt-rot__campo"><textarea data-tt-contador="160">Ilha</textarea><span data-tt-contador-saida></span></div></div>
    <div data-tt-itens data-nome="tt_destinos[o_que_fazer][]">
      <ul data-tt-itens-lista><li><input type="text" name="tt_destinos[o_que_fazer][]" value="A"></li><li><input type="text" name="tt_destinos[o_que_fazer][]" value="B"></li><li><input type="text" name="tt_destinos[o_que_fazer][]" value="C"></li></ul>
      <button type="button" data-tt-add-item>Adicionar item</button>
    </div>
    <div data-tt-fotos data-urls='[{"id":127,"url":"https://x.test/n1.jpg","alt":""}]' data-titulo="Escolher fotos do destino" data-botao="Adicionar ao destino">
      <input type="hidden" data-tt-alterado><input type="hidden" name="tt_destinos[galeria]" data-tt-fotos-json>
      <ul data-tt-lista></ul><button type="button" data-tt-add-fotos>Adicionar</button><span data-tt-contagem></span>
    </div></div>`;
}

function montarDestino() {
  const dom = new JSDOM(`<!doctype html><body><form>${paginaDestino()}</form></body>`, { runScripts: 'outside-only', pretendToBeVisual: true });
  const w = dom.window;
  w.__midia = null;
  w.wp = {
    media(opcoes) {
      w.__midia = { opcoes, on(ev, cb) { this.cb = cb; }, open() {}, state: () => ({ get: () => ({ each: (f) => f({ toJSON: () => ({ id: 128, url: 'https://x.test/n2.jpg', alt: 'Baía' }) }) }) }) };
      return w.__midia;
    }
  };
  w.eval(JS);
  // o script liga sozinho no DOMContentLoaded; nos testes chamamos na hora (iniciar é idempotente)
  w.TTRoteiroEditor.iniciar(w.document.querySelector('[data-tt-rot]'));
  const valores = () => [...w.document.querySelectorAll('[data-tt-itens-lista] input')].map((i) => i.value);
  return { w, doc: w.document, valores };
}

test('destino: cada item ganha subir, descer e remover; as pontas ficam travadas', () => {
  const { doc } = montarDestino();
  const itens = doc.querySelectorAll('[data-tt-itens-lista] li');
  assert.equal(itens[0].querySelectorAll('button').length, 3);
  assert.equal(itens[0].querySelector('[data-tt-sobe]').disabled, true);
  assert.equal(itens[2].querySelector('[data-tt-desce]').disabled, true);
  assert.equal(itens[1].querySelector('[data-tt-sobe]').disabled, false);
});

test('destino: mover e remover itens muda a ordem dos campos', () => {
  const { doc, valores } = montarDestino();
  doc.querySelectorAll('[data-tt-itens-lista] li')[2].querySelector('[data-tt-sobe]').click();
  assert.deepEqual(valores(), ['A', 'C', 'B']);
  doc.querySelectorAll('[data-tt-itens-lista] li')[0].querySelector('[data-tt-remove]').click();
  assert.deepEqual(valores(), ['C', 'B']);
  assert.equal(doc.querySelectorAll('[data-tt-itens-lista] li')[0].querySelector('[data-tt-sobe]').disabled, true);
});

test('destino: "Adicionar item" cria um campo vazio com o nome certo e põe o cursor nele', () => {
  const { doc, valores } = montarDestino();
  doc.querySelector('[data-tt-add-item]').click();
  assert.deepEqual(valores(), ['A', 'B', 'C', '']);
  const novo = doc.querySelectorAll('[data-tt-itens-lista] input')[3];
  assert.equal(novo.name, 'tt_destinos[o_que_fazer][]');
  assert.equal(doc.activeElement, novo);
});

test('destino: Enter num item não envia o formulário e abre o item seguinte', () => {
  const { w, doc, valores } = montarDestino();
  let enviou = false;
  doc.querySelector('form').addEventListener('submit', () => { enviou = true; });
  const primeiro = doc.querySelectorAll('[data-tt-itens-lista] input')[0];
  const tecla = new w.KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true });
  primeiro.dispatchEvent(tecla);
  assert.equal(tecla.defaultPrevented, true);
  assert.equal(enviou, false);
  assert.deepEqual(valores(), ['A', '', 'B', 'C']);
});

test('destino: contador do resumo acompanha a digitação', () => {
  const { w, doc } = montarDestino();
  const saida = doc.querySelector('[data-tt-contador-saida]');
  assert.equal(saida.textContent, '4 de 160 caracteres');
  const area = doc.querySelector('[data-tt-contador]');
  area.value = 'x'.repeat(170);
  area.dispatchEvent(new w.Event('input', { bubbles: true }));
  assert.equal(saida.textContent, '170 de 160 caracteres');
  assert.equal(saida.classList.contains('is-acima'), true);
});

test('destino: foto escolhida na Biblioteca entra com o ID; a janela usa os textos do destino', () => {
  const { w, doc } = montarDestino();
  doc.querySelector('[data-tt-add-fotos]').click();
  assert.equal(w.__midia.opcoes.title, 'Escolher fotos do destino');
  assert.equal(w.__midia.opcoes.button.text, 'Adicionar ao destino');
  w.__midia.cb();
  const gravado = JSON.parse(doc.querySelector('[data-tt-fotos-json]').value);
  assert.deepEqual(gravado.map((f) => f.id), [127, 128]);
});
