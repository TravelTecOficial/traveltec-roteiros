<?php
// Dados do destino — includes/destino-dados.php (funções puras)
require __DIR__ . '/bootstrap.php';
require '/p/includes/roteiro-dados.php';
require '/p/includes/destino-dados.php';

/* ---------------------------------------------------------------- campos e abas */
igual(
	array( 'versao', 'escopo', 'subchamada', 'texto_de_abertura', 'perfis', 'duracao_ideal', 'quando_ir', 'clima', 'como_chegar', 'documentacao', 'moeda', 'idioma', 'fuso', 'pontos_turisticos', 'passeios', 'gastronomia', 'onde_ficar', 'faq', 'atualizado_em', 'o_que_fazer', 'galeria' ),
	array_keys( tt_destinos_campos() ),
	'campos do destino: os do contrato v2 e os da v1'
);
igual( array( 'resumo', 'praticas', 'fazer', 'ficar', 'faq', 'fotos' ), array_keys( tt_destino_abas() ), 'abas do destino' );
$abas = tt_destino_abas();
ok( tt_roteiro_aba_preenchida( $abas['resumo'][1], array( 'resumo' => 'Ilha', 'texto_de_abertura' => '' ) ), 'aba Resumo conta o resumo curto' );
ok( ! tt_roteiro_aba_preenchida( $abas['fotos'][1], array( 'galeria' => '' ) ), 'aba Fotos vazia sem IDs' );
ok( tt_roteiro_aba_preenchida( $abas['fotos'][1], array( 'galeria' => '12,13' ) ), 'aba Fotos preenchida com IDs' );

/* ---------------------------------------------------------------- lista "um por linha" */
igual( array( 'Passeio de barco', 'Trilha do Atalaia' ), tt_destino_lista_ler( "Passeio de barco\r\n\r\n  Trilha do Atalaia  \n" ), 'lista: quebra por linha, tira espaços e linhas vazias' );
igual( array( 'Mergulho', 'Pôr do sol' ), tt_destino_lista_ler( '<ul><li>Mergulho</li><li><strong>Pôr do sol</strong></li></ul>' ), 'lista: HTML colado vira itens de texto' );
igual( array( 'Praia', 'Forte' ), tt_destino_lista_ler( "- Praia\n• Forte" ), 'lista: tira o marcador digitado à mão' );
igual( array( 'A', 'B resto' ), tt_destino_lista_ler( array( ' A ', '', array( 'x' ), "B\nresto" ) ), 'lista: aceita array; ignora vazio e não escalar; item fica numa linha só' );
igual( array(), tt_destino_lista_ler( '' ), 'lista vazia' );
igual( "A\nB resto", tt_destino_lista_montar( array( ' A ', '', "B\nresto" ) ), 'montar: uma linha por item' );
igual( '', tt_destino_lista_montar( array() ), 'montar: sem itens, texto vazio' );

/* ---------------------------------------------------------------- galeria (IDs) */
igual( array( 12, 13, 7 ), tt_destino_galeria_ids( '12, 13;7' ), 'galeria: separa por vírgula, ponto e vírgula ou espaço' );
igual( array( 12, 7 ), tt_destino_galeria_ids( '12,abc,0,-3,12,7,1.5' ), 'galeria: ignora texto, zero, negativo, decimal e repetido' );
igual( array( 5, 9 ), tt_destino_galeria_ids( array( 5, '9', null, array( 'id' => 5 ) ) ), 'galeria: aceita array de números' );
igual( array( 127, 128 ), tt_destino_galeria_ids( '[{"id":127,"url":"a.jpg","alt":""},{"id":"128","url":"b.jpg"},{"url":"sem-id.jpg"}]' ), 'galeria: aceita o JSON da tela (objetos com id)' );
igual( array(), tt_destino_galeria_ids( '[quebrado' ), 'galeria: JSON quebrado vira lista vazia' );
igual( array(), tt_destino_galeria_ids( '' ), 'galeria vazia' );
igual( '127,128', tt_destino_galeria_montar( array( 127, 128, 127 ) ), 'montar galeria: IDs separados por vírgula' );

/* ---------------------------------------------------------------- limpar por tipo de campo */
igual( "A\nB", tt_destino_limpar( 'o_que_fazer', '<li>A</li><li>B</li>' ), 'limpar: lista' );
igual( '3,4', tt_destino_limpar( 'galeria', array( 3, 4 ) ), 'limpar: galeria' );
igual( '<p>Texto</p>', tt_destino_limpar( 'quando_ir', '<p>Texto</p>' ), 'limpar: HTML passa pelo wp_kses_post' );
igual( '', tt_destino_limpar( 'nao_existe', 'x' ), 'limpar: chave desconhecida não grava nada' );

