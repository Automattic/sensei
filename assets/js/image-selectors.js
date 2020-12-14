jQuery( document ).ready( function () {
	if ( jQuery( '.radio-images' ).length ) {
		jQuery( '.radio-images' ).each( function () {
			if ( jQuery( this ).is( ':checked' ) ) {
				jQuery( this )
					.next( 'img.radio-image-thumb' )
					.addClass( 'active' );
			}
			jQuery( this ).hide();
		} );

		jQuery( '.radio-image-thumb' ).click( function () {
			jQuery( this )
				.addClass( 'active' )
				.siblings( '.active' )
				.removeClass( 'active' );
			jQuery( this ).prev( 'input.radio-images' ).trigger( 'click' );
		} );
	}
} );
