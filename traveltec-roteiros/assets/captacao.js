/**
 * Voucher Tec — captura de origem (UTMs / ids de clique) e envio dos formulários ao n8n.
 * Carregado pelo plugin em todas as páginas; a configuração vem em window.TT_VOUCHER_CAPTACAO
 * ({ webhook, company_id, forms: { "<id do formulário>": "cotacao" | "contato" | "newsletter" } }).
 *
 * - Captura na entrada: utm_*, gclid, gbraid, wbraid, fbclid, msclkid, ttclid + referrer e página de entrada.
 *   Guarda em localStorage por 90 dias (sobrevive a outra aba / outro dia). Uma visita nova com qualquer
 *   parâmetro de campanha substitui o conjunto inteiro (não mistura gclid antigo com utm novo).
 *   O primeiro contato (first_*) nunca é sobrescrito.
 * - Envio: depois do sucesso do JetFormBuilder (envio por AJAX), POST para o recebe-forms no mesmo formato
 *   das LPs (nome, email, telefone, mensagem, company_id, source, page_url, timestamp, external_id, UTMs...).
 *   Vai como application/x-www-form-urlencoded + keepalive: sem preflight de CORS e sobrevive ao redirecionamento.
 */
(function () {
  'use strict';

  var CFG = window.TT_VOUCHER_CAPTACAO || {};
  var WEBHOOK = CFG.webhook || 'https://exec.traveltec.com.br/webhook/recebe-forms';
  var COMPANY_ID = CFG.company_id || '';
  var IDS = CFG.forms || {};
  var CHAVES = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id',
    'gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'ttclid'];
  var LS = 'rt_origem';
  var LS_ID = 'rt_external_id';
  var VALIDADE = 90 * 24 * 3600 * 1000;

  // Formulários do plugin (por papel): campos de cada um → chaves do recebe-forms.
  var FORMS = {
    'cotacao': { source: 'site-cotacao', tipo: 'Cotação', nome: 'field_seu_nome', email: 'field_e_mail', telefone: 'field_celular',
      extras: {
        destino: 'field_destino', data_embarque: 'data_de_embarque', data_retorno: 'data_de_retorno',
        mes_viagem: 'informe_o_mes_de_sua_viagem', duracao: 'duracao_da_viagem', adultos: 'qtde_de_adultos',
        criancas: 'qtde_de_criancas', bebes: 'qtde_de_bebes', motivo: 'field_Motivo_viagem',
        lugares: 'field_lugares', experiencia: 'field_experiencia_desejada', orcamento: 'field_orcamento',
        observacoes: 'suas_observacoes', nascimento: 'field_nascimento', roteiro: 'post_title'
      } },
    'contato': { source: 'site-contato', tipo: 'Contato', nome: 'nome', email: 'email', telefone: 'celular', mensagem: 'mensagem' },
    'newsletter': { source: 'site-newsletter', tipo: 'Newsletter', nome: 'nome', email: 'email', mensagemFixa: 'Inscrição na newsletter' },
    'newsletter-blog': { source: 'site-newsletter-blog', tipo: 'Newsletter (blog)', nome: 'nome_blog', email: 'email_blog', mensagemFixa: 'Inscrição na newsletter' }
  };
  var ROTULOS = {
    destino: 'Destino', data_embarque: 'Embarque', data_retorno: 'Retorno', mes_viagem: 'Mês da viagem',
    duracao: 'Duração', adultos: 'Adultos', criancas: 'Crianças', bebes: 'Bebês', motivo: 'Motivo',
    lugares: 'Lugares desejados', experiencia: 'Experiência', orcamento: 'Orçamento por pessoa (US$)',
    observacoes: 'Observações', nascimento: 'Nascimento', roteiro: 'Página'
  };

  function ler() {
    try { return JSON.parse(localStorage.getItem(LS)) || {}; } catch (e) { return {}; }
  }
  function gravar(o) {
    try { localStorage.setItem(LS, JSON.stringify(o)); } catch (e) {}
  }
  function cookie(nome) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + nome + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
  }
  function externalId() {
    try {
      var id = localStorage.getItem(LS_ID);
      if (!id) {
        id = 'site_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
        localStorage.setItem(LS_ID, id);
      }
      return id;
    } catch (e) { return 'site_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10); }
  }
  function refExterno() {
    var r = document.referrer || '';
    try { return r && new URL(r).hostname !== location.hostname ? r : ''; } catch (e) { return ''; }
  }

  // 1. Captura na entrada
  function capturar() {
    var p = new URLSearchParams(location.search);
    var o = ler();
    var agora = Date.now();
    if (o.ts && agora - o.ts > VALIDADE) {
      // expirou: mantém só o primeiro contato
      o = { first_source: o.first_source, first_landing: o.first_landing, first_ts: o.first_ts };
    }
    var novos = {};
    var tem = false;
    CHAVES.forEach(function (k) {
      var v = p.get(k);
      if (v) { novos[k] = v.slice(0, 300); tem = true; }
    });
    var ref = refExterno();
    if (tem || (ref && !o.ts)) {
      CHAVES.forEach(function (k) { delete o[k]; });
      Object.keys(novos).forEach(function (k) { o[k] = novos[k]; });
      o.referrer = ref;
      o.landing_page = location.href.slice(0, 500);
      o.ts = agora;
    } else if (!o.ts) {
      o.referrer = '';
      o.landing_page = location.href.slice(0, 500);
      o.ts = agora;
    }
    if (!o.first_ts) {
      o.first_source = novos.utm_source || (novos.gclid || novos.gbraid || novos.wbraid ? 'google' : '') ||
        (novos.fbclid ? 'facebook' : '') || (ref ? new URL(ref).hostname : 'direto');
      o.first_landing = location.href.slice(0, 500);
      o.first_ts = agora;
    }
    gravar(o);
  }

  function origem() {
    var o = ler();
    var d = {};
    CHAVES.concat(['referrer', 'landing_page', 'first_source', 'first_landing']).forEach(function (k) {
      if (o[k]) d[k] = o[k];
    });
    var ga = cookie('_ga').match(/^GA\d\.\d\.(.+)$/);
    if (ga) d.client_id = ga[1];
    var fbp = cookie('_fbp');
    if (fbp) d.fbp = fbp;
    var fbc = cookie('_fbc') || (o.fbclid ? 'fb.1.' + (o.ts || Date.now()) + '.' + o.fbclid : '');
    if (fbc) d.fbc = fbc;
    // Cookies de anúncio/analytics, quando existirem (valor bruto — o tratamento fica no n8n).
    var brutos = { gcl_aw: '_gcl_aw', gcl_au: '_gcl_au', gcl_gb: '_gcl_gb', gcl_dc: '_gcl_dc', ttp: '_ttp',
      uet_msclkid: '_uetmsclkid', li_fat_id: 'li_fat_id' };
    Object.keys(brutos).forEach(function (k) { var c = cookie(brutos[k]); if (c) d[k] = c; });
    var sessao = document.cookie.match(/(?:^|; )(_ga_[A-Z0-9]+)=([^;]*)/);
    if (sessao) { d.ga_session_cookie = sessao[1]; d.ga_session = decodeURIComponent(sessao[2]); }
    if (o.ts) d.origem_em = new Date(o.ts).toISOString();
    if (o.first_ts) d.first_em = new Date(o.first_ts).toISOString();
    return d;
  }

  function rotulo(form, nome) {
    var el = form.querySelector('[name="' + nome + '"], [name="' + nome + '[]"]');
    var linha = el && el.closest('.jet-form-builder-row');
    var r = linha && linha.querySelector('.jet-form-builder__label-text');
    return (r ? r.textContent : nome).replace(/\s+/g, ' ').replace(/[\s*]+$/, '').trim() || nome;
  }

  function dispositivo() {
    var ua = navigator.userAgent || '';
    if (/iPad|Tablet/i.test(ua) || (/Android/i.test(ua) && !/Mobile/i.test(ua))) return 'tablet';
    return /Mobi|iPhone|Android/i.test(ua) ? 'mobile' : 'desktop';
  }

  // 2. Envio
  var fotos = new WeakMap();

  function valores(form) {
    var v = {};
    new FormData(form).forEach(function (valor, chave) {
      if (typeof valor !== 'string') return;
      chave = chave.replace(/\[\]$/, '');
      v[chave] = v[chave] ? v[chave] + ', ' + valor : valor;
    });
    return v;
  }

  function montar(form, v) {
    var id = String(form.getAttribute('data-form-id') || '');
    var m = FORMS[IDS[id]] || { source: 'site-form-' + id, nome: 'nome', email: 'email', telefone: 'telefone', mensagem: 'mensagem' };
    var d = {
      nome: m.nome ? (v[m.nome] || '').trim() : '',
      email: (v[m.email] || '').trim(),
      telefone: m.telefone ? String(v[m.telefone] || '').replace(/\D/g, '') : '',
      mensagem: m.mensagem ? (v[m.mensagem] || '').trim() : (m.mensagemFixa || ''),
      company_id: COMPANY_ID,
      source: m.source,
      tipo_formulario: m.tipo || ('Formulário ' + id),
      form_id: id,
      page_url: location.href,
      timestamp: new Date().toISOString(),
      external_id: externalId()
    };
    if (m.extras) {
      var linhas = [];
      Object.keys(m.extras).forEach(function (k) {
        var val = (v[m.extras[k]] || '').trim();
        if (!val || val === 'Selecione') return;
        d[k] = val;
        if (k !== 'roteiro') linhas.push(ROTULOS[k] + ': ' + val);
      });
      d.mensagem = linhas.join('\n');
    }
    // Tipo do formulário visível no texto de conversão do CRM.
    d.mensagem = '[' + d.tipo_formulario + '] ' + d.mensagem;
    if (d.telefone) {
      d.telefone_digitado = String(v[m.telefone] || '').trim();
      d.telefone_e164 = /^55\d{10,11}$/.test(d.telefone) ? d.telefone
        : (/^\d{10,11}$/.test(d.telefone) ? '55' + d.telefone : d.telefone);
    }
    // Todos os campos preenchidos: com o nome original (campo_*) e em JSON com o rótulo da tela.
    var campos = {};
    Object.keys(v).forEach(function (k) {
      var val = String(v[k] || '').trim();
      if (!val || k.charAt(0) === '_' || val === 'Selecione') return;
      d['campo_' + k] = val;
      campos[rotulo(form, k)] = val;
    });
    d.campos_json = JSON.stringify(campos);
    d.page_title = document.title;
    d.page_path = location.pathname;
    d.dispositivo = dispositivo();
    d.user_agent = navigator.userAgent;
    d.idioma = navigator.language || '';
    d.tela = screen.width + 'x' + screen.height;
    d.janela = window.innerWidth + 'x' + window.innerHeight;
    try { d.fuso = Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (e) {}
    var o = origem();
    Object.keys(o).forEach(function (k) { d[k] = o[k]; });
    return d;
  }

  function enviar(form) {
    var v = fotos.get(form) || valores(form);
    var d = montar(form, v);
    // Newsletter vai sem telefone: o tratamento de lead só com e-mail fica no n8n.
    if (!d.email && !d.telefone) return;
    if (!COMPANY_ID) {
      // Sem company_id o n8n não sabe de qual agência é o lead: preencher em Voucher Tec › Dados da agência.
      if (window.console) console.warn('[Voucher Tec] company_id vazio — lead não enviado ao webhook.');
      return;
    }
    var corpo = new URLSearchParams();
    Object.keys(d).forEach(function (k) { if (d[k] !== '' && d[k] != null) corpo.append(k, d[k]); });
    try {
      fetch(WEBHOOK, { method: 'POST', body: corpo, keepalive: true, mode: 'no-cors' });
    } catch (e) {
      if (navigator.sendBeacon) navigator.sendBeacon(WEBHOOK, corpo);
    }
    (window.dataLayer = window.dataLayer || []).push(Object.assign({
      event: 'generate_lead', company_id: COMPANY_ID, source: d.source, form_id: d.form_id,
      page_url: d.page_url, timestamp: d.timestamp
    }, origem()));
  }

  // Guarda os valores no momento do envio (o JetFormBuilder pode limpar o formulário no sucesso).
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && f.classList && f.classList.contains('jet-form-builder')) fotos.set(f, valores(f));
  }, true);

  function ligar() {
    if (!window.jQuery) return;
    window.jQuery(document).on('jet-form-builder/ajax/on-success', function (e, resposta, form) {
      var el = form && form.jquery ? form[0] : form;
      if (el && el.tagName === 'FORM') enviar(el);
    });
  }

  capturar();
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ligar);
  else ligar();
  // Conferência no console: RTOrigem.previa(document.querySelector('form.jet-form-builder')) mostra o que seria enviado.
  window.RTOrigem = { dados: origem, previa: function (f) { return montar(f, valores(f)); } };
})();
