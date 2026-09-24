<?php
/**
 * Instalação do site padrão: imagens, formulários, páginas, modelos do Theme Builder e menus.
 *
 * Os modelos (templates/v2/*.json) trazem marcadores de ID, trocados aqui:
 *   {{DOC:card}} {{FORM:cotacao}} {{PAGE:obrigado-cotacao}} {{MENU:principal}} {{IMG:arquivo}} {{IMGID:arquivo}}
 * Tudo o que foi criado fica em tt_voucher_ids; reinstalar regrava os mesmos posts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TT_VOUCHER_OPT_IDS', 'tt_voucher_ids' );

function tt_voucher_ids() {
	$ids = get_option( TT_VOUCHER_OPT_IDS, array() );
	return array_merge( array( 'docs' => array(), 'forms' => array(), 'menus' => array(), 'midia' => array() ), is_array( $ids ) ? $ids : array() );
}

function tt_voucher_salvar_ids( $ids ) {
	update_option( TT_VOUCHER_OPT_IDS, $ids );
}

/** IDs de todos os documentos do Elementor instalados (páginas e modelos). */
function tt_voucher_ids_documentos() {
	return array_values( array_filter( array_map( 'intval', tt_voucher_ids()['docs'] ), function ( $id ) {
		return $id && get_post( $id );
	} ) );
}

