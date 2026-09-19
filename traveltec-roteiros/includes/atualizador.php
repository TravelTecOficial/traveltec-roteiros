<?php
/**
 * Atualização automática pelo GitHub.
 *
 * O site do cliente pergunta ao GitHub qual é a última versão publicada (release) e,
 * se for maior que a instalada, o WordPress mostra "Atualizar agora" na tela de Plugins
 * como faz com qualquer plugin do repositório oficial.
 *
 * A release precisa ter um anexo chamado exatamente traveltec-roteiros.zip, com a pasta
 * traveltec-roteiros/ dentro — é o que o empacotar.py gera. O zipball automático do GitHub
 * não serve: ele vem com o nome do repositório e da tag na pasta raiz.
 *
 * Repositório privado: basta definir no wp-config.php do site
 *     define( 'TT_ROTEIROS_GITHUB_TOKEN', 'ghp_...' );
 * com um token de leitura. Em repositório público não precisa de nada.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TT_ROTEIROS_REPO', 'TravelTecOficial/traveltec-roteiros' );
define( 'TT_ROTEIROS_ANEXO', 'traveltec-roteiros.zip' );
define( 'TT_ROTEIROS_CACHE', 'tt_roteiros_release' );

/** Token opcional, só para repositório privado. */
function tt_roteiros_token() {
	return defined( 'TT_ROTEIROS_GITHUB_TOKEN' ) && TT_ROTEIROS_GITHUB_TOKEN ? TT_ROTEIROS_GITHUB_TOKEN : '';
}

/**
 * Última release publicada, em cache por 6 horas.
 * Devolve array vazio quando não dá para consultar (sem internet, limite do GitHub, repo privado sem token).
 */
function tt_roteiros_release( $forcar = false ) {
	if ( ! $forcar ) {
		$cache = get_site_transient( TT_ROTEIROS_CACHE );
		if ( is_array( $cache ) ) {
			return $cache;
		}
	}

	$cabecalhos = array(
		'Accept'     => 'application/vnd.github+json',
		'User-Agent' => 'TravelTec-Roteiros/' . TT_ROTEIROS_VERSION,
	);
	if ( tt_roteiros_token() ) {
		$cabecalhos['Authorization'] = 'Bearer ' . tt_roteiros_token();
	}

	$resposta = wp_remote_get(
		'https://api.github.com/repos/' . TT_ROTEIROS_REPO . '/releases/latest',
		array( 'timeout' => 15, 'headers' => $cabecalhos )
	);

	// Falhou: guarda vazio por 1h para não bater no GitHub a cada tela do painel.
	if ( is_wp_error( $resposta ) || 200 !== (int) wp_remote_retrieve_response_code( $resposta ) ) {
		set_site_transient( TT_ROTEIROS_CACHE, array(), HOUR_IN_SECONDS );
		return array();
	}

	$dados = json_decode( wp_remote_retrieve_body( $resposta ), true );
	$zip   = '';
	foreach ( (array) ( isset( $dados['assets'] ) ? $dados['assets'] : array() ) as $anexo ) {
		if ( isset( $anexo['name'] ) && TT_ROTEIROS_ANEXO === $anexo['name'] ) {
			// Em repo privado o download é pela API, com token; em público, pela URL direta.
			$zip = tt_roteiros_token() ? $anexo['url'] : $anexo['browser_download_url'];
			break;
		}
	}
	if ( empty( $dados['tag_name'] ) || ! $zip ) {
		set_site_transient( TT_ROTEIROS_CACHE, array(), HOUR_IN_SECONDS );
		return array();
	}

	$release = array(
		'versao' => ltrim( (string) $dados['tag_name'], 'vV' ),
		'zip'    => $zip,
		'notas'  => isset( $dados['body'] ) ? (string) $dados['body'] : '',
		'data'   => isset( $dados['published_at'] ) ? (string) $dados['published_at'] : '',
	);
	set_site_transient( TT_ROTEIROS_CACHE, $release, 6 * HOUR_IN_SECONDS );
	return $release;
}

