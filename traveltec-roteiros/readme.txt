=== Voucher Tec - Travel Tec ===
(slug traveltec-roteiros; até a 1.3.2 se chamava TravelTec Roteiros)
Requer: WordPress 6+, tema Hello Elementor, Elementor + Elementor Pro / PRO Elements, JetFormBuilder, PHP 7.4+.

Instalação: Plugins > Adicionar novo > Enviar plugin > traveltec-roteiros.zip > Ativar.
Em site novo, ao ativar (com Elementor Pro ativo) o site padrão é instalado sozinho. Senão: Voucher Tec > Instalar site padrão
ou POST /wp-json/voucher-tec/v1/instalar. Dados da agência, cores e fontes: Voucher Tec > Dados da agência ou
POST /wp-json/voucher-tec/v1/aplicar (linha da tabela companies do Cliente Ideal). Guia completo: docs/GUIA-MCP.md.

O que instala
- Tipo de conteúdo "Roteiros" (slug /roteiros/) com os campos do fluxo n8n "Roteiros Lazer - Publicar Pagina":
  subtitulo, texto_de_apresentacao, preco_de_referencia, galeria, programacao, hospedagem, inclui, nao_inclui,
  pagamento, cancelamento, documentacao + imagem destacada. A API aceita o objeto "acf" no POST /wp-json/wp/v2/roteiros.
- Modelo Elementor "página do roteiro" (Single, condição: todos os roteiros).
- Modelo "card" (Loop Item) e o modelo de Arquivo que responde em /roteiros/ com a grade.

1.1.0: a lista passou a ser /roteiros/ (arquivo do tipo de conteúdo). Antes era a página /galeria-de-roteiros/,
que continua existindo em quem instalou a 1.0.0 e pode ser apagada.
Cores e fontes vêm do Kit do Elementor do site.

1.1.1: a página do roteiro passa a exibir o preço de referência, logo abaixo do subtítulo.

1.2.0: a animação da galeria passou a vir num CSS do plugin (o <style> do campo é descartado na gravação).

1.3.0: atualização automática. O site consulta as releases do repositório
github.com/TravelTecOficial/traveltec-roteiros e mostra "Atualizar agora" na tela de Plugins.
Repositório privado: define( 'TT_ROTEIROS_GITHUB_TOKEN', '...' ) no wp-config.php.

1.3.1: rótulos do tipo de conteúdo (busca e lista vazia).

1.3.2: a aba do navegador em /roteiros/ deixa de ser "Roteiros Archive" — quem escrevia isso era o Rank Math.

2.0.0 (Voucher Tec): o plugin passa a instalar o site padrão inteiro, no layout da Rede Turística
- cabeçalho (transparente sobre o topo), rodapé, roteiros (card com movimento, lista, página com galeria e abas),
  blog (lista e post), Sobre, Contato, Cotação, 3 páginas de obrigado, menus e os formulários Cotação, Contato e
  Newsletter (JetFormBuilder, AJAX);
- dados da agência com os campos da tabela companies do Cliente Ideal, exibidos por marcadores (%tt:campo%);
- cores e fontes trocadas de uma vez em todas as páginas e no Kit do Elementor;
- captação de leads: UTMs e ids de clique + envio ao webhook recebe-forms (n8n) com o company_id;
- API REST voucher-tec/v1 (status, instalar, aplicar) e painel "Voucher Tec".
Sites da 1.x atualizam só o código: nada é instalado sem clicar em "Instalar site padrão".

2.0.1 (teste no clone da Rede com os dados da Mergulhando na Viagem):
- logo do rodapé ligado ao logo do site; nome da agência no copyright do rodapé;
- menus do plugin refeitos a cada instalação, com as páginas que existirem;
- Instagram/TikTok/YouTube cadastrados só com o @perfil viram URL completa;
- a secundária da marca só vira a cor dos textos se for escura (senão vai só para o Kit);
- a sinopse do Cliente Ideal (texto interno) nunca é usada no site.

2.0.2 (modelos da Rede Turística v2.0, fechada em 24/09/2026):
- post do blog no padrão da casa (capa, lateral com busca e categorias, chamada final com botão);
- logo do cabeçalho com altura limitada (logos retangulares não crescem no tablet/celular);
- primeira versão publicada no GitHub com atualização automática para os sites.

2.0.3: reinstalar uma peça depois de aplicar os dados já grava com as cores e fontes do site
(antes voltava com a paleta da referência).

2.0.4: inclui o popup "Menu (celular)" que o botão de menu do cabeçalho abre — sem ele, num site
limpo, o menu não abria no celular.

2.0.5: nos sites sem o site padrão instalado (os da 1.x), o plugin não acrescenta nada às páginas —
as fontes do Google e o CSS dos ícones vazios só entram depois de "Instalar site padrão".

2.0.6: painel em abas — Licença e instruções, Identidade, Roteiros, Experiências, Contato, Cotação, Sobre.
Cada aba mostra as suas peças (Editar no Elementor / Ver), instala só aquela parte e edita os dados que ela usa.
Instalar uma parte não mexe em mais nada: cores e fontes globais do Elementor só na instalação completa,
menus só com cabeçalho/rodapé, formulários só os da parte — dá para trocar um site no ar aos poucos.

2.0.7: salvar cores e fontes num site com o layout antigo muda só as peças do plugin; as cores e fontes
globais do Elementor (Kit) só mudam depois que o cabeçalho do plugin estiver instalado.

2.0.8: links internos dos modelos (botões de cotação, "Ver roteiros", blog) apontam para a página que existir no
site — a do plugin ou a que já havia (/cotacao-de-viagem/, /experiencias/, /fale-conosco/...). O post do blog
assume também o lugar de um "Single Post" antigo. Quem já instalou uma parte: reinstalar para ganhar os links.

2.0.9: títulos dos cards do blog no tamanho do modelo mesmo em sites com tipografia global própria (antes pegavam
o tamanho de título do Kit e ficavam enormes).

2.0.10: cabeçalho transparente também nos posts do blog (sobre a foto de capa), como nos roteiros; o fundo
aparece ao rolar a página.

2.0.11: a capa do post desce o título a altura do cabeçalho transparente (reinstalar Experiências).

2.0.12: quando a API do GitHub recusa (limite de consultas por IP, comum em hospedagem compartilhada), o site
descobre a última versão pela página de releases — a atualização aparece mesmo assim.

2.0.13: as páginas do plugin usam as fontes da aba Identidade mesmo quando o Kit do site tem outras (sites com
o layout antigo). Ex.: DSelection, em que o texto do Kit é Cinzel, só maiúsculas.

2.0.14: links dentro das páginas do plugin seguem a fonte do texto (o Kit pode ter fonte própria para links).

2.0.15: as páginas instaladas entram nos menus do site ("Quem Somos", "Fale Conosco", "Cotação"... passam a
apontar para as páginas novas; o que faltar entra no fim do menu do cabeçalho). Ao ativar ou atualizar, a
assinatura TravelTec antiga do rodapé vira uma linha discreta.

2.0.16: duas opções no painel. Aba Cotação: moeda do orçamento por pessoa no formulário (dólar, o padrão, ou
real — faixas fixas em cada moeda, sem conversão de câmbio). Aba Roteiros: preço nos roteiros — exibir o preço
de referência (padrão) ou mostrar "Consulte-nos" no card e na página do roteiro (o valor gravado não muda).
Também pela API: POST /wp-json/voucher-tec/v1/aplicar com moeda_orcamento (USD|BRL) e preco_roteiros
(exibir|consulte); GET /status devolve as escolhas em "opcoes".