/* ---------------------------------------------------------------- o que gravar a partir do formulário */
igual( array(), tt_destino_valores_a_gravar( null ), 'gravar: sem dados, nada' );
igual(
	array( 'texto_de_abertura' => '<p>Oi</p>', 'quando_ir' => '<p>Verão</p>', 'o_que_fazer' => "A\nB", 'galeria' => '1,2' ), // na ordem de tt_destinos_campos()
	tt_destino_valores_a_gravar( array( 'texto_de_abertura' => '<p>Oi</p>', 'quando_ir' => '<p>Verão</p>', 'o_que_fazer' => array( 'A', '', 'B' ), 'galeria' => '[{"id":1},{"id":2}]', 'intruso' => 'x' ) ),
	'gravar: os quatro campos da v1, sem chaves estranhas'
);
igual( array( 'o_que_fazer' => '' ), tt_destino_valores_a_gravar( array( 'o_que_fazer_presente' => '1' ) ), 'gravar: lista com todos os itens removidos grava vazio' );
igual( array( 'quando_ir' => '' ), tt_destino_valores_a_gravar( array( 'quando_ir' => array( 'x' ) ) ), 'gravar: valor não escalar vira vazio, não "Array"' );

/* ---------------------------------------------------------------- HTML para o site */
igual( '', tt_destino_texto_html( '', 'Quando ir' ), 'texto vazio: nada, nem o título' );
igual( '', tt_destino_texto_html( '<p>nao informado</p>', 'Quando ir' ), 'texto "nao informado" conta como vazio' );
igual( '<h2 class="tt-destino-titulo">Quando ir</h2><div class="tt-destino-texto"><p>Verão</p></div>', tt_destino_texto_html( '<p>Verão</p>', 'Quando ir' ), 'texto com título' );
igual( '<div class="tt-destino-texto"><p>Abertura</p></div>', tt_destino_texto_html( '<p>Abertura</p>' ), 'texto sem título' );
igual( '', tt_destino_lista_html( " \n ", 'O que fazer' ), 'lista vazia: nada' );
igual( '<h2 class="tt-destino-titulo">O que fazer</h2><ul class="tt-destino-lista"><li>A &amp; B</li><li>C</li></ul>', tt_destino_lista_html( "A & B\nC", 'O que fazer' ), 'lista: itens escapados' );
igual( '', tt_destino_galeria_html( array(), 'g1', 'Galeria' ), 'galeria vazia: nada' );
$g = tt_destino_galeria_html( array( array( 'grande' => 'https://x.test/a.jpg', 'mini' => 'https://x.test/a-768.jpg', 'alt' => 'Baía "do" Sancho' ), array( 'grande' => '', 'mini' => 'x' ) ), 'tt-destino-9', 'Galeria' );
contem( '<h2 class="tt-destino-titulo">Galeria</h2><div class="tt-destino-galeria">', $g, 'galeria: título e grade' );
contem( '<a href="https://x.test/a.jpg" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="tt-destino-9">', $g, 'galeria: link abre a foto grande na ampliação do Elementor' );
contem( '<img src="https://x.test/a-768.jpg" alt="Baía &quot;do&quot; Sancho" loading="lazy">', $g, 'galeria: miniatura com alt escapado' );
igual( 1, substr_count( $g, '<img' ), 'galeria: foto sem endereço é ignorada' );

/* ================================================================ v2 (2.3.0) — contrato dos campos */

