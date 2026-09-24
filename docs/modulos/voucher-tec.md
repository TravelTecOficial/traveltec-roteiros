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
- [ ] **Teste da 2.0.1** (reinstalar com substituir + aplicar) e demais páginas (roteiro com conteúdo, blog, obrigado, mobile)
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
