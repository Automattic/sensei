jQuery( document ).ready( function ( $ ) {
	// Show more HPPS settings when the feature is enabled.
	const progressStorageFeature = $(
		'.sensei-settings_progress-storage-feature'
	);
	progressStorageFeature.on( 'change', function () {
		if ( $( this ).is( ':checked' ) ) {
			$( '.sensei-settings__progress-storage-settings' ).show();
		} else {
			$( '.sensei-settings__progress-storage-settings' ).hide();
		}
	} );

	// Disable the repository options when the sync progress is disabled.
	// Ensure comments are selected when the sync progress is disabled.
	const syncProgress = $(
		'.sensei-settings_progress-storage-synchronization'
	);
	syncProgress.on( 'change', function () {
		const savedState = $( this ).data( 'saved-state' );
		let repositoryOptions = $(
			'.sensei-settings_progress-storage-repository'
		);
		if ( $( this ).is( ':checked' ) ) {
			if ( savedState > 0 ) {
				repositoryOptions.prop( 'disabled', false );
			}
		} else {
			// ensure comments are selected
			repositoryOptions
				.filter( '[value="comments"]' )
				.prop( 'checked', true );
			repositoryOptions.prop( 'disabled', true );
		}
	} );
} );