/* ---------------------------------------------------------------- campos simples */
igual( 'nacional', tt_destino_limpar( 'escopo', ' Nacional ' ), 'escopo: nacional' );
igual( 'internacional', tt_destino_limpar( 'escopo', 'internacional' ), 'escopo: internacional' );
igual( '', tt_destino_limpar( 'escopo', 'europa' ), 'escopo: fora da lista vira vazio' );
igual( '2', tt_destino_limpar( 'versao', 2 ), 'versão: número' );
igual( '2', tt_destino_limpar( 'versao', '2' ), 'versão: texto' );
igual( '', tt_destino_limpar( 'versao', 'x' ), 'versão inválida: vazio (destino v1)' );
igual( 2, tt_destino_valor_rest( 'versao', '2' ), 'versão na leitura: inteiro' );
igual( null, tt_destino_valor_rest( 'versao', '' ), 'versão ausente na leitura: null' );
$longa = str_repeat( 'á', 170 );
igual( 160, preg_match_all( '/./us', tt_destino_limpar( 'subchamada', $longa ) ), 'subchamada: corta em 160 caracteres (UTF-8)' );
igual( 'Praias <b> & sol', tt_destino_limpar( 'subchamada', '<p>Praias &lt;b&gt; &amp; sol</p>' ), 'subchamada: texto sem HTML' );
igual( '5 a 7 dias', tt_destino_limpar( 'duracao_ideal', ' 5 a 7  dias ' ), 'duração: espaços normalizados' );
igual( 60, strlen( tt_destino_limpar( 'fuso', str_repeat( 'x', 80 ) ) ), 'fuso: até 60' );
igual( '2026-10-14', tt_destino_limpar( 'atualizado_em', '2026-10-14' ), 'data: AAAA-MM-DD' );
igual( '2026-10-14', tt_destino_limpar( 'atualizado_em', '2026-10-14T10:00:00Z' ), 'data: aceita data e hora (fica só a data)' );
igual( '', tt_destino_limpar( 'atualizado_em', '2026-02-30' ), 'data impossível: vazio' );
igual( '', tt_destino_limpar( 'atualizado_em', '14/10/2026' ), 'data em outro formato: vazio' );

/* ---------------------------------------------------------------- perfis */
igual( "praia\nfamilia\nromantico", tt_destino_limpar( 'perfis', array( 'praia', 'Família', 'praia', 'luxo', 'romântico', 'neve' ) ), 'perfis: só os da lista, sem repetir, no máximo 3' );
igual( array( 'cultura', 'gastronomia' ), tt_destino_valor_rest( 'perfis', "cultura\ngastronomia" ), 'perfis na leitura: array' );
igual( array( 'neve' ), tt_destino_perfis_ler( 'neve, xpto' ), 'perfis: aceita texto separado por vírgula' );

/* ---------------------------------------------------------------- listas JSON */
$pontos = array();
for ( $i = 1; $i <= 10; $i++ ) {
	$pontos[] = array( 'nome' => "Ponto $i", 'descricao' => 'Linha', 'foto' => $i % 2 ? $i : null );
}
$pontos[] = array( 'nome' => '', 'descricao' => 'sem nome' );
$gravado  = tt_destino_limpar( 'pontos_turisticos', $pontos );
$lido     = json_decode( $gravado, true );
igual( 8, count( $lido ), 'pontos: no máximo 8' );
igual( array( 'nome' => 'Ponto 1', 'descricao' => 'Linha', 'foto' => 1 ), $lido[0], 'pontos: item com nome, descrição e foto (id)' );
igual( null, $lido[1]['foto'], 'pontos: sem foto = null' );
igual( $lido, tt_destino_valor_rest( 'pontos_turisticos', $gravado ), 'pontos: leitura devolve o array gravado' );
igual( $gravado, tt_destino_limpar( 'pontos_turisticos', $gravado ), 'limpar de novo o JSON gravado não muda nada (o WordPress passa pelo sanitize ao gravar)' );
$p = tt_destino_json_ler( 'pontos_turisticos', array( array( 'nome' => str_repeat( 'n', 90 ), 'descricao' => str_repeat( 'd', 200 ), 'foto' => 'abc' ), 'texto solto', array( 'descricao' => 'sem nome' ) ) );
igual( 1, count( $p ), 'pontos: item sem nome ou que não é objeto sai' );
igual( 80, strlen( $p[0]['nome'] ), 'pontos: nome até 80' );
igual( 160, strlen( $p[0]['descricao'] ), 'pontos: descrição até 160' );
igual( null, $p[0]['foto'], 'pontos: foto inválida = null' );
$barra = chr( 92 );
$aspas = tt_destino_limpar( 'faq', array( array( 'pergunta' => 'Precisa de "visto"?', 'resposta' => 'Não. Barra ' . $barra . ' fica.' ) ) );
igual( array( array( 'pergunta' => 'Precisa de "visto"?', 'resposta' => 'Não. Barra ' . $barra . ' fica.' ) ), json_decode( $aspas, true ), 'faq: aspas e barra invertida sobrevivem no JSON' );
contem( 'Não', $aspas, 'json gravado com acento legível' );
igual( '', tt_destino_limpar( 'faq', array( array( 'pergunta' => 'Só pergunta?', 'resposta' => '' ) ) ), 'faq: pergunta sem resposta sai; lista vazia grava vazio' );
igual( 8, count( tt_destino_json_ler( 'faq', array_fill( 0, 9, array( 'pergunta' => 'P', 'resposta' => 'R' ) ) ) ), 'faq: no máximo 8' );
igual( 6, count( tt_destino_json_ler( 'passeios', array_fill( 0, 7, array( 'titulo' => 'T', 'duracao' => '3 h' ) ) ) ), 'passeios: no máximo 6' );
$ps = tt_destino_json_ler( 'passeios', array( array( 'titulo' => 'T', 'duracao' => str_repeat( 'x', 50 ) ) ) );
igual( 40, strlen( $ps[0]['duracao'] ), 'passeios: duração até 40' );
igual( 5, count( tt_destino_json_ler( 'onde_ficar', array_fill( 0, 6, array( 'bairro' => 'B', 'descricao' => 'D', 'perfil' => 'P' ) ) ) ), 'onde ficar: no máximo 5' );
igual( 6, count( tt_destino_json_ler( 'gastronomia', array_fill( 0, 7, array( 'nome' => 'Prato' ) ) ) ), 'gastronomia: no máximo 6' );
$gs = tt_destino_json_ler( 'gastronomia', array( array( 'nome' => 'Prato' ) ) );
igual( array( 'nome' => 'Prato', 'descricao' => '' ), $gs[0], 'gastronomia: sub-campo ausente vira texto vazio' );
igual( array(), tt_destino_valor_rest( 'faq', '' ), 'lista vazia na leitura: array vazio' );
igual( array(), tt_destino_json_ler( 'faq', '{quebrado' ), 'JSON quebrado: lista vazia' );
igual( '', tt_destino_limpar( 'clima', 'nada' ), 'clima: texto que não é lista grava vazio' );

