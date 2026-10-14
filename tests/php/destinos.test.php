<?php
// Tipo Destinos, shortcodes e formulário — includes/destinos.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['tt_tipos']      = array();
$GLOBALS['tt_shortcodes'] = array();
$GLOBALS['tt_ids']        = array( 'docs' => array(), 'forms' => array(), 'menus' => array(), 'midia' => array() );
$GLOBALS['tt_vivos']      = array();
$GLOBALS['tt_anexos']     = array( 127 => 'https://x.test/n1.jpg', 128 => 'https://x.test/n2.jpg' );

function post_type_exists( $t ) { return isset( $GLOBALS['tt_tipos'][ $t ] ); }
function register_post_type( $t, $args ) { $GLOBALS['tt_tipos'][ $t ] = $args; }
function register_post_meta( $t, $chave, $args ) { $GLOBALS['tt_metas'][ $t ][ $chave ] = $args; }
function add_shortcode( $nome, $cb ) { $GLOBALS['tt_shortcodes'][ $nome ] = $cb; }
function shortcode_atts( $padrao, $atts ) { return array_merge( $padrao, array_intersect_key( (array) $atts, $padrao ) ); }
function shortcode_exists( $n ) { return isset( $GLOBALS['tt_shortcodes'][ $n ] ); }
function do_shortcode( $s ) { return 'FORM(' . $s . ')'; }
function get_the_ID() { return 9; }
function wpautop( $s ) { return $s; }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function wp_get_attachment_image_url( $id, $tam = 'thumbnail' ) { return isset( $GLOBALS['tt_anexos'][ $id ] ) ? $GLOBALS['tt_anexos'][ $id ] . '?' . $tam : false; }
function tt_voucher_ids() { return $GLOBALS['tt_ids']; }
function tt_voucher_vivo( $id ) { return in_array( $id, $GLOBALS['tt_vivos'], true ); }

require '/p/includes/roteiro-dados.php';
require '/p/includes/destino-dados.php';
require '/p/includes/destinos.php';

/* ---------------------------------------------------------------- tipo de conteúdo */
// Sem a aba Destinos instalada: existe no painel e na REST, mas não toma os endereços do site
// (Ajimex e Zizi Quatti têm PÁGINAS em /destinos/ — atualizar o plugin não pode derrubá-las).
ok( ! tt_destinos_ativo(), 'sem o modelo da página do destino instalado, os destinos não estão ativos no site' );
tt_destinos_registrar_tipo();
$tipo = $GLOBALS['tt_tipos']['destinos'];
ok( false === $tipo['has_archive'] && false === $tipo['rewrite'] && false === $tipo['publicly_queryable'], 'inativo: sem /destinos/ nem /destinos/<slug>/' );
ok( true === $tipo['show_ui'] && true === $tipo['show_in_rest'] && 'destinos' === $tipo['rest_base'], 'inativo: cadastro e REST funcionam' );
ok( true === $tipo['exclude_from_search'] && false === $tipo['show_in_nav_menus'], 'inativo: fora da busca e dos menus' );
$GLOBALS['tt_ids']['docs']['destino-single'] = 300;
ok( ! tt_destinos_ativo(), 'modelo na lixeira: continua inativo' );
$GLOBALS['tt_vivos'] = array( 300 );
ok( tt_destinos_ativo(), 'modelo instalado e vivo: ativo' );
tt_destinos_registrar_tipo();
igual( false, $GLOBALS['tt_tipos']['destinos']['has_archive'], 'já registrado: não registra de novo sozinho' );
tt_destinos_registrar_tipo( true );
$tipo = $GLOBALS['tt_tipos']['destinos'];
igual( 'destinos', $tipo['has_archive'], 'ativo: arquivo em /destinos/' );
igual( array( 'slug' => 'destinos', 'with_front' => false ), $tipo['rewrite'], 'ativo: endereço /destinos/<slug>/' );
ok( true === $tipo['publicly_queryable'] && true === $tipo['show_in_rest'] && 'destinos' === $tipo['rest_base'], 'ativo: público e na REST' );
igual( array( 'title', 'thumbnail', 'excerpt', 'revisions' ), $tipo['supports'], 'título, capa, resumo e revisões' );
$GLOBALS['tt_tipos']['destinos'] = 'de-outro-plugin';
tt_destinos_registrar_tipo();
igual( 'de-outro-plugin', $GLOBALS['tt_tipos']['destinos'], 'se o site já tem o tipo, não registra de novo' );
ok( isset( $GLOBALS['tt_acoes']['init'] ), 'ligado ao init' );

