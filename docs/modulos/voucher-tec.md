# Módulo: Voucher Tec 2.0 (site padrão das agências)

## Objetivo

O plugin `traveltec-roteiros` deixa de instalar só os roteiros e passa a levar o site inteiro no layout da
Rede Turística. O MCP conecta, aplica os dados do Cliente Ideal e faz só os ajustes finos. Nome exibido:
**Voucher Tec - Travel Tec**, pedido pelo dono em 24/09/2026. O slug fica igual para a atualização automática.

## Etapas

- [x] Exportação da Rede (14 documentos + 3 formulários) em `src/rede/` (24/09/2026)
- [x] `build_v2.py`: marcadores de dados/IDs/imagens; nenhum dado da Rede sobra nos modelos
- [x] PHP: dados (`includes/dados.php`), estilo, instalador, captação, API REST, painel — sintaxe conferida (PHP 8.2)
- [x] `dist/traveltec-roteiros.zip` 2.0.0 gerado
- [x] 1º teste (24/09/2026) em `mediumslateblue-hawk-933841.hostingersite.com`, um clone da Rede
  (`config.local.json` → `VoucherTecTeste`), com os dados da Mergulhando na Viagem. Instalação, marcadores,
  logo, paleta e captação ligada: OK. Corrigido na 2.0.1: logo do rodapé, copyright, menus, @perfil das redes,
  secundária clara, sinopse fora do site.
