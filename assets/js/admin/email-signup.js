jQuery( document ).ready( function( $ ) {
	setTimeout( function() {
		$( '#mc_embed_signup' ).modal( {
			fadeDuration: 250,
			showClose: false,
		} );
	}, 250 );

	$( 'body' ).on( 'submit', '#mc_embed_signup', function( event ) {
		setTimeout( function() {
			$.modal.close();
		} );
	} );

	$( 'body' ).on( 'change', '#mc_embed_signup .gdpr-checkbox input[type=checkbox]', function( event ) {
		if ( $( event.target ).is( ':checked' ) ) {
			$( '#mc_embed_signup .email-input' ).show();
			$( '#mc_embed_signup #mc-embedded-subscribe' ).prop( 'disabled', false );
		} else {
			$( '#mc_embed_signup .email-input' ).hide();
			$( '#mc_embed_signup #mc-embedded-subscribe' ).prop( 'disabled', true );
		}
	} );
} );
