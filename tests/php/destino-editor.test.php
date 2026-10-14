<?php
// Tela de edição do destino — includes/destino-editor.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['tt_pode']   = true;
$GLOBALS['tt_nonce']  = true;
$GLOBALS['tt_anexos'] = array( 127 => 'https://x.test/n1.jpg' );
function wp_verify_nonce( $n, $a ) { return $GLOBALS['tt_nonce'] && 'tt_destinos_salvar' === $a; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function wp_unslash( $v ) { return $v; }
function current_user_can( $cap, $id = 0 ) { return $GLOBALS['tt_pode']; }
function wp_get_attachment_image_url( $id, $tam = 'thumbnail' ) { return isset( $GLOBALS['tt_anexos'][ $id ] ) ? $GLOBALS['tt_anexos'][ $id ] : false; }
function get_the_post_thumbnail( $id, $tam ) { return 5 === $id ? '<img src="capa.jpg">' : ''; }
function get_post_field( $campo, $id ) { return 5 === $id ? 'Arquipélago <b>protegido</b>' : ''; }

require '/p/includes/roteiro-dados.php';
require '/p/includes/destino-dados.php';
require '/p/includes/destino-editor.php';

/* ---------------------------------------------------------------- a caixa */
$post = (object) array( 'ID' => 5, 'post_excerpt' => 'Ilha & mar' );
$GLOBALS['tt_meta'][5] = array( 'texto_de_abertura' => '<p>Abertura</p>', 'o_que_fazer' => "Barco\nTrilha \"A\"", 'quando_ir' => '', 'galeria' => '127,999' );
ob_start();
tt_destino_desenhar_caixa( $post );
$html = ob_get_clean();

contem( 'name="tt_destinos_nonce"', $html, 'caixa: nonce' );
contem( 'class="tt-rot tt-dest" data-tt-rot', $html, 'caixa: usa a mesma raiz do editor de roteiros (o JS liga sozinho)' );
igual( 6, substr_count( $html, 'data-tt-aba="' ), 'caixa: seis abas (Resumo, Informações práticas, O que fazer, Onde ficar, FAQ, Fotos)' );
igual( 6, substr_count( $html, 'data-tt-painel="' ), 'caixa: seis painéis' );
igual( 3, substr_count( $html, 'tt-rot__ponto is-cheio' ), 'caixa: destino v1 — Resumo, O que fazer e Fotos preenchidas; as outras vazias' );
contem( '<label for="excerpt">Resumo</label><textarea class="widefat" rows="3" id="excerpt" name="excerpt" data-tt-contador="160">Ilha &amp; mar</textarea>', $html, 'resumo: é o campo "excerpt" do WordPress, com o id que o salvamento automático lê (senão o rascunho perde o resumo)' );
contem( 'name="tt_destinos[texto_de_abertura]" data-tt-editor>&lt;p&gt;Abertura&lt;/p&gt;</textarea>', $html, 'abertura: editor visual' );
contem( 'data-tt-itens data-nome="tt_destinos[o_que_fazer][]"', $html, 'lista: caixa de itens' );
contem( '<input type="hidden" name="tt_destinos[o_que_fazer_presente]" value="1">', $html, 'lista: marca de presença (para gravar a lista vazia)' );
contem( 'name="tt_destinos[o_que_fazer][]" value="Trilha &quot;A&quot;"', $html, 'lista: um campo por item, escapado' );
igual( 2, substr_count( $html, 'name="tt_destinos[o_que_fazer][]"' ), 'lista: dois itens gravados, dois campos' );
contem( 'name="tt_destinos[galeria]"', $html, 'fotos: campo com os IDs' );
contem( '&quot;id&quot;:127', $html, 'fotos: foto existente entra com o ID' );
ok( false === strpos( $html, '999' ), 'fotos: ID apagado da Biblioteca não entra na tela' );
contem( 'data-titulo="Escolher fotos do destino"', $html, 'fotos: texto da janela de mídia' );

/* ---------------------------------------------------------------- salvar */
$_POST = array( 'tt_destinos' => array( 'quando_ir' => '<p>Verão</p>', 'o_que_fazer' => array( 'A', 'B' ), 'galeria' => '[{"id":127},{"id":128}]' ) );
igual( array(), tt_destino_salvar( 5 ), 'salvar: sem nonce, não grava' );
$_POST['tt_destinos_nonce'] = 'x';
$GLOBALS['tt_pode']         = false;
igual( array(), tt_destino_salvar( 5 ), 'salvar: sem permissão, não grava' );
$GLOBALS['tt_pode'] = true;
igual( array( 'quando_ir', 'o_que_fazer', 'galeria' ), tt_destino_salvar( 5 ), 'salvar: grava o que veio' );
igual( "A\nB", $GLOBALS['tt_meta'][5]['o_que_fazer'], 'salvar: lista, um por linha' );
igual( '127,128', $GLOBALS['tt_meta'][5]['galeria'], 'salvar: galeria em IDs' );
igual( '<p>Abertura</p>', $GLOBALS['tt_meta'][5]['texto_de_abertura'], 'salvar: campo que não veio fica como estava' );
$_POST['tt_destinos'] = array( 'o_que_fazer_presente' => '1' );
tt_destino_salvar( 5 );
igual( '', $GLOBALS['tt_meta'][5]['o_que_fazer'], 'salvar: todos os itens removidos esvaziam a lista' );
ok( isset( $GLOBALS['tt_acoes']['save_post_destinos'] ), 'salvar: ligado ao save_post_destinos' );

/* ---------------------------------------------------------------- colunas da lista */
igual( array( 'cb', 'tt_capa', 'title', 'tt_resumo', 'date' ), array_keys( tt_destino_colunas( array( 'cb' => '', 'title' => 'Título', 'date' => 'Data' ) ) ), 'colunas: Capa antes do título, Resumo depois' );
ob_start();
tt_destino_coluna_valor( 'tt_capa', 5 );
tt_destino_coluna_valor( 'tt_resumo', 5 );
tt_destino_coluna_valor( 'tt_resumo', 6 );
$col = ob_get_clean();
igual( '<img src="capa.jpg">Arquipélago protegido—', $col, 'colunas: capa, resumo em texto simples e traço quando vazio' );

/* ================================================================ v2 (2.3.0): campos novos na tela */
$GLOBALS['tt_anexos'][201] = 'https://x.test/torre.jpg';
$GLOBALS['tt_meta'][7]     = array(
	'escopo'            => 'nacional',
	'subchamada'        => 'Natureza "pura"',
	'perfis'            => "praia\nnatureza",
	'duracao_ideal'     => '4 dias',
	'clima'             => '[{"estacao":"inverno","meses":"jun–ago","max":26,"min":-2,"chuva_mm":40}]',
	'documentacao'      => '<p>Texto padrão</p>',
	'pontos_turisticos' => '[{"nome":"Baía do Sancho","descricao":"Praia","foto":201},{"nome":"Forte","descricao":"","foto":999}]',
	'faq'               => '[{"pergunta":"Quando ir?","resposta":"Setembro"}]',
	'atualizado_em'     => '2026-10-14',
);
ob_start();
tt_destino_desenhar_caixa( (object) array( 'ID' => 7, 'post_excerpt' => '' ) );
$h = ob_get_clean();
contem( '<option value="nacional" selected="selected">', $h, 'resumo: escopo marcado' );
contem( 'data-tt-escopo', $h, 'resumo: seletor de escopo ligado ao JS (esconde os campos de internacional)' );
contem( 'name="tt_destinos[subchamada]" value="Natureza &quot;pura&quot;" maxlength="160" data-tt-contador="160"', $h, 'resumo: subchamada com limite e contador' );
contem( 'name="tt_destinos[perfis][]" value="praia" checked', $h, 'resumo: perfil marcado' );
contem( 'name="tt_destinos[perfis][]" value="neve">', $h, 'resumo: perfil não marcado' );
contem( '<input type="hidden" name="tt_destinos[perfis_presente]" value="1">', $h, 'resumo: marca de presença dos perfis' );
contem( 'name="tt_destinos[duracao_ideal]" value="4 dias" maxlength="60"', $h, 'resumo: duração ideal' );
contem( 'type="date" id="tt_dest_atualizado_em" name="tt_destinos[atualizado_em]" value="2026-10-14"', $h, 'resumo: atualizado em' );
contem( 'name="tt_destinos[clima][2][estacao]" value="inverno"', $h, 'práticas: inverno é a 3ª linha do clima' );
contem( 'name="tt_destinos[clima][2][min]" value="-2"', $h, 'práticas: mínima do inverno' );
contem( 'name="tt_destinos[clima][0][max]" value=""', $h, 'práticas: verão sem dado, campo vazio' );
contem( '<div class="tt-dest-inter" data-tt-so-inter hidden>', $h, 'práticas: nacional esconde documentação, moeda, idioma e fuso' );
contem( '<div class="tt-dest-doc"><p>Texto padrão</p></div>', $h, 'práticas: documentação só para ler (texto do sistema)' );
ok( false === strpos( $h, 'name="tt_destinos[documentacao]"' ), 'práticas: documentação não é enviada pela tela (não é editada no site)' );
igual( 8, substr_count( $h, 'name="tt_destinos[pontos_turisticos][' ) / 3, 'o que fazer: 8 linhas de pontos turísticos' );
igual( 6, substr_count( $h, '][titulo]"' ), 'o que fazer: 6 linhas de passeios' );
contem( 'name="tt_destinos[pontos_turisticos][0][nome]" value="Baía do Sancho" maxlength="80"', $h, 'o que fazer: nome do ponto com limite' );
contem( '<img src="https://x.test/torre.jpg" alt="" data-tt-foto-previa><input type="hidden" name="tt_destinos[pontos_turisticos][0][foto]" value="201" data-tt-foto-id>', $h, 'o que fazer: foto do ponto com miniatura' );
contem( 'name="tt_destinos[pontos_turisticos][1][foto]" value="" data-tt-foto-id', $h, 'o que fazer: foto apagada da Biblioteca sai vazia' );
ok( false === strpos( $h, 'tt_destinos[o_que_fazer]' ), 'o que fazer: sem a lista simples da v1 quando o destino não tem' );
igual( 5, substr_count( $h, '][bairro]"' ), 'onde ficar: 5 linhas' );
igual( 8, substr_count( $h, '][pergunta]"' ), 'faq: 8 linhas' );
contem( 'name="tt_destinos[faq][0][resposta]" maxlength="600">Setembro</textarea>', $h, 'faq: resposta com limite' );

$_POST = array( 'tt_destinos_nonce' => 'x', 'tt_destinos' => array(
	'escopo'            => 'nacional',
	'perfis_presente'   => '1',
	'pontos_turisticos' => array( array( 'nome' => 'Baía', 'descricao' => '', 'foto' => '201' ), array( 'nome' => '', 'descricao' => '', 'foto' => '' ) ),
	'faq'               => array( array( 'pergunta' => 'P?', 'resposta' => '' ) ),
) );
tt_destino_salvar( 7 );
igual( '[{"nome":"Baía","descricao":"","foto":201}]', $GLOBALS['tt_meta'][7]['pontos_turisticos'], 'salvar: pontos turísticos em JSON, linha vazia fora' );
igual( '', $GLOBALS['tt_meta'][7]['perfis'], 'salvar: nenhum perfil marcado esvazia os perfis' );
igual( '', $GLOBALS['tt_meta'][7]['faq'], 'salvar: pergunta sem resposta não grava' );
igual( '<p>Texto padrão</p>', $GLOBALS['tt_meta'][7]['documentacao'], 'salvar: documentação não é tocada pela tela' );