/* ---------------------------------------------------------------- shortcodes */
igual(
	array( 'tt_destino_texto', 'tt_destino_lista', 'tt_destino_galeria', 'tt_destino_subchamada', 'tt_destino_visao_geral', 'tt_destino_praticas', 'tt_destino_o_que_fazer', 'tt_destino_onde_ficar', 'tt_destino_faq', 'tt_destino_atualizado', 'tt_destino_form' ),
	array_keys( $GLOBALS['tt_shortcodes'] ),
	'shortcodes: os da 2.2 (sites com o modelo antigo) e um por seção da página v2'
);
$sc = $GLOBALS['tt_shortcodes'];
$GLOBALS['tt_meta'][9] = array( 'texto_de_abertura' => '<p>Abertura</p>', 'quando_ir' => '', 'o_que_fazer' => "A\nB", 'galeria' => '127,999,128' );
igual( '<div class="tt-destino-texto"><p>Abertura</p></div>', $sc['tt_destino_texto']( array( 'campo' => 'texto_de_abertura' ) ), 'texto: abertura' );
igual( '', $sc['tt_destino_texto']( array( 'campo' => 'quando_ir', 'titulo' => 'Quando ir' ) ), 'texto: campo vazio some com o título' );
igual( '', $sc['tt_destino_texto']( array( 'campo' => 'galeria' ) ), 'texto: só aceita os campos de texto' );
igual( '', $sc['tt_destino_texto']( array() ), 'texto: sem campo, nada' );
contem( '<li>A</li><li>B</li>', $sc['tt_destino_lista']( array( 'titulo' => 'O que fazer' ) ), 'lista do destino' );
$gal = $sc['tt_destino_galeria']( array( 'titulo' => 'Galeria' ) );
igual( 2, substr_count( $gal, '<img' ), 'galeria: foto apagada da Biblioteca (999) é ignorada' );
contem( 'href="https://x.test/n1.jpg?large"', $gal, 'galeria: abre a foto grande' );
contem( 'src="https://x.test/n1.jpg?medium_large"', $gal, 'galeria: miniatura média' );
contem( 'data-elementor-lightbox-slideshow="tt-destino-9"', $gal, 'galeria: um grupo por destino' );

/* ---------------------------------------------------------------- formulário de cotação */
igual( 0, tt_destino_form_id(), 'sem formulário instalado: 0' );
igual( '', $sc['tt_destino_form']( array( 'titulo' => 'Solicite sua cotação' ) ), 'sem formulário: nada, nem o título' );
$GLOBALS['tt_ids']['forms']['cotacao'] = 305;
igual( 0, tt_destino_form_id(), 'formulário na lixeira: 0' );
$GLOBALS['tt_vivos'] = array( 305 );
igual( 0, tt_destino_form_id(), 'JetFormBuilder desligado (shortcode não existe): 0, para não aparecer o código na página' );
$GLOBALS['tt_shortcodes']['jet_fb_form'] = 'jfb';
igual( 305, tt_destino_form_id(), 'formulário instalado e vivo' );
$form = $sc['tt_destino_form']( array( 'titulo' => 'Solicite sua cotação' ) );
contem( '<h2 class="tt-destino-titulo">Solicite sua cotação</h2>', $form, 'formulário: título' );
contem( 'FORM([jet_fb_form form_id="305" submit_type="ajax"', $form, 'formulário: o de cotação do plugin' );

