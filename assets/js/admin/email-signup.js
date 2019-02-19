jQuery( document ).ready( function( $ ) {
	setTimeout( function() {
		$( '#mc_embed_signup' ).modal( {
			fadeDuration: 250,
			showClose: false,
		} );
	}, 1000 );

	$( 'body' ).on( 'submit', '#mc_embed_signup', function( event ) {
		setTimeout( function() {
			$.modal.close();
		} );
	} );

	$( 'body' ).on( 'change', '#mc_embed_signup #gdpr_34447', function( event ) {
		if ( $( event.target ).is( ':checked' ) ) {
			$( '#mc_embed_signup .email-input' ).show();
		} else {
			$( '#mc_embed_signup .email-input' ).hide();
		}
	} );
} );
