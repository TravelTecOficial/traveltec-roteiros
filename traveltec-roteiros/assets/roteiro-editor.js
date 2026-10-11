/**
 * Tela de edição do roteiro e do destino (Voucher Tec): abas, editor visual sob demanda, preço, fotos, lista de
 * itens e contador.
 * O PHP (includes/roteiro-editor.php) desenha o HTML e grava; aqui só o comportamento. Preço e fotos ligam o
 * campo "alterado" na primeira edição — sem ele o servidor mantém o valor gravado.
 */
( function () {
	'use strict';

	function todos( seletor, raiz ) {
		return Array.prototype.slice.call( ( raiz || document ).querySelectorAll( seletor ) );
	}

	/* ------------------------------------------------------------ abas e editores */

	function iniciarEditores( painel ) {
		if ( painel.getAttribute( 'data-tt-editores' ) === '1' ) {
			return;
		}
		painel.setAttribute( 'data-tt-editores', '1' );
		if ( ! window.wp || ! window.wp.editor ) {
			return;
		}
		todos( 'textarea[data-tt-editor]', painel ).forEach( function ( area ) {
			window.wp.editor.initialize( area.id, {
				tinymce: { wpautop: false, height: 240, toolbar1: 'bold,italic,bullist,numlist,link,unlink,undo,redo,removeformat', toolbar2: '' },
				quicktags: true,
				mediaButtons: false
			} );
		} );
	}

	function ativarAba( raiz, id ) {
		todos( '[data-tt-aba]', raiz ).forEach( function ( botao ) {
			var ativa = botao.getAttribute( 'data-tt-aba' ) === id;
			botao.classList.toggle( 'is-ativa', ativa );
			botao.setAttribute( 'aria-selected', ativa ? 'true' : 'false' );
			botao.tabIndex = ativa ? 0 : -1;
		} );
		todos( '[data-tt-painel]', raiz ).forEach( function ( painel ) {
			var ativo = painel.getAttribute( 'data-tt-painel' ) === id;
			painel.hidden = ! ativo;
			if ( ativo ) {
				iniciarEditores( painel );
			}
		} );
	}

	function iniciarAbas( raiz ) {
		var botoes = todos( '[data-tt-aba]', raiz );
		botoes.forEach( function ( botao, i ) {
			botao.addEventListener( 'click', function () {
				ativarAba( raiz, botao.getAttribute( 'data-tt-aba' ) );
			} );
			botao.addEventListener( 'keydown', function ( evento ) {
				var passo = evento.key === 'ArrowRight' ? 1 : evento.key === 'ArrowLeft' ? -1 : 0;
				if ( ! passo ) {
					return;
				}
				var alvo = botoes[ ( i + passo + botoes.length ) % botoes.length ];
				ativarAba( raiz, alvo.getAttribute( 'data-tt-aba' ) );
				alvo.focus();
				evento.preventDefault();
			} );
		} );
		var ativa = raiz.querySelector( '[data-tt-aba].is-ativa' ) || botoes[ 0 ];
		if ( ativa ) {
			ativarAba( raiz, ativa.getAttribute( 'data-tt-aba' ) );
		}
	}

	/* ------------------------------------------------------------ preço */

	function iniciarPreco( raiz ) {
		var caixa = raiz.querySelector( '[data-tt-preco]' );
		if ( ! caixa ) {
			return;
		}
		var alterado = caixa.querySelector( '[data-tt-alterado]' );
		var modo = caixa.querySelector( '[data-tt-preco-modo]' );
		var previa = caixa.querySelector( '[data-tt-previa]' );
		var blocoCampos = caixa.querySelector( '[data-tt-modo-campos]' );
		var blocoLivre = caixa.querySelector( '[data-tt-modo-livre]' );
		var troca = caixa.querySelector( '[data-tt-trocar-modo]' );

		function valor( nome ) {
			var campo = caixa.querySelector( '[name="tt_preco[' + nome + ']"]' );
			return campo ? campo.value.trim() : '';
		}

		function atualizarPrevia() {
			var texto = '';
			if ( modo.value === 'livre' ) {
				texto = valor( 'livre' );
			} else if ( valor( 'valor' ) !== '' ) {
				texto = ( valor( 'prefixo' ) + ' ' + ( valor( 'moeda' ) === 'BRL' ? 'R$' : valor( 'moeda' ) ) + ' ' + valor( 'valor' ) ).trim();
				if ( valor( 'complemento' ) !== '' ) {
					texto += ', ' + valor( 'complemento' );
				}
			}
			previa.textContent = texto || '(sem preço)';
		}

		function marcar() {
			alterado.value = '1';
			atualizarPrevia();
		}

		[ 'input', 'change' ].forEach( function ( tipo ) {
			caixa.addEventListener( tipo, function ( evento ) {
				var nome = evento.target.name || '';
				if ( nome.indexOf( 'tt_preco[' ) === 0 && evento.target !== alterado && evento.target !== modo ) {
					marcar();
				}
			} );
		} );

		troca.addEventListener( 'click', function () {
			var paraLivre = modo.value !== 'livre';
			modo.value = paraLivre ? 'livre' : 'campos';
			blocoCampos.hidden = paraLivre;
			blocoLivre.hidden = ! paraLivre;
			troca.textContent = paraLivre ? 'Usar os campos (prefixo, moeda e valor)' : 'Usar texto livre';
			marcar();
		} );

		atualizarPrevia();
	}

	/* ------------------------------------------------------------ fotos */

	function botaoFoto( rotulo, titulo, desligado, aoClicar ) {
		var botao = document.createElement( 'button' );
		botao.type = 'button';
		botao.className = 'tt-rot__foto-botao';
		botao.textContent = rotulo;
		botao.title = titulo;
		botao.setAttribute( 'aria-label', titulo );
		botao.disabled = desligado;
		botao.addEventListener( 'click', aoClicar );
		return botao;
	}

	function iniciarFotos( raiz ) {
		var caixa = raiz.querySelector( '[data-tt-fotos]' );
		if ( ! caixa ) {
			return;
		}
		var lista = caixa.querySelector( '[data-tt-lista]' );
		var json = caixa.querySelector( '[data-tt-fotos-json]' );
		var alterado = caixa.querySelector( '[data-tt-alterado]' );
		var contagem = caixa.querySelector( '[data-tt-contagem]' );
		var fotos = [];
		try {
			fotos = JSON.parse( caixa.getAttribute( 'data-urls' ) || '[]' );
		} catch ( erro ) {
			fotos = [];
		}

		function sincronizar( mudou ) {
			json.value = JSON.stringify( fotos );
			if ( mudou ) {
				alterado.value = '1';
			}
			contagem.textContent = fotos.length === 1 ? '1 foto' : fotos.length + ' fotos';
		}

		function mover( i, passo ) {
			var item = fotos.splice( i, 1 )[ 0 ];
			fotos.splice( i + passo, 0, item );
			desenhar();
			sincronizar( true );
		}

		function desenhar() {
			lista.innerHTML = '';
			fotos.forEach( function ( foto, i ) {
				var item = document.createElement( 'li' );
				item.className = 'tt-rot__foto';
				var imagem = document.createElement( 'img' );
				imagem.src = foto.url;
				imagem.alt = foto.alt || '';
				imagem.loading = 'lazy';
				var acoes = document.createElement( 'span' );
				acoes.className = 'tt-rot__foto-acoes';
				acoes.appendChild( botaoFoto( '←', 'Mover para antes', i === 0, function () { mover( i, -1 ); } ) );
				acoes.appendChild( botaoFoto( '→', 'Mover para depois', i === fotos.length - 1, function () { mover( i, 1 ); } ) );
				acoes.appendChild( botaoFoto( '×', 'Remover foto', false, function () {
					fotos.splice( i, 1 );
					desenhar();
					sincronizar( true );
				} ) );
				item.appendChild( imagem );
				item.appendChild( acoes );
				lista.appendChild( item );
			} );
		}

		var quadro = null;
		caixa.querySelector( '[data-tt-add-fotos]' ).addEventListener( 'click', function () {
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			if ( ! quadro ) {
				quadro = window.wp.media( {
					title: caixa.getAttribute( 'data-titulo' ) || 'Escolher fotos do roteiro',
					button: { text: caixa.getAttribute( 'data-botao' ) || 'Adicionar ao roteiro' },
					library: { type: 'image' },
					multiple: true
				} );
				quadro.on( 'select', function () {
					quadro.state().get( 'selection' ).each( function ( anexo ) {
						var dados = anexo.toJSON();
						var url = ( dados.sizes && dados.sizes.large && dados.sizes.large.url ) || dados.url;
						if ( url ) {
							fotos.push( { id: dados.id || 0, url: url, alt: dados.alt || '' } ); // o id é usado pelos destinos; o roteiro grava só url e alt
						}
					} );
					desenhar();
					sincronizar( true );
				} );
			}
			quadro.open();
		} );

		desenhar();
		sincronizar( false );
	}

	/* ------------------------------------------------------------ lista de itens (destino: o que fazer) */

	function iniciarItens( raiz ) {
		todos( '[data-tt-itens]', raiz ).forEach( function ( caixa ) {
			var lista = caixa.querySelector( '[data-tt-itens-lista]' );
			var nome = caixa.getAttribute( 'data-nome' );

			function atualizar() {
				var itens = todos( 'li', lista );
				itens.forEach( function ( item, i ) {
					item.querySelector( '[data-tt-sobe]' ).disabled = i === 0;
					item.querySelector( '[data-tt-desce]' ).disabled = i === itens.length - 1;
				} );
			}

			function equipar( item ) {
				var acoes = document.createElement( 'span' );
				acoes.className = 'tt-rot__foto-acoes';
				var sobe = botaoFoto( '↑', 'Mover para cima', false, function () {
					lista.insertBefore( item, item.previousElementSibling );
					atualizar();
				} );
				sobe.setAttribute( 'data-tt-sobe', '' );
				var desce = botaoFoto( '↓', 'Mover para baixo', false, function () {
					lista.insertBefore( item.nextElementSibling, item );
					atualizar();
				} );
				desce.setAttribute( 'data-tt-desce', '' );
				var remove = botaoFoto( '×', 'Remover item', false, function () {
					lista.removeChild( item );
					atualizar();
				} );
				remove.setAttribute( 'data-tt-remove', '' );
				acoes.appendChild( sobe );
				acoes.appendChild( desce );
				acoes.appendChild( remove );
				item.appendChild( acoes );
				// Enter num campo de texto publicaria o post: aqui ele abre o item seguinte.
				item.querySelector( 'input' ).addEventListener( 'keydown', function ( evento ) {
					if ( evento.key !== 'Enter' ) {
						return;
					}
					evento.preventDefault();
					novoItem( item.nextElementSibling );
				} );
			}

			function novoItem( antesDe ) {
				var item = document.createElement( 'li' );
				item.className = 'tt-rot__item';
				var campo = document.createElement( 'input' );
				campo.type = 'text';
				campo.className = 'widefat';
				campo.name = nome;
				item.appendChild( campo );
				equipar( item );
				lista.insertBefore( item, antesDe || null );
				atualizar();
				campo.focus();
			}

			todos( 'li', lista ).forEach( equipar );
			atualizar();
			caixa.querySelector( '[data-tt-add-item]' ).addEventListener( 'click', function () {
				novoItem( null );
			} );
		} );
	}

	/* ------------------------------------------------------------ contador de caracteres (destino: resumo) */

	function iniciarContadores( raiz ) {
		todos( '[data-tt-contador]', raiz ).forEach( function ( area ) {
			var alvo = parseInt( area.getAttribute( 'data-tt-contador' ), 10 ) || 0;
			var campo = area.closest( '.tt-rot__campo' );
			var saida = campo ? campo.querySelector( '[data-tt-contador-saida]' ) : null;
			if ( ! saida ) {
				return;
			}
			function contar() {
				saida.textContent = area.value.length + ' de ' + alvo + ' caracteres';
				saida.classList.toggle( 'is-acima', area.value.length > alvo );
			}
			area.addEventListener( 'input', contar );
			contar();
		} );
	}

	/* ------------------------------------------------------------ início */

	function iniciar( raiz ) {
		if ( raiz.getAttribute( 'data-tt-pronto' ) === '1' ) {
			return; // o script pode ser chamado duas vezes; a tela só liga uma
		}
		raiz.setAttribute( 'data-tt-pronto', '1' );
		iniciarAbas( raiz );
		iniciarPreco( raiz );
		iniciarFotos( raiz );
		iniciarItens( raiz );
		iniciarContadores( raiz );
	}

	function iniciarTodos() {
		todos( '[data-tt-rot]' ).forEach( iniciar );
	}

	window.TTRoteiroEditor = { iniciar: iniciar };

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', iniciarTodos );
	} else {
		iniciarTodos();
	}
}() );