/* ---------------------------------------------------------------- primeiro destino publicado entra no menu do site */
$GLOBALS['tt_ligou'] = 0;
function tt_voucher_menus_ligar() { $GLOBALS['tt_ligou']++; }
$publicar = function ( $novo, $antigo, $tipo ) {
	foreach ( $GLOBALS['tt_acoes']['transition_post_status'] as $cb ) {
		$cb( $novo, $antigo, (object) array( 'post_type' => $tipo ) );
	}
};
$GLOBALS['tt_vivos'] = array( 300, 305 );
$publicar( 'publish', 'draft', 'destinos' );
igual( 1, $GLOBALS['tt_ligou'], 'destino publicado: liga o item Destinos nos menus do site' );
$publicar( 'publish', 'publish', 'destinos' );
$publicar( 'draft', 'publish', 'destinos' );
$publicar( 'publish', 'draft', 'roteiros' );
igual( 1, $GLOBALS['tt_ligou'], 'atualizar, despublicar ou publicar outro tipo não mexe nos menus' );
$GLOBALS['tt_vivos'] = array( 305 );
$publicar( 'publish', 'draft', 'destinos' );
igual( 1, $GLOBALS['tt_ligou'], 'sem a aba Destinos instalada, publicar não mexe nos menus' );

/* ================================================================ v2 (2.3.0): seções da página */
$GLOBALS['tt_anexos'][201] = 'https://x.test/torre.jpg';
$GLOBALS['tt_anexos'][202] = 'https://x.test/barco.jpg';
$passeios3 = '[{"titulo":"Barco","duracao":"3 h","foto":202},{"titulo":"Trem","duracao":"1 dia","foto":null},{"titulo":"Vinho","duracao":"4 h","foto":999}]';
$GLOBALS['tt_meta'][9] = array(
	'versao'            => '2',
	'escopo'            => 'internacional',
	'subchamada'        => 'Sol, fado e pastel de nata',
	'texto_de_abertura' => '<p>Lisboa é...</p>',
	'perfis'            => "cultura\ngastronomia",
	'duracao_ideal'     => '5 a 7 dias',
	'quando_ir'         => '<p>Primavera</p>',
	'clima'             => '[{"estacao":"verao","meses":"jun–ago","max":28,"min":18,"chuva_mm":20}]',
	'como_chegar'       => '<p>Voo direto</p>',
	'documentacao'      => '<p>Passaporte</p>',
	'moeda'             => 'Euro (EUR)',
	'idioma'            => 'Português',
	'fuso'              => '+4 h em relação a Brasília',
	'pontos_turisticos' => '[{"nome":"Torre de Belém","descricao":"Símbolo","foto":201}]',
	'passeios'          => $passeios3,
	'gastronomia'       => '[{"nome":"Bacalhau","descricao":"Mil receitas"}]',
	'onde_ficar'        => '[{"bairro":"Alfama","descricao":"Antigo","perfil":"Casais"}]',
	'faq'               => '[{"pergunta":"Precisa de visto?","resposta":"Não"}]',
	'atualizado_em'     => '2026-10-14',
);
igual( '<p class="tt-destino-subchamada tt-destino-subchamada--hero">Sol, fado e pastel de nata</p>', $sc['tt_destino_subchamada']( array() ), 'hero: subchamada' );
contem( 'tt-destino-subchamada--card', $sc['tt_destino_subchamada']( array( 'estilo' => 'card' ) ), 'card: subchamada' );
$vg = $sc['tt_destino_visao_geral']( array( 'titulo' => 'Visão geral' ) );
contem( '<h2 class="tt-destino-titulo">Visão geral</h2><div class="tt-destino-texto"><p>Lisboa é...</p></div>', $vg, 'visão geral: título e texto' );
contem( 'Gastronomia</li>', $vg, 'visão geral: perfis' );
$pr = $sc['tt_destino_praticas']( array( 'titulo' => 'Informações práticas' ) );
foreach ( array( 'Quando ir', 'Clima por estação', 'Como chegar', 'Documentação' ) as $sub ) {
	contem( '<h3 class="tt-destino-subtitulo">' . $sub . '</h3>', $pr, 'práticas (internacional): ' . $sub );
}
contem( '<strong>Euro (EUR)</strong>', $pr, 'práticas (internacional): moeda' );
$of = $sc['tt_destino_o_que_fazer']( array( 'titulo' => 'O que fazer' ) );
contem( 'src="https://x.test/torre.jpg?medium_large" alt="Torre de Belém"', $of, 'o que fazer: ponto turístico com foto' );
contem( '<h3 class="tt-destino-subtitulo">Passeios</h3>', $of, 'o que fazer: 3 passeios aparecem' );
contem( '<figcaption>Foto: Viator</figcaption>', $of, 'o que fazer: crédito da foto do passeio' );
contem( '<article class="tt-destino-card tt-destino-card--sem-foto"><div class="tt-destino-card__corpo"><h4>Vinho</h4>', $of, 'o que fazer: foto apagada da Biblioteca, card sem imagem' );
contem( '<strong>Bacalhau</strong>', $of, 'o que fazer: gastronomia' );
contem( '<h3>Alfama</h3>', $sc['tt_destino_onde_ficar']( array( 'titulo' => 'Onde ficar' ) ), 'onde ficar' );
contem( '<summary>Precisa de visto?</summary>', $sc['tt_destino_faq']( array( 'titulo' => 'Perguntas frequentes' ) ), 'faq' );
igual( '<p class="tt-destino-atualizado">Atualizado em 14/10/2026</p>', $sc['tt_destino_atualizado']( array() ), 'atualizado em' );

