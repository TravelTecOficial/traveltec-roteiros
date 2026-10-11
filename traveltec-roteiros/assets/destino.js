/**
 * Página do destino (Voucher Tec): o campo "Destino desejado" do formulário de cotação já vem com o nome do
 * destino. Só preenche campo vazio — o que a pessoa escreveu fica.
 */
( function () {
	'use strict';

	function preencher( documento, nome ) {
		var feitos = 0;
		if ( ! nome ) {
			return feitos;
		}
		Array.prototype.slice.call( documento.querySelectorAll( '[name="field_destino"]' ) ).forEach( function ( campo ) {
			if ( campo.value !== '' ) {
				return;
			}
			campo.value = nome;
			campo.dispatchEvent( new documento.defaultView.Event( 'input', { bubbles: true } ) );
			feitos++;
		} );
		return feitos;
	}

	window.TTDestino = { preencher: preencher };

	function iniciar() {
		preencher( document, ( window.ttDestino && window.ttDestino.nome ) || '' );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', iniciar );
	} else {
		iniciar();
	}
}() );
