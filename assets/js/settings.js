jQuery( document ).ready( function ( $ ) {
	/***** Settings Tabs *****/

	// Make sure each heading has a unique ID.
	jQuery( 'ul#settings-sections.subsubsub' )
		.find( 'a' )
		.each( function () {
			var id_value = jQuery( this ).attr( 'href' ).replace( '#', '' );
			jQuery( 'h3:contains("' + jQuery( this ).text() + '")' )
				.attr( 'id', id_value )
				.addClass( 'section-heading' );
		} );

	// Only show the General settings.
	var defaultSettingsSlug = 'default-settings';
	$( '#woothemes-sensei section' ).each( function () {
		if ( this.id !== defaultSettingsSlug ) {
			$( this ).hide();
		}
	} );
	sensei_log_event( 'settings_view', { view: defaultSettingsSlug } );

	jQuery( '#woothemes-sensei .subsubsub a.tab' ).click( function () {
		// Move the "current" CSS class.
		jQuery( this )
			.parents( '.subsubsub' )
			.find( '.current' )
			.removeClass( 'current' );
		jQuery( this ).addClass( 'current' );

		// Hide all sections.
		jQuery( '#woothemes-sensei section' ).hide();

		// If the link is a tab, show only the specified tab.
		var toShow = jQuery( this ).attr( 'href' );
		// Remove the first occurance of # from the selected string (will be added manually below).
		toShow = toShow.replace( '#', '' );
		jQuery( '#' + toShow ).show();

		sensei_log_event( 'settings_view', { view: toShow } );

		return false;
	} );

	/***** Colour pickers *****/

	jQuery( '.colorpicker' ).hide();
	jQuery( '.colorpicker' ).each( function () {
		jQuery( this ).farbtastic( jQuery( this ).prev( '.color' ) );
	} );

	jQuery( '.color' ).click( function () {
		jQuery( this ).next( '.colorpicker' ).fadeIn();
	} );

	jQuery( document ).mousedown( function () {
		jQuery( '.colorpicker' ).each( function () {
			var display = jQuery( this ).css( 'display' );
			if ( display == 'block' ) {
				jQuery( this ).fadeOut();
			}
		} );
	} );
} );
