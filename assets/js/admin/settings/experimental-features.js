jQuery( document ).ready( function ( $ ) {
	// Show more HPPS settings when the feature is enabled.
	const progressStorageFeature = $(
		'.sensei-settings_progress-storage-feature'
	);
	progressStorageFeature.on( 'change', function () {
		if ( $( this ).is( ':checked' ) ) {
			$( '.sensei-settings_progress-storage-settings' ).show();
		} else {
			$( '.sensei-settings_progress-storage-settings' ).hide();
		}
	} );

	// Disable the repository options when the sync progress is disabled.
	// Ensure comments are selected when the sync progress is disabled.
	const syncProgress = $(
		'.sensei-settings_progress-storage-synchronization'
	);
	syncProgress.on( 'change', function () {
		let repository_options = $(
			'.sensei-settings_progress-storage-repository'
		);
		if ( $( this ).is( ':checked' ) ) {
			repository_options.prop( 'disabled', false );
		} else {
			// ensure comments are selected
			repository_options
				.filter( '[value="comments"]' )
				.prop( 'checked', true );
			repository_options.prop( 'disabled', true );
		}
	} );
} );