/* ---------------------------------------------------------------- clima */
$clima = tt_destino_json_ler( 'clima', array(
	array( 'estacao' => 'inverno', 'meses' => 'jun–ago', 'max' => '14.6', 'min' => 8, 'chuva_mm' => 300 ),
	array( 'estacao' => 'verao', 'meses' => 'dez–fev', 'max' => 28, 'min' => 18, 'chuva_mm' => -5 ),
	array( 'estacao' => 'inverno', 'max' => 99 ),
	array( 'estacao' => 'monção', 'max' => 30 ),
	array( 'estacao' => 'outono' ),
) );
igual( array( 'verao', 'inverno' ), array_column( $clima, 'estacao' ), 'clima: na ordem do ano, uma por estação, estação inválida ou sem dado sai' );
igual( 15, $clima[1]['max'], 'clima: temperatura arredondada para inteiro' );
igual( null, $clima[0]['chuva_mm'], 'clima: chuva negativa vira null' );
$cm = tt_destino_json_ler( 'clima', array( array( 'estacao' => 'inverno', 'min' => '-3' ) ) );
igual( -3, $cm[0]['min'], 'clima: mínima negativa vale' );

/* ---------------------------------------------------------------- REST: listas da v1 voltam como array */
igual( array( 'A', 'B' ), tt_destino_valor_rest( 'o_que_fazer', "A\nB" ), 'o que fazer (v1) na leitura: array' );
igual( array( 12, 13 ), tt_destino_valor_rest( 'galeria', '12,13' ), 'galeria na leitura: array de ids' );
igual( '<p>Oi</p>', tt_destino_valor_rest( 'texto_de_abertura', '<p>Oi</p>' ), 'texto na leitura: como gravado' );
igual( null, tt_destino_valor_rest( 'nao_existe', 'x' ), 'chave desconhecida na leitura: null' );

/* ---------------------------------------------------------------- formulário da tela (v2) */
$v = tt_destino_valores_a_gravar( array(
	'escopo'            => 'internacional',
	'perfis'            => array( 'praia' ),
	'clima'             => array( array( 'estacao' => 'verao', 'meses' => '', 'max' => '30', 'min' => '', 'chuva_mm' => '' ), array( 'estacao' => 'outono', 'meses' => '', 'max' => '', 'min' => '', 'chuva_mm' => '' ) ),
	'pontos_turisticos' => array( array( 'nome' => 'Torre', 'descricao' => '', 'foto' => '' ), array( 'nome' => '', 'descricao' => '', 'foto' => '' ) ),
) );
igual( 'internacional', $v['escopo'], 'tela: escopo' );
igual( 'praia', $v['perfis'], 'tela: perfis' );
igual( '[{"estacao":"verao","meses":"","max":30,"min":null,"chuva_mm":null}]', $v['clima'], 'tela: estação em branco não grava' );
igual( '[{"nome":"Torre","descricao":"","foto":null}]', $v['pontos_turisticos'], 'tela: linha em branco não grava' );
igual( array( 'perfis' => '' ), tt_destino_valores_a_gravar( array( 'perfis_presente' => '1' ) ), 'tela: nenhum perfil marcado esvazia os perfis' );

