jQuery( document ).ready( function ( $ ) {
	const progressStorage = $(
		'#sensei_experimental_progress_storage_feature'
	);
	progressStorage.on( 'change', function () {
		$( '.sensei-settings_progress-storage-settings' ).toggle();
	} );
} );
