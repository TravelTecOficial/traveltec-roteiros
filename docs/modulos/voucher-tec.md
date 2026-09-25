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

O dono instala o zip num WordPress no ar e passa a URL e o Application Password. Depois disso, o Claude
roda `instalar` e `aplicar` com uma agência do Cliente Ideal e confere o site.