/** Download do anexo em repositório privado: a API exige token e Accept de arquivo binário. */
add_filter( 'http_request_args', function ( $args, $url ) {
	$api = 'https://api.github.com/repos/' . TT_ROTEIROS_REPO . '/releases/assets/';
	if ( tt_roteiros_token() && 0 === strpos( $url, $api ) ) {
		$args['headers']['Authorization'] = 'Bearer ' . tt_roteiros_token();
		$args['headers']['Accept']        = 'application/octet-stream';
	}
	return $args;
}, 10, 2 );

/** Ficha do plugin, com as notas da release. */
function tt_roteiros_ficha( $release ) {
	return (object) array(
		'id'             => TT_ROTEIROS_REPO,
		'slug'           => 'traveltec-roteiros',
		'plugin'         => TT_ROTEIROS_BASENAME,
		'new_version'    => $release['versao'],
		'version'        => $release['versao'],
		'url'            => 'https://github.com/' . TT_ROTEIROS_REPO,
		'package'        => $release['zip'],
		'requires_php'   => '7.4',
		'icons'          => array(),
		'banners'        => array(),
		'banners_rtl'    => array(),
		'compatibility'  => new stdClass(),
	);
}

/** É aqui que o WordPress descobre que existe versão nova. */
add_filter( 'pre_set_site_transient_update_plugins', function ( $transiente ) {
	if ( ! is_object( $transiente ) ) {
		return $transiente;
	}
	$release = tt_roteiros_release();
	if ( empty( $release['versao'] ) ) {
		return $transiente;
	}
	$ficha = tt_roteiros_ficha( $release );
	if ( version_compare( $release['versao'], TT_ROTEIROS_VERSION, '>' ) ) {
		$transiente->response[ TT_ROTEIROS_BASENAME ] = $ficha;
		unset( $transiente->no_update[ TT_ROTEIROS_BASENAME ] );
	} else {
		$transiente->no_update[ TT_ROTEIROS_BASENAME ] = $ficha;
	}
	return $transiente;
} );

/** Janela "Ver detalhes da versão". */
add_filter( 'plugins_api', function ( $resultado, $acao, $args ) {
	if ( 'plugin_information' !== $acao || empty( $args->slug ) || 'traveltec-roteiros' !== $args->slug ) {
		return $resultado;
	}
	$release = tt_roteiros_release();
	if ( empty( $release['versao'] ) ) {
		return $resultado;
	}
	$ficha                = tt_roteiros_ficha( $release );
	$ficha->name          = 'TravelTec Roteiros';
	$ficha->author        = 'TravelTec';
	$ficha->homepage      = $ficha->url;
	$ficha->download_link = $release['zip'];
	$ficha->last_updated  = $release['data'];
	$ficha->sections      = array(
		'description' => 'Sistema de roteiros: tipo de conteúdo, campos, recebimento pela API e as páginas do Elementor.',
		'changelog'   => wpautop( esc_html( $release['notas'] ) ),
	);
	return $ficha;
}, 10, 3 );

/** Depois de atualizar, esquece o cache para a tela já mostrar a versão certa. */
add_action( 'upgrader_process_complete', function ( $upgrader, $extra ) {
	if ( isset( $extra['type'] ) && 'plugin' === $extra['type'] ) {
		delete_site_transient( TT_ROTEIROS_CACHE );
	}
}, 10, 2 );

/**
 * Atualizar o plugin troca o código, mas não regrava os modelos do Elementor —
 * isso apagaria o que o cliente editou neles. Quando a versão dos modelos instalados
 * ficar para trás, avisa com o link da tela de reinstalar.
 */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || ! get_option( TT_ROTEIROS_OPT ) ) {
		return;
	}
	$instalada = (string) get_option( 'tt_roteiros_versao_modelos', '' );
	if ( '' === $instalada || version_compare( $instalada, TT_ROTEIROS_VERSAO_MODELOS, '>=' ) ) {
		return;
	}
	$url = admin_url( 'edit.php?post_type=roteiros&page=tt-roteiros-modelos' );
	echo '<div class="notice notice-warning"><p><strong>TravelTec Roteiros ' . esc_html( TT_ROTEIROS_VERSION ) . ':</strong> os modelos do Elementor ainda são da versão '
		. esc_html( $instalada ) . '. <a href="' . esc_url( $url ) . '">Reinstalar modelos</a> para usar o layout novo.</p></div>';
} );