- [x] **2.0.2 publicada no GitHub** (24/09/2026, release v2.0.2, commit d95ccae): modelos da Rede v2.0 (post #5424),
  logo do cabeçalho limitado. Daqui em diante os sites atualizam pelo painel (Plugins › Atualizar agora).
- [x] 2.0.3: peça reinstalada já sai com a paleta atual do site (antes voltava laranja).
- [x] 2.0.4: popup do menu do celular (#1078 na Rede) incluído — num site limpo o ☰ não abria nada.
- [x] 2.0.5: sem o site padrão instalado (sites da 1.x), o plugin não acrescenta nada às páginas — fontes do
  Google e CSS dos ícones vazios só depois de instalar. Pedido para atualizar os 6 sites no ar sem peso extra.
- [x] 2.0.6: painel em abas (Licença e instruções, Identidade, Roteiros, Experiências, Contato, Cotação, Sobre),
  pedido pelo dono em 24/09/2026. Cada aba: peças, instalar só a parte e os dados dela. Instalar uma parte não
  troca mais o Kit, os menus nem os outros formulários (antes trocava — risco para site no ar).
- [x] 2.0.7: aplicar cores/fontes não mexe no Kit enquanto o cabeçalho do plugin não estiver instalado (site no ar
  com layout antigo mantém as cores globais).
- [x] 2.0.8: links internos por marcador (`https://tt.token/url_cotacao|url_roteiros|url_blog`, e `url_contato`,
  `url_sobre`) resolvidos para a página que existir no site; `liberar_condicoes` trata single/single-post/single-page
  como a mesma disputa. Achado no Ajimex (cotação em /cotacao-de-viagem/, blog em /experiencias/).
- [x] 2.0.9: título do card do blog não herda mais o tamanho de título global do Kit (Ajimex: títulos enormes).
- [x] 2.0.10: posts do blog (com o blog-post do plugin) ganham a classe `tt-topo-transparente` — pedido do dono
  no Ajimex ("faz como nos roteiros"). No Ajimex o cabeçalho é o antigo #48: o CSS do topo transparente foi
  gravado nele (container cc5b294) e a assinatura do rodapé #50 virou uma linha discreta. Backup em
  `Ajimex/backups/elementor-48|50-2026-09-24.json`.
- [x] 2.0.12: atualizador com plano B pela página de releases (a API do GitHub limita 60 consultas/h por IP; no
  Ajimex, na Hostinger, a 2.0.11 não aparecia). Sites presos numa versão anterior: enviar o zip uma vez à mão.
- [x] 2.0.13: fontes da paleta dentro dos documentos do plugin (variáveis globais de tipografia redefinidas só
  neles, `wp_head` #tt-voucher-fontes-docs). No DSelection o Kit tem Cinzel no texto e as páginas novas saíam em maiúsculas.
- [x] 2.0.14: links nos documentos do plugin com `font-family:inherit` (o Kit do DSelection tem Cinzel nos links).
- [x] 2.0.15 (`includes/ajustes.php`), pedido do dono no Zizi Quatti ("se está instalando tem que entrar"; "é marca
  nossa"): (1) `tt_voucher_menus_ligar` — sobre/contato/cotação (e roteiros, se houver roteiro publicado) entram nos
  menus do site com posição; item com o mesmo papel (página/slug/âncora/nome: Quem Somos, Fale Conosco, Cotação de
  Viagem...) é reapontado, o que faltar vai para o fim do menu da posição menu-1; backup em
  `tt_voucher_menus_anteriores`. Roda ao instalar qualquer parte e uma vez por versão. (2)
  `tt_voucher_assinatura_discreta` — assinatura antiga (`tt-assinatura__rule`) vira linha discreta, mantendo cor
  (`--tt-fg`) e filtro do logo; roda na ativação e uma vez por versão (`tt_voucher_ajustes_versao`).
- [x] 2.0.16 (`includes/opcoes.php`), pedido do dono em 25/09/2026, desenho aprovado no chat: (1) aba Cotação —
  moeda do orçamento por pessoa: `tt_voucher_moeda_orcamento` = USD (padrão) | BRL; reescreve o título e as 5
  faixas do campo `field_orcamento` do formulário de cotação instalado (BRL: 10–15 mil, 15–25 mil, 25–35 mil,
  35–50 mil, acima de 50 mil; sem câmbio) e a reinstalação do formulário já sai na moeda escolhida; (2) aba
  Roteiros — `tt_voucher_preco_roteiros` = exibir (padrão) | consulte; filtro `get_post_metadata` devolve
  "Consulte-nos" para `preco_de_referencia` só na exibição (não no admin nem na REST — o n8n e o editor seguem
  vendo o valor); vale para card e página sem reinstalar modelos. Também em `aplicar` (`moeda_orcamento`,
  `preco_roteiros`) e em `status` (`opcoes`). Testes PHP 7.4/8.2 (lógica com stubs do WP) passando; teste no WP
  no ar pendente (abaixo). Site sem o formulário de cotação do plugin (ex.: Rede Turística, #1025 antigo): a
  opção de moeda avisa e vale quando instalar a peça.
- [x] 2.0.16 testada no ar (25/09/2026): Zizi Quatti com orçamento em reais e DSelection com "Consulte-nos" nos 9 roteiros.
- [x] **2.0.17 publicada** (28/09/2026, release v2.0.17, OK do dono): categoria e tags do blog (`category`/`post_tag`)
  ligadas ao tipo `roteiros` (`tt_roteiros_ligar_taxonomias`, também com CPT de outro plugin). O n8n do Cliente Ideal
  aplica `categories`/`tags` ao publicar. Na 2.1.0, Categorias e Tags ficam sob o menu Voucher Tec
  (`tt_voucher_menu_roteiros` leva os itens de taxonomia do submenu antigo).
- [x] **2.1.0 — menu único e editor de roteiros** (pedido do dono em 25/09/2026; spec e plano em `landing-pages/docs/superpowers/`):
  menu **Voucher Tec › Roteiros / Adicionar roteiro / Configuração** (`includes/menu.php`; o menu "Roteiros" solto sai, o de topo
  passa a `edit_posts` e a Configuração exige `manage_options`); tela de edição em 7 abas com editor visual, preço em campos e
  fotos pela Biblioteca de Mídia (`includes/roteiro-editor.php`, `includes/roteiro-dados.php`, `assets/roteiro-editor.js|css`);
  lista com colunas Capa e Preço. Formato gravado igual ao do import; preço e fotos só regravam com `alterado`. Testes em
  `tests/` (`cd tests && npm install && npm test`: PHP 7.4 e 8.2 por php-wasm + jsdom). **Publicada em 11/10/2026** (release v2.1.0), com a 2.0.17 junto
  e Categorias/Tags dos roteiros no menu Voucher Tec. Dono aprovou em 11/10/2026 ("a versão está ok"). O comparativo
  "salvar sem mexer" não foi rodado: o site de teste `VoucherTecTeste` não respondeu nesse dia; a regra está coberta
  pelos testes de `tt_roteiro_valores_a_gravar` (preço e fotos só gravam com "alterado").
- [ ] **Teste da 2.0.16 no WP no ar** (`VoucherTecTeste`): `aplicar` com `moeda_orcamento`/`preco_roteiros`,
  conferir cotação (R$/US$), card e página de roteiro com "Consulte-nos", e que o n8n ainda grava o preço.
- [ ] **Atualização nos 6 sites no ar (1.x → 2.0.5)**, um por vez, conferindo que nada muda na tela.
  Ajimex (`ajimex.com.br`): 1.3.2 → 2.0.7 OK; cores do site (#3B2E7E/#2A2159/#A8C83C, Nunito/Source Sans 3)
  aplicadas; aba Roteiros instalada (card #276, lista #277, roteiro #278) — aprovado pelo dono. Kit, home, menus e
  formulários intactos. Modelos 1.x #174/#175/#193 guardados.
  Experiências instalada (arquivo #282, post #283) e Contato (/contato/ #294, obrigado #295, forms contato #296 e
  newsletter #297, captação ligada com o company_id). Dados de contato aplicados do Cliente Ideal. Menu trocado pelo
  dono. Sobre (/sobre/ #301; description/history tirados do site, tagline mantida) e Cotação
  (/cotacao-de-viagens/ #303, obrigado #304, form #305). Menus Principal/Rodapé e botões de cotação do cabeçalho #48
  e da home (#27, #166) apontam para as páginas novas (backup em `Ajimex/backups/`). Páginas antigas
  /quem-somos/, /fale-conosco/, /cotacao-de-viagem/ seguem no ar sem link — decidir redirecionamento 301.
  Falta: teste de envio dos formulários (dono, no fim); cabeçalho/rodapé continuam os antigos (#48/#50). Só atualizar — não clicar em
  "Instalar site padrão". Troca de layout nesses sites é etapa separada, peça por peça.
  DSelection (`dselectiontravel.com.br`, 24/09/2026): 2.0.12; cores do site (#052948/#021632/#B9BFC9, Cinzel/Outfit) e
  dados do Cliente Ideal (nome "DSelection Travel") aplicados. Dono instalou as 5 abas: roteiros #1161–1163, blog
  #1167/#1168, contato #1171 (+#1172, forms #1173/#1174), cotação #1177 (+#1178, form #1179), sobre #1183 (sem
  description/history no Cliente Ideal). Ajustes à mão (backup em `dselections/backups/`): cabeçalho #753 transparente
  com a classe tt-topo-transparente (CSS do container 1ae6be22), assinatura do rodapé #1008 numa linha, arquivo antigo
  #1030 sem condição (o post_archive dele ganhava do "todos os arquivos" do #1167 — o plugin não libera condição mais
  específica), menu Main: Sobre nós → /sobre/ (com "A pessoa por trás da marca" como subitem, para caber), + Cotação
  e Contato. Falta: teste dos formulários (dono).
  Zizi Quatti (`ziziquattiviagens.com.br`, pasta/config `GDQExperience`, company GDQ Experience, 24/09/2026): 2.0.14;
  cores do manual (#1E3F6B/#344152/#FFC845, Archivo/Barlow) e dados aplicados; dono instalou as 5 abas (roteiros
  #216–218, blog #222/#223, contato #224/#225, cotação #230/#231, sobre #236, forms #226/#227/#232). Mesma base do
  Ajimex: cabeçalho #48 transparente (CSS no container cc5b294, keyframes só com o início) e links "Solicitar cotação"
  (#48, #162, home #27, destinos, 404) → /cotacao-de-viagens/ (backup em `GDQExperience/backups/`). Menus e rodapé #50
  ficam para a 2.0.15 (automático ao atualizar). Sem roteiros publicados.
- [ ] **Teste da 2.0.4** (reinstalar com substituir + aplicar) e demais páginas (roteiro com conteúdo, blog, obrigado, mobile)
- [ ] **Teste num WordPress no ar** (dono instala; Claude/MCP testa): instalar, aplicar dados de uma agência,
  conferir todas as páginas desktop/mobile, formulários e captação

- [x] **2.2.0 — Destinos, publicada em 11/10/2026** (release v2.2.0; pedido do dono em 11/10/2026): tipo `destinos` (`includes/destinos.php`), dados em
  `destino-dados.php`, tela em abas em `destino-editor.php` (Resumo, O que fazer, Quando ir, Fotos), menu
  Voucher Tec › Destinos / Adicionar destino, aba Destinos na Configuração (grupo `destino-card`,
  `destinos-lista`, `destino-single`). Modelos tirados do site-modelo `yellowgreen-termite-955684`
  (`src/destinos/`, seção `destinos` do `build_v2.py`). Decisões do dono: só a interface de cadastro agora
  (automação depois; a REST já aceita `acf`); sem botão de conversão de páginas; **não mexer no site da Zizi**.
  Spec e plano: `landing-pages/docs/superpowers/specs|plans/2026-10-11-voucher-tec-destinos*.md`.
- [x] **2.2.1** (11/10/2026): a lista `/destinos/` saía vazia — a grade herdou a consulta `tt_destinos` da página antiga
  do site-modelo (páginas-filhas de #29). Agora consulta o tipo `destinos`, como a lista de roteiros.
- [x] **2.2.2** (11/10/2026): `tt_voucher_item_certo` só aceita como certo o item que é link; item de menu que era a
  página `/destinos/` (mesmo endereço da lista) passa a ser reapontado. Achado no rodapé do site-modelo.
- [x] **Destinos no site-modelo** (11/10/2026, plugin 2.2.2): modelos #360 (card), #361 (lista) e #362 (página); destinos
  #363 Fernando de Noronha, #365 Sardenha, #366 Tóquio, recriados das páginas pela REST. Páginas #29, #82, #84 e #86 na
  lixeira (cópias em `_wordpress-base/_scratch/destinos-2026-10-11/`). Menu do cabeçalho com "Destinos" depois de
  Roteiros; rodapé reapontado. Conferido no navegador: lista, as três páginas, galeria, botão, formulário com o destino
  preenchido e celular. **Falta o dono conferir a tela de cadastro** (Voucher Tec › Adicionar destino) — não foi aberta
  no painel pelo Claude.
- [x] **2.3.0 — Destinos v2, página completa** (14/10/2026, **pronta no repositório, NÃO publicada**: sem release no GitHub,
  sem instalar em site nenhum — aguarda o OK do dono). Spec e contrato dos campos (Supabase ⇄ plugin):
  `clienteideal/docs/superpowers/specs/2026-10-14-destinos-v2-design.md` e `...-destinos-v2-campos.md`.
  - **Campos** (`tt_destinos_campos()` em `destino-dados.php`): `versao`, `escopo` (nacional|internacional), `subchamada` (≤160),
    `perfis` (até 3 da lista fixa, gravado um por linha), `duracao_ideal` (≤60), `clima`, `como_chegar`, `documentacao`,
    `moeda`/`idioma`/`fuso` (≤60), `pontos_turisticos`, `passeios`, `gastronomia`, `onde_ficar`, `faq` (listas gravadas como
    JSON, `tt_destino_listas()` tem máximo e limite de cada sub-campo), `atualizado_em` (AAAA-MM-DD). Os da v1 seguem
    (`texto_de_abertura` = Visão geral, `o_que_fazer`, `quando_ir`, `galeria`).
  - **REST `acf`**: leitura devolve listas como array (inclusive `o_que_fazer`, `galeria` e `perfis`) e `versao` como número;
    gravação aceita array ou texto, limpa e corta (`tt_destino_limpar`). Gravação com `wp_slash` (`tt_destino_gravar_meta`):
    sem isso o `update_post_meta` tirava a barra do `\"` e o JSON quebrava (de quebra resolve a "barra invertida some ao salvar"
    nos campos do destino).
  - **Tela** em 6 abas: Resumo, Informações práticas, O que fazer, Onde ficar, FAQ, Fotos. Listas com sub-campos = linhas fixas
    até o máximo do contrato (linha em branco não grava; sem JS de repetidor). Foto do ponto/passeio pela Biblioteca de Mídia.
    Nacional esconde documentação/moeda/idioma/fuso. Documentação só para ler (texto do sistema). Lista simples v1 só aparece
    em destino que ainda a tem.
  - **Página** (`destino-single`, gerada por `build_v2.py`): hero (nome, `[tt_destino_subchamada]`, botão "Solicite sua cotação"
    → `#tt-destino-cotacao`), depois `[tt_destino_visao_geral]`, `[tt_destino_praticas]`, `[tt_destino_o_que_fazer]`,
    `[tt_destino_onde_ficar]`, `[tt_destino_galeria]`, `[tt_destino_faq]`, `[tt_destino_atualizado]` e o formulário. Cada shortcode
    devolve '' sem dado (a seção some com o título). Card (`destino-card`) com a subchamada abaixo do nome.
  - **Decisões (Claude, 14/10/2026)**: passeios com menos de 3 não aparecem (mas ficam gravados — quem edita no painel não
    perde o que digitou); os mínimos do contrato (4 pontos, 3 pratos, 5 FAQ...) são do Supabase, o plugin só corta no máximo;
    nacional esconde as partes internacionais mesmo se vier dado; a galeria continua na página (entre Onde ficar e FAQ);
    "Atualizado em" sem a data do sistema usa a data da última edição; sem formulário de cotação, o CTA mostra o botão do
    WhatsApp do site (era a faixa vazia da lista de pendências); JSON-LD FAQPage no `<head>` pelo plugin; o botão do meio da
    página ("Quero uma cotação para X") saiu — o hero já leva ao formulário. Shortcodes da 2.2 (`tt_destino_texto|lista`)
    continuam, para sites que ainda têm o modelo antigo.
  - **Testes**: `cd tests && npm test` — PHP 7.4 e 8.2 (limpeza/limites v2, v1 igual, REST, shortcodes, tela) + JS (modelos,
    escopo e foto do item). Zip: `python empacotar.py` → `dist/traveltec-roteiros.zip`.
  - **Como atualizar nos sites** (depois da release aprovada): Plugins › "Atualizar agora" **e** Voucher Tec › Configuração ›
    Destinos › reinstalar a parte (troca a página e o card). Só atualizar, sem reinstalar: o modelo 2.2 continua mostrando
    abertura, o que fazer, quando ir e galeria; os campos novos ficam gravados, sem aparecer.
  - **Falta**: dono aprovar → release v2.3.0 → instalar no site-modelo e conferir com um destino v1 e um v2 (nacional e
    internacional) → Rede Turística e clientes.
- [ ] **Automação dos destinos** (n8n / Cliente Ideal) — etapa futura (v2: Supabase grava no contrato acima).
- Código dos destinos: `includes/destinos.php`, `includes/destino-dados.php`, `includes/destino-editor.php`,
  `assets/destinos.css`, `assets/destino.js`, `assets/roteiro-editor.js|css` (tela, compartilhada com os roteiros),
  `src/destinos/` + seção `destinos` do `build_v2.py` (modelos).
- **Destinos só "ligam" em quem instala a aba** (`tt_destinos_ativo`: existe o modelo `destino-single`). Antes disso o tipo
  aparece no painel e na REST, mas não assume `/destinos/` nem `/destinos/<nome>/`. Motivo: em 11/10/2026 **Ajimex e Zizi
  Quatti tinham página `/destinos/` publicada (#29)** — com o tipo sempre público, só atualizar o plugin derrubaria as
  páginas-filhas. Antes de instalar a aba num site desses: passar o conteúdo para o cadastro e mandar as páginas para a lixeira.
- Página do destino: abertura, "o que fazer", "quando ir", galeria e formulário saem por shortcodes do plugin
  (`[tt_destino_texto|lista|galeria|form]`), não por tags dinâmicas — assim a seção vazia some com o título.
- Ficou para depois (revisão de 11/10/2026): faixa do formulário aparece vazia sem formulário de cotação instalado;
  menus criados pelo próprio plugin só ganham "Destinos" na instalação seguinte de uma parte; barra invertida some ao
  salvar (igual aos roteiros); `banner-destinos.jpg` entra na Biblioteca a cada reinstalação parcial.
- [x] **2.3.1** (15/10/2026): `/destinos/` do DSelection abria com o modelo do blog (#1167, "Experiências") — o cache
  de condições do Elementor foi refeito na instalação com o tipo ainda desligado e perdeu `destinos_archive`. Agora o
  tipo é ligado antes do cache, o cache é refeito de novo na visita seguinte (`wp_loaded`) e, ao atualizar, uma vez no
  painel (`tt_voucher_ajustes_da_versao`), o que conserta os sites que já tinham instalado.

## Decisões do dono

- Site sempre montado em domínio provisório; o MCP busca logo, cores, fontes, endereço e redes no Cliente Ideal.
- O plugin precisa ter todas as páginas e uma forma fácil de trocar as cores.
- A home não entra nesta versão: na Rede ela está no layout antigo e depende do plugin Prime Slider.
- Nada de WordPress local para teste: o dono instala num WP no ar.

## Como funciona

- **Dados**: option `tt_voucher_cliente`, com as colunas de `companies` mais `telefone`, `youtube_url` e
  `webhook_url`. Os marcadores `%tt:campo%` e `https://tt.token/<campo>` são trocados no
  `elementor/widget/render_content`.
- **Estilo**: option `tt_voucher_estilo`. A paleta da Rede é o ponto de partida. `aplicar` troca as
  cores hex, o `rgba` da primária e os nomes das fontes em todos os documentos do plugin, e atualiza o Kit.
- **Instalação**: option `tt_voucher_ids`, com docs, forms, menus e mídia. Marcadores `{{DOC|PAGE|FORM|MENU|IMG|IMGID:x}}`.
- **Captação**: `assets/captacao.js` com `window.TT_VOUCHER_CAPTACAO`. Liga com a option `tt_voucher_captacao`,
  que é ativada ao instalar os formulários. Na Rede fica desligada, porque ela usa o snippet #5406.
- **API**: `voucher-tec/v1/status|instalar|aplicar` (manage_options).

## Pendências / riscos a conferir no teste

- Condições do Theme Builder: modelos concorrentes perdem a condição, com backup em `tt_voucher_condicoes_anteriores`.
- Cabeçalho transparente na home (`tt_voucher_home_transparente`): só funciona se a home tiver imagem no topo.
- JetFormBuilder: conferir se o formulário criado pelo plugin renderiza (meta `_jf_*`) e envia por AJAX.
- Rede Turística (1.3.2) vai receber a 2.0 pela atualização: nada é instalado sozinho, mas conferir o painel.

## Próximo passo

Dono aprova a 2.3.0 (Destinos v2) → release → site-modelo com destino v1 e v2. Dono confere a tela de cadastro de destinos.
Depois: automação dos destinos v2 (Supabase + n8n / Cliente Ideal).
Antes de instalar a aba Destinos no Ajimex ou na Zizi Quatti: passar as páginas de destino para o cadastro.