/* ---------------------------------------------------------------- HTML das seções v2 */
igual( '', tt_destino_secao_html( 'x', 'Título', array( '', '' ) ), 'seção sem nada: nada, nem o título' );
igual( '<section class="tt-destino-secao tt-destino-secao--faq"><h2 class="tt-destino-titulo">FAQ</h2>A</section>', tt_destino_secao_html( 'faq', 'FAQ', array( '', 'A' ) ), 'seção com conteúdo: título + partes' );
igual( '', tt_destino_subchamada_html( '' ), 'subchamada vazia: nada' );
igual( '<p class="tt-destino-subchamada tt-destino-subchamada--card">Sol &amp; mar</p>', tt_destino_subchamada_html( 'Sol & mar', 'card' ), 'subchamada no card' );
$d = tt_destino_destaques_html( "praia\ncultura", '5 a 7 dias' );
contem( '<li class="tt-destino-perfil">Praia</li><li class="tt-destino-perfil">Cultura</li>', $d, 'destaques: perfis com rótulo' );
contem( '<strong class="tt-destino-destaque__valor">5 a 7 dias</strong>', $d, 'destaques: duração' );
igual( '', tt_destino_destaques_html( '', '' ), 'destaques vazios: nada' );
$c = tt_destino_clima_html( '[{"estacao":"verao","meses":"dez–fev","max":28,"min":18,"chuva_mm":120}]', 'Clima por estação' );
contem( '<h3 class="tt-destino-subtitulo">Clima por estação</h3><div class="tt-destino-clima">', $c, 'clima: subtítulo e grade' );
contem( '<h4 class="tt-destino-clima__estacao">Verão</h4><p class="tt-destino-clima__meses">dez–fev</p>', $c, 'clima: estação e meses' );
contem( 'máx <strong>28 °C</strong>', $c, 'clima: máxima' );
contem( 'chuva <strong>120 mm</strong>', $c, 'clima: chuva' );
igual( '', tt_destino_clima_html( '' ), 'clima vazio: nada' );
igual( '', tt_destino_info_html( '', '', '' ), 'moeda/idioma/fuso vazios: nada' );
contem( '<span class="tt-destino-info__rotulo">Moeda</span><strong>Euro (EUR)</strong>', tt_destino_info_html( 'Euro (EUR)', '', '' ), 'moeda' );
$cards = tt_destino_cards_html( array( array( 'titulo' => 'Barco', 'duracao' => '3 h', 'foto_url' => 'https://x.test/b.jpg' ), array( 'titulo' => 'Sem foto', 'duracao' => '' ) ), 'titulo', 'duracao', 'Passeios', 'Foto: Viator' );
contem( '<figure class="tt-destino-card__foto"><img src="https://x.test/b.jpg" alt="Barco" loading="lazy"><figcaption>Foto: Viator</figcaption></figure>', $cards, 'cards: foto com crédito' );
contem( '<article class="tt-destino-card tt-destino-card--sem-foto"><div class="tt-destino-card__corpo"><h4>Sem foto</h4></div></article>', $cards, 'cards: sem foto, só o texto' );
contem( '<p class="tt-destino-atualizado">Atualizado em 14/10/2026</p>', tt_destino_atualizado_html( '2026-10-14' ), 'atualizado em dd/mm/aaaa' );
igual( '', tt_destino_atualizado_html( '' ), 'sem data: nada' );
contem( '<li><strong>Pastel de nata</strong><span>Doce de massa folhada</span></li>', tt_destino_gastronomia_html( array( array( 'nome' => 'Pastel de nata', 'descricao' => 'Doce de massa folhada' ) ) ), 'gastronomia' );
contem( '<article class="tt-destino-bairro"><h3>Alfama</h3><span class="tt-destino-bairro__perfil">Casais</span><p>Bairro antigo</p></article>', tt_destino_onde_ficar_html( array( array( 'bairro' => 'Alfama', 'perfil' => 'Casais', 'descricao' => 'Bairro antigo' ) ) ), 'onde ficar' );
$faq = array( array( 'pergunta' => 'Precisa de visto?', 'resposta' => 'Não, até 90 dias. &lt;/script&gt;' ) );
contem( '<details class="tt-destino-faq__item"><summary>Precisa de visto?</summary>', tt_destino_faq_html( $faq ), 'faq: pergunta que abre' );
$ld = tt_destino_faq_jsonld( $faq );
contem( '<script type="application/ld+json" class="tt-destino-faq-schema">', $ld, 'faq: JSON-LD' );
contem( '"@type":"FAQPage"', $ld, 'faq: FAQPage' );
contem( '"name":"Precisa de visto?"', $ld, 'faq: pergunta no JSON-LD' );
igual( 1, substr_count( $ld, '</script>' ), 'faq: texto com </script> não fecha a tag antes da hora' );
igual( '', tt_destino_faq_jsonld( '' ), 'sem FAQ: sem JSON-LD' );

