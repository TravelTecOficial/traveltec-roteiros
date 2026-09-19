=== TravelTec Roteiros ===
Requer: WordPress 6+, Elementor + Elementor Pro (Theme Builder e Loop Grid), PHP 7.4+.

Instalação: Plugins > Adicionar novo > Enviar plugin > traveltec-roteiros.zip > Ativar.
Ao ativar (com Elementor Pro ativo) os modelos são instalados sozinhos. Senão: Roteiros > Modelos do Elementor > Instalar modelos.

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
