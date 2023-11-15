jQuery( document ).ready( function ( $ ) {
	const progressStorage = $( '#experimental_progress_storage' );
	progressStorage.on( 'change', function () {
		if ( $( this ).is( ':checked' ) ) {
			$( '.sensei-settings_progress-storage-settings' ).show();
		} else {
			$( '.sensei-settings_progress-storage-settings' ).hide();
		}
	} );
} );