/* ---------------------------------------------------------------- Viator: link de afiliado (2.4.0) */
igual( 'https://www.viator.com/pt-BR/tours/Lisboa/Barco/d538-123P1', tt_destino_viator_url( ' https://www.viator.com/pt-BR/tours/Lisboa/Barco/d538-123P1?pid=P00099999&mcid=42383&medium=api#x ' ), 'viator: endereço sem parâmetros (tira o pid da TravelTec)' );
igual( 'https://viator.com/tours/x', tt_destino_viator_url( 'https://viator.com/tours/x' ), 'viator: domínio sem www' );
igual( '', tt_destino_viator_url( 'http://www.viator.com/tours/x' ), 'viator: só https' );
igual( '', tt_destino_viator_url( 'https://www.viator.com.golpe.test/tours/x' ), 'viator: outro site com viator.com no meio' );
igual( '', tt_destino_viator_url( 'https://fakeviator.com/x' ), 'viator: domínio parecido' );
igual( '', tt_destino_viator_url( array( 'x' ) ), 'viator: não texto' );
$ps = tt_destino_json_ler( 'passeios', array( array( 'titulo' => 'T', 'url' => 'https://www.viator.com/tours/a?pid=P1' ), array( 'titulo' => 'U', 'url' => 'javascript:alert(1)' ) ) );
igual( 'https://www.viator.com/tours/a', $ps[0]['url'], 'passeios: url limpa na leitura' );
igual( '', $ps[1]['url'], 'passeios: url de outro site some' );
igual( 'P00123456', tt_destino_viator_pid_limpo( ' p00 123456 ' ), 'pid: maiúscula e sem espaço' );
igual( '', tt_destino_viator_pid_limpo( 'P00"><script>' ), 'pid: formato inválido' );
igual( '', tt_destino_viator_link( 'https://www.viator.com/tours/a', '' ), 'link: sem código da agência, sem link' );
igual( '', tt_destino_viator_link( '', 'P00123456' ), 'link: sem endereço, sem link' );
igual( 'https://www.viator.com/tours/a?pid=P00123456&mcid=42383&medium=link', tt_destino_viator_link( 'https://www.viator.com/tours/a', 'P00123456' ), 'link: pid, mcid e medium' );
$cl = tt_destino_cards_html( tt_destino_passeios_com_link( array( array( 'titulo' => 'Barco', 'duracao' => '3 h', 'url' => 'https://www.viator.com/tours/a' ), array( 'titulo' => 'Trem', 'duracao' => '', 'url' => '' ) ), 'P00123456' ), 'titulo', 'duracao', 'Passeios' );
contem( '<a class="tt-destino-card__link" href="https://www.viator.com/tours/a?pid=P00123456&amp;mcid=42383&amp;medium=link" target="_blank" rel="sponsored noopener">Ver passeio</a>', $cl, 'cards: botão Ver passeio com o link de afiliado' );
igual( 1, substr_count( $cl, 'tt-destino-card__link' ), 'cards: passeio sem endereço fica sem botão' );
ok( false === strpos( tt_destino_cards_html( tt_destino_passeios_com_link( array( array( 'titulo' => 'Barco', 'url' => 'https://www.viator.com/tours/a' ) ), '' ), 'titulo', 'duracao' ), 'Ver passeio' ), 'cards: sem código da agência, nenhum botão' );