function tt_voucher_modelo( $nome ) {
	$arq = TT_ROTEIROS_DIR . 'templates/v2/' . $nome . '.json';
	return file_exists( $arq ) ? json_decode( file_get_contents( $arq ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

function tt_voucher_indice() {
	$i = tt_voucher_modelo( '_indice' );
	return is_array( $i ) ? $i : array( 'docs' => array(), 'forms' => array(), 'imagens' => array() );
}

function tt_voucher_dependencias() {
	return array(
		'elementor'      => did_action( 'elementor/loaded' ) > 0,
		'elementor_pro'  => defined( 'ELEMENTOR_PRO_VERSION' ),
		'jetformbuilder' => post_type_exists( 'jet-form-builder' ) || defined( 'JET_FORM_BUILDER_VERSION' ),
	);
}

/** Post ainda vivo? */
function tt_voucher_vivo( $id ) {
	return $id && get_post( $id ) && 'trash' !== get_post_status( $id );
}

/* ------------------------------------------------------------------ Imagens */

function tt_voucher_instalar_imagens( &$ids, &$rel ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	foreach ( tt_voucher_indice()['imagens'] as $arq ) {
		if ( ! empty( $ids['midia'][ $arq ] ) && tt_voucher_vivo( $ids['midia'][ $arq ] ) ) {
			continue;
		}
		$origem = TT_ROTEIROS_DIR . 'assets/img/' . $arq;
		if ( ! file_exists( $origem ) ) {
			$rel['avisos'][] = "Imagem ausente no plugin: $arq";
			continue;
		}
		$up = wp_upload_bits( $arq, null, file_get_contents( $origem ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! empty( $up['error'] ) ) {
			$rel['avisos'][] = "Imagem $arq: " . $up['error'];
			continue;
		}
		$tipo = wp_check_filetype( $up['file'] );
		$id   = wp_insert_attachment( array(
			'post_mime_type' => $tipo['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $arq ),
			'post_status'    => 'inherit',
		), $up['file'] );
		if ( $id && ! is_wp_error( $id ) ) {
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
			$ids['midia'][ $arq ] = $id;
			$rel['criados'][]     = "imagem $arq (#$id)";
		}
	}
}

/* ------------------------------------------------------------------ Páginas (esqueleto) */

/**
 * Garante o post de cada página antes de montar o conteúdo — os formulários precisam do ID das
 * páginas de obrigado e as páginas precisam do ID dos formulários.
 */
function tt_voucher_garantir_pagina( $doc, &$ids, &$rel, $substituir ) {
	$chave = $doc['chave'];
	if ( ! empty( $ids['docs'][ $chave ] ) && tt_voucher_vivo( $ids['docs'][ $chave ] ) ) {
		return (int) $ids['docs'][ $chave ];
	}
	$existente = get_page_by_path( $doc['slug'], OBJECT, 'page' );
	if ( $existente ) {
		if ( ! $substituir ) {
			$rel['conflitos'][] = "Já existe a página /{$doc['slug']}/ (#{$existente->ID}). Use substituir para usar o layout do plugin nela.";
			return 0;
		}
		$ids['docs'][ $chave ] = $existente->ID;
		return $existente->ID;
	}
	$id = wp_insert_post( array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => $doc['titulo'],
		'post_name'   => $doc['slug'],
	) );
	if ( ! $id || is_wp_error( $id ) ) {
		$rel['avisos'][] = "Não criou a página {$doc['titulo']}.";
		return 0;
	}
	$ids['docs'][ $chave ] = $id;
	$rel['criados'][]      = "página /{$doc['slug']}/ (#$id)";
	return $id;
}

/* ------------------------------------------------------------------ Formulários (JetFormBuilder) */

function tt_voucher_acoes_form( $chave, $ids ) {
	$email   = tt_voucher_dados()['email_atendimento'];
	$email   = $email ? $email : get_option( 'admin_email' );
	$nome    = tt_voucher_valor( 'nome' );
	$pagina  = function ( $p ) use ( $ids ) {
		return isset( $ids['docs'][ $p ] ) ? (int) $ids['docs'][ $p ] : 0;
	};
	$redir   = function ( $p, $i ) use ( $pagina ) {
		return array( 'id' => 9000 + $i, 'type' => 'redirect_to_page', 'conditions' => array(), 'events' => array(), 'index' => $i,
			'settings' => array( 'redirect_to_page' => array( 'redirect_type' => 'static_page', 'redirect_page' => $pagina( $p ), 'redirect_url' => '' ) ) );
	};
	$mail    = function ( $assunto, $corpo, $campo_email ) use ( $email, $nome ) {
		return array( 'id' => 8000, 'type' => 'send_email', 'conditions' => array(), 'events' => array(), 'index' => 0,
			'settings' => array( 'send_email' => array(
				'mail_to' => 'custom', 'custom_email' => $email, 'from_name' => $nome, 'subject' => $assunto,
				'content_type' => 'text/html', 'content' => $corpo, 'reply_to' => 'form', 'reply_to_from_field' => $campo_email,
			) ) );
	};
	switch ( $chave ) {
		case 'cotacao': // o aviso da cotação chega pelo n8n (webhook recebe-forms)
			return array( $redir( 'obrigado-cotacao', 0 ) );
		case 'contato':
			return array(
				$mail( '%nome% entrou em contato pelo site', 'Nome: %nome%<br>E-mail: %email%<br>Celular: %celular%<br><br>%mensagem%', 'email' ),
				$redir( 'obrigado-contato', 1 ),
			);
		case 'newsletter':
			return array(
				$mail( '%nome% assinou a newsletter', 'NOVA INSCRIÇÃO NA NEWSLETTER<br><br>Nome: %nome%<br>E-mail: %email%', 'email' ),
				$redir( 'obrigado-newsletter', 1 ),
			);
	}
	return array();
}

function tt_voucher_instalar_forms( &$ids, &$rel ) {
	foreach ( tt_voucher_indice()['forms'] as $chave ) {
		$f = tt_voucher_modelo( 'form-' . $chave );
		if ( ! $f ) {
			continue;
		}
		$args = array( 'post_type' => 'jet-form-builder', 'post_status' => 'publish', 'post_title' => $f['titulo'], 'post_content' => $f['content'] );
		$id   = isset( $ids['forms'][ $chave ] ) && tt_voucher_vivo( $ids['forms'][ $chave ] ) ? (int) $ids['forms'][ $chave ] : 0;
		if ( $id ) {
			$args['ID'] = $id;
			wp_update_post( wp_slash( $args ) );
		} else {
			$id = wp_insert_post( wp_slash( $args ) );
			if ( ! $id || is_wp_error( $id ) ) {
				$rel['avisos'][] = "Não criou o formulário $chave.";
				continue;
			}
			$rel['criados'][] = "formulário {$f['titulo']} (#$id)";
		}
		$jf_args                = json_decode( (string) $f['args'], true );
		$jf_args                = is_array( $jf_args ) ? $jf_args : array();
		$jf_args['submit_type'] = 'ajax'; // a captação só envia ao n8n depois do sucesso via AJAX
		update_post_meta( $id, '_jf_args', wp_slash( wp_json_encode( $jf_args ) ) );
		update_post_meta( $id, '_jf_messages', wp_slash( (string) $f['messages'] ) );
		update_post_meta( $id, '_jf_validation', wp_slash( (string) ( $f['validation'] ? $f['validation'] : '{}' ) ) );
		update_post_meta( $id, '_jf_preset', '{}' );
		update_post_meta( $id, '_jf_recaptcha', '{}' );
		$ids['forms'][ $chave ] = $id;
	}
}

/** Regrava as ações (e-mail da agência e páginas de obrigado) com os dados atuais. */
function tt_voucher_atualizar_acoes_forms() {
	$ids = tt_voucher_ids();
	foreach ( $ids['forms'] as $chave => $id ) {
		if ( tt_voucher_vivo( $id ) ) {
			update_post_meta( $id, '_jf_actions', wp_slash( wp_json_encode( tt_voucher_acoes_form( $chave, $ids ), JSON_UNESCAPED_UNICODE ) ) );
		}
	}
}

/* ------------------------------------------------------------------ Menus */

function tt_voucher_instalar_menus( &$ids, &$rel ) {
	$pag = function ( $chave ) use ( $ids ) {
		return isset( $ids['docs'][ $chave ] ) ? (int) $ids['docs'][ $chave ] : 0;
	};
	$blog = (int) get_option( 'page_for_posts' );
	$menus = array(
		'principal' => array( 'Voucher Tec — Principal', 'menu-1', array(
			array( 'Home', home_url( '/' ), 0 ),
			array( 'Roteiros', home_url( '/roteiros/' ), 0 ),
			array( 'Blog', $blog ? get_permalink( $blog ) : home_url( '/blog/' ), $blog ),
			array( 'Cotação de viagens', '', $pag( 'cotacao' ) ),
			array( 'Sobre', '', $pag( 'sobre' ) ),
			array( 'Contato', '', $pag( 'contato' ) ),
		) ),
		'rodape'    => array( 'Voucher Tec — Rodapé', 'menu-2', array(
			array( 'Página inicial', home_url( '/' ), 0 ),
			array( 'Roteiros', home_url( '/roteiros/' ), 0 ),
			array( 'Contato', '', $pag( 'contato' ) ),
			array( 'Política de Privacidade', '', (int) get_option( 'wp_page_for_privacy_policy' ) ),
		) ),
	);
	$locais = get_theme_mod( 'nav_menu_locations', array() );
	foreach ( $menus as $chave => $def ) {
		$menu = ! empty( $ids['menus'][ $chave ] ) ? wp_get_nav_menu_object( (int) $ids['menus'][ $chave ] ) : false;
		if ( ! $menu ) {
			$menu = wp_get_nav_menu_object( $def[0] );
		}
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( $def[0] );
			if ( is_wp_error( $menu_id ) ) {
				$rel['avisos'][] = "Menu {$def[0]}: " . $menu_id->get_error_message();
				continue;
			}
			$menu             = wp_get_nav_menu_object( $menu_id );
			$rel['criados'][] = "menu {$def[0]}";
		}
		// Os menus são do plugin: a cada instalação voltam ao padrão, com as páginas que existirem agora.
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id, array( 'post_status' => 'any' ) ) as $antigo ) {
			wp_delete_post( $antigo->ID, true );
		}
		foreach ( $def[2] as $pos => $item ) {
			list( $titulo, $url, $page_id ) = $item;
			if ( ! $url && ! $page_id ) {
				continue;
			}
			wp_update_nav_menu_item( $menu->term_id, 0, $page_id && get_post( $page_id )
				? array( 'menu-item-title' => $titulo, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_id, 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish', 'menu-item-position' => $pos + 1 )
				: array( 'menu-item-title' => $titulo, 'menu-item-url' => $url, 'menu-item-type' => 'custom', 'menu-item-status' => 'publish', 'menu-item-position' => $pos + 1 ) );
		}
		$ids['menus'][ $chave ] = $menu->term_id;
		$locais[ $def[1] ]      = $menu->term_id;
	}
	set_theme_mod( 'nav_menu_locations', $locais );
}

/* ------------------------------------------------------------------ Documentos do Elementor */

function tt_voucher_marcadores( $ids ) {
	$t = array();
	foreach ( $ids['docs'] as $k => $id ) {
		$t[ '{{DOC:' . $k . '}}' ]  = (string) $id;
		$t[ '{{PAGE:' . $k . '}}' ] = (string) $id;
	}
	foreach ( $ids['forms'] as $k => $id ) {
		$t[ '{{FORM:' . $k . '}}' ] = (string) $id;
	}
	foreach ( $ids['menus'] as $k => $id ) {
		$m                          = wp_get_nav_menu_object( (int) $id );
		$t[ '{{MENU:' . $k . '}}' ] = $m ? $m->slug : '';
	}
	foreach ( $ids['midia'] as $arq => $id ) {
		$t[ '{{IMG:' . $arq . '}}' ]   = (string) wp_get_attachment_url( $id );
		$t[ '{{IMGID:' . $arq . '}}' ] = (string) $id;
	}
	return $t;
}

/** Tira as condições de outros modelos do mesmo tipo que disputam o mesmo lugar (ex.: outro cabeçalho "site inteiro"). */
function tt_voucher_liberar_condicoes( $tipo, $condicoes, $meu_id, &$rel ) {
	if ( ! $condicoes ) {
		return;
	}
	$outros = get_posts( array(
		'post_type' => 'elementor_library', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'exclude' => array( $meu_id ),
		'meta_query' => array( array( 'key' => '_elementor_template_type', 'value' => $tipo ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	$backup = get_option( 'tt_voucher_condicoes_anteriores', array() );
	foreach ( $outros as $id ) {
		$c         = array_values( array_filter( (array) get_post_meta( $id, '_elementor_conditions', true ) ) );
		$restantes = array_values( array_diff( $c, $condicoes ) );
		if ( count( $restantes ) < count( $c ) ) {
			$backup[ $id ] = $c;
			update_post_meta( $id, '_elementor_conditions', $restantes );
			$rel['avisos'][] = 'Condição tirada do modelo #' . $id . ' (' . get_the_title( $id ) . ') para o do plugin valer.';
		}
	}
	update_option( 'tt_voucher_condicoes_anteriores', $backup );
}

function tt_voucher_gravar_doc( $doc, &$ids, &$rel, $substituir ) {
	$chave = $doc['chave'];
	$base  = array(
		'_elementor_edit_mode' => 'builder',
		'_elementor_version'   => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0',
	);
	$marc = tt_voucher_marcadores( $ids );
	$data = strtr( wp_json_encode( $doc['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), $marc );
	$ps   = json_decode( strtr( wp_json_encode( $doc['page_settings'] ? $doc['page_settings'] : new stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), $marc ), true );
	$ps   = is_array( $ps ) ? $ps : array();

	if ( 'page' === $doc['tipo'] ) {
		$id = tt_voucher_garantir_pagina( $doc, $ids, $rel, $substituir );
		if ( ! $id ) {
			return 0;
		}
		wp_update_post( array( 'ID' => $id, 'post_content' => '', 'post_status' => 'publish' ) );
		update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
		$ps['template'] = 'elementor_header_footer';
		$meta = $base + array( '_elementor_template_type' => 'wp-page' );
		if ( ! empty( $doc['topo_transparente'] ) ) {
			update_post_meta( $id, '_tt_topo_transparente', 1 );
		}
	} else {
		$id = ! empty( $ids['docs'][ $chave ] ) && tt_voucher_vivo( $ids['docs'][ $chave ] ) ? (int) $ids['docs'][ $chave ] : 0;
		$args = array( 'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => $doc['titulo'] );
		if ( $id ) {
			$args['ID'] = $id;
			wp_update_post( wp_slash( $args ) );
		} else {
			$id = wp_insert_post( wp_slash( $args ) );
			if ( ! $id || is_wp_error( $id ) ) {
				$rel['avisos'][] = "Não criou o modelo {$doc['titulo']}.";
				return 0;
			}
			$rel['criados'][] = "modelo {$doc['titulo']} (#$id)";
		}
		wp_set_object_terms( $id, $doc['template_type'], 'elementor_library_type' );
		$meta = $base + array(
			'_elementor_template_type' => $doc['template_type'],
			'_elementor_conditions'    => $doc['condicoes'],
		);
		if ( in_array( $doc['template_type'], array( 'single', 'single-page', 'single-post', 'archive' ), true ) ) {
			$ps['page_template'] = 'elementor_header_footer';
		}
		tt_voucher_liberar_condicoes( $doc['template_type'], $doc['condicoes'], $id, $rel );
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	update_post_meta( $id, '_elementor_data', wp_slash( $data ) );
	update_post_meta( $id, '_elementor_page_settings', $ps );
	update_post_meta( $id, '_tt_voucher_modelo', $chave );
	$ids['docs'][ $chave ] = $id;
	return $id;
}

/* ------------------------------------------------------------------ Instalação completa */

/**
 * Instala tudo. $opcoes: substituir (bool) — usa o layout do plugin em páginas que já existem
 * com o mesmo endereço; modulos (array|null) — só as chaves pedidas (docs e forms).
 */
function tt_voucher_instalar( $opcoes = array() ) {
	$substituir = ! empty( $opcoes['substituir'] );
	$so         = isset( $opcoes['modulos'] ) && is_array( $opcoes['modulos'] ) ? $opcoes['modulos'] : null;
	$rel        = array( 'criados' => array(), 'atualizados' => array(), 'conflitos' => array(), 'avisos' => array() );
	$dep        = tt_voucher_dependencias();
	if ( ! $dep['elementor'] || ! $dep['elementor_pro'] ) {
		return new WP_Error( 'sem_elementor', 'Elementor e Elementor Pro (ou PRO Elements) precisam estar ativos.' );
	}
	tt_roteiros_registrar_tipo();
	$ids    = tt_voucher_ids();
	$indice = tt_voucher_indice();
	$quer   = function ( $chave ) use ( $so ) {
		return null === $so || in_array( $chave, $so, true );
	};

	tt_voucher_instalar_imagens( $ids, $rel );

	// Página do blog (lista de posts), quando o site usa página inicial estática.
	if ( ! get_option( 'page_for_posts' ) && 'page' === get_option( 'show_on_front' ) ) {
		$blog = get_page_by_path( 'blog', OBJECT, 'page' );
		$blog_id = $blog ? $blog->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Blog', 'post_name' => 'blog' ) );
		if ( $blog_id && ! is_wp_error( $blog_id ) ) {
			update_option( 'page_for_posts', $blog_id );
			update_option( 'posts_per_page', 9 );
		}
	}

	// 1) esqueleto das páginas  2) formulários  3) modelos  4) menus  5) conteúdo das páginas
	$docs = array();
	foreach ( $indice['docs'] as $d ) {
		if ( $quer( $d['chave'] ) ) {
			$docs[ $d['chave'] ] = tt_voucher_modelo( $d['chave'] );
		}
	}
	foreach ( $docs as $doc ) {
		if ( $doc && 'page' === $doc['tipo'] ) {
			tt_voucher_garantir_pagina( $doc, $ids, $rel, $substituir );
		}
	}
	if ( $dep['jetformbuilder'] ) {
		tt_voucher_instalar_forms( $ids, $rel );
	} else {
		$rel['avisos'][] = 'JetFormBuilder não está ativo: formulários não instalados.';
	}
	tt_voucher_salvar_ids( $ids );
	if ( $quer( 'card' ) && isset( $docs['card'] ) ) {
		tt_voucher_gravar_doc( $docs['card'], $ids, $rel, $substituir ); // a lista precisa do ID do card
	}
	tt_voucher_instalar_menus( $ids, $rel );
	foreach ( $docs as $chave => $doc ) {
		if ( $doc && 'card' !== $chave ) {
			tt_voucher_gravar_doc( $doc, $ids, $rel, $substituir );
		}
	}
	tt_voucher_salvar_ids( $ids );
	tt_voucher_atualizar_acoes_forms();

	// O Elementor Pro só reconhece as condições depois que o cache dele é refeito.
	try {
		$modulo = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' );
		if ( $modulo ) {
			$modulo->get_conditions_manager()->get_cache()->regenerate();
		}
	} catch ( \Throwable $e ) {
		$rel['avisos'][] = 'Cache de condições do Elementor: ' . $e->getMessage();
	}
	if ( $dep['jetformbuilder'] ) {
		update_option( 'tt_voucher_captacao', 1 );
	}
	update_option( 'tt_roteiros_versao_modelos', TT_VOUCHER_VERSAO_MODELOS );
	tt_voucher_estilo_no_kit( tt_voucher_estilo() );
	tt_voucher_limpar_cache();
	flush_rewrite_rules( true );
	$rel['ids'] = $ids;
	return $rel;
}
