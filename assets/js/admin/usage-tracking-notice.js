jQuery( document ).ready( function() {
	function displayProgressIndicator() {
		jQuery( '#sensei-usage-tracking-notice #progress' ).addClass( 'is-active' );
	}

	function displaySuccess( enabledTracking ) {
		if ( enabledTracking ) {
			jQuery( '#sensei-usage-tracking-enable-success' ).show();
		} else {
			jQuery( '#sensei-usage-tracking-disable-success' ).show();
		}
		jQuery( '#sensei-usage-tracking-notice' ).hide();
	}

	function displayError() {
		jQuery( '#sensei-usage-tracking-failure' ).show();
		jQuery( '#sensei-usage-tracking-notice' ).hide();
	}

	// If we're on the Settings page, check or uncheck the checkbox
	function checkSettingBox( enabledTracking ) {
		jQuery( '#sensei_usage_tracking_enabled' ).prop( 'checked', enabledTracking );
	}

	// Handle button clicks
	jQuery( '#sensei-usage-tracking-notice button' ).click( function( event ) {
		event.preventDefault();

		const button         = jQuery( this );
		const enableTracking = jQuery( this ).data( 'enable-tracking' ) == 'yes';
		const nonce          = jQuery( '#sensei-usage-tracking-notice' ).data( 'nonce' );

		displayProgressIndicator();

		jQuery.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'handle_tracking_opt_in',
				enable_tracking: enableTracking ? 1 : 0,
				nonce: nonce,
			},
			success: () => {
				displaySuccess( enableTracking );
				checkSettingBox( enableTracking );
			},
			error: displayError,
		} );
	});
});
