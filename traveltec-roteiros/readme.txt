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