// passeios com menos de 3: a seção some
$GLOBALS['tt_meta'][9]['passeios'] = '[{"titulo":"Barco","duracao":"3 h","foto":202},{"titulo":"Trem","duracao":"1 dia"}]';
ok( false === strpos( $sc['tt_destino_o_que_fazer']( array( 'titulo' => 'O que fazer' ) ), 'Passeios' ), 'passeios: menos de 3, não aparecem' );

// nacional: as partes de internacional somem, mesmo se houver dado
$GLOBALS['tt_meta'][9]['escopo'] = 'nacional';
$pr = $sc['tt_destino_praticas']( array( 'titulo' => 'Informações práticas' ) );
ok( false === strpos( $pr, 'Documentação' ) && false === strpos( $pr, 'Euro' ), 'nacional: sem documentação, moeda, idioma e fuso' );
contem( 'Como chegar', $pr, 'nacional: o resto das informações práticas continua' );
igual( '', $sc['tt_destino_texto']( array( 'campo' => 'documentacao', 'titulo' => 'Documentação' ) ), 'nacional: documentação pelo shortcode antigo também some' );

// seções vazias somem com o título
$GLOBALS['tt_meta'][9] = array( 'escopo' => 'nacional' );
foreach ( array( 'tt_destino_subchamada', 'tt_destino_visao_geral', 'tt_destino_praticas', 'tt_destino_o_que_fazer', 'tt_destino_onde_ficar', 'tt_destino_faq' ) as $nome ) {
	igual( '', $sc[ $nome ]( array() ), 'vazio: ' . $nome . ' não mostra nada' );
}

// destino v1 (sem versão): sai com o que tiver
$GLOBALS['tt_meta'][9] = array( 'texto_de_abertura' => '<p>Abertura</p>', 'quando_ir' => '<p>Verão</p>', 'o_que_fazer' => "Mergulho\nTrilha", 'galeria' => '127' );
contem( '<p>Abertura</p>', $sc['tt_destino_visao_geral']( array() ), 'v1: abertura na visão geral' );
contem( '<h3 class="tt-destino-subtitulo">Quando ir</h3><div class="tt-destino-texto"><p>Verão</p></div>', $sc['tt_destino_praticas']( array() ), 'v1: quando ir nas informações práticas' );
contem( '<ul class="tt-destino-lista"><li>Mergulho</li><li>Trilha</li></ul>', $sc['tt_destino_o_que_fazer']( array() ), 'v1: a lista "o que fazer" dentro de O que fazer' );
igual( '', $sc['tt_destino_faq']( array() ), 'v1: sem FAQ' );
contem( '<h2 class="tt-destino-titulo">O que fazer</h2>', $sc['tt_destino_lista']( array( 'titulo' => 'O que fazer' ) ), 'v1: shortcode antigo da lista continua' );

/* ---------------------------------------------------------------- REST (campo "acf") */
$GLOBALS['tt_capa'] = array();
function get_post_thumbnail_id( $id ) { return isset( $GLOBALS['tt_capa'][ $id ] ) ? $GLOBALS['tt_capa'][ $id ] : 0; }
function get_post( $id ) { return 50 === $id ? (object) array( 'ID' => 50 ) : null; }
function set_post_thumbnail( $post, $id ) { $GLOBALS['tt_capa'][ $post ] = $id; }
$GLOBALS['tt_meta'][40] = array();
tt_destino_rest_gravar( array(
	'versao'            => 2,
	'escopo'            => 'internacional',
	'subchamada'        => str_repeat( 's', 200 ),
	'perfis'            => array( 'praia', 'luxo' ),
	'faq'               => array( array( 'pergunta' => 'P "1"?', 'resposta' => 'R' ) ),
	'passeios'          => array(),
	'o_que_fazer'       => array( 'A', 'B' ),
	'galeria'           => array( 7, 8 ),
	'imagem_destaque'   => 50,
	'campo_estranho'    => 'x',
), 40 );
igual( 160, strlen( $GLOBALS['tt_meta'][40]['subchamada'] ), 'REST gravação: subchamada cortada em 160' );
igual( '[{"pergunta":"P \"1\"?","resposta":"R"}]', $GLOBALS['tt_meta'][40]['faq'], 'REST gravação: lista gravada como JSON' );
igual( '', $GLOBALS['tt_meta'][40]['passeios'], 'REST gravação: lista vazia grava vazio' );
ok( ! isset( $GLOBALS['tt_meta'][40]['campo_estranho'] ), 'REST gravação: chave fora do contrato é ignorada' );
igual( 50, $GLOBALS['tt_capa'][40], 'REST gravação: imagem destacada' );
ok( ! isset( $GLOBALS['tt_meta'][40]['quando_ir'] ), 'REST gravação: campo que não veio não é tocado' );
$lido = tt_destino_rest_ler( 40 );
igual( 2, $lido['versao'], 'REST leitura: versão inteira' );
igual( array( 'praia' ), $lido['perfis'], 'REST leitura: perfis em array' );
igual( array( array( 'pergunta' => 'P "1"?', 'resposta' => 'R' ) ), $lido['faq'], 'REST leitura: FAQ em array' );
igual( array(), $lido['passeios'], 'REST leitura: lista vazia = []' );
igual( array( 'A', 'B' ), $lido['o_que_fazer'], 'REST leitura: o que fazer em array' );
igual( array( 7, 8 ), $lido['galeria'], 'REST leitura: galeria em array de ids' );
igual( '', $lido['moeda'], 'REST leitura: campo ausente = texto vazio' );
igual( 50, $lido['imagem_destaque'], 'REST leitura: capa' );
igual( array_merge( array( 'imagem_destaque' ), array_keys( tt_destinos_campos() ) ), array_keys( $lido ), 'REST leitura: todas as chaves do contrato' );
igual( true, tt_destino_rest_gravar( 'não é objeto', 40 ), 'REST gravação: valor que não é objeto não grava nada' );
$GLOBALS['tt_meta'][41] = array( 'texto_de_abertura' => '<p>v1</p>', 'o_que_fazer' => "X\nY", 'quando_ir' => '<p>Q</p>', 'galeria' => '3,4' );
$v1 = tt_destino_rest_ler( 41 );
igual( null, $v1['versao'], 'REST leitura de destino v1: versão null' );
igual( '<p>v1</p>', $v1['texto_de_abertura'], 'REST leitura de destino v1: abertura' );
igual( array( 'X', 'Y' ), $v1['o_que_fazer'], 'REST leitura de destino v1: lista' );
igual( array(), $v1['faq'], 'REST leitura de destino v1: campos novos vazios' );

/* ---------------------------------------------------------------- CTA sem formulário: WhatsApp do site */
$GLOBALS['tt_vivos'] = array();
function get_the_title( $id = 0 ) { return 'Lisboa &amp; arredores'; }
if ( ! function_exists( 'tt_voucher_valor' ) ) {
	function tt_voucher_valor( $c ) { return 'whatsapp_digitos' === $c ? '+55 (11) 91234-5678' : ''; }
}
$w = $sc['tt_destino_form']( array( 'titulo' => 'Solicite sua cotação' ) );
contem( 'href="https://wa.me/5511912345678?text=Ol%C3%A1%21%20Quero%20uma%20cota%C3%A7%C3%A3o%20para%20Lisboa%20%26%20arredores."', $w, 'sem formulário: botão do WhatsApp com o destino na mensagem' );
contem( '<h2 class="tt-destino-titulo">Solicite sua cotação</h2>', $w, 'sem formulário: título continua' );
