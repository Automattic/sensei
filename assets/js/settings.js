jQuery( document ).ready( function ( $ ) {
	/***** Settings Tabs *****/
	$senseiSettings = $( '#woothemes-sensei.sensei-settings' );

	function hideAllSections() {
		$senseiSettings.find( 'section' ).hide();
		$senseiSettings.find( 'a.tab' ).removeClass( 'current' );
	}

	function show( sectionId = '' ) {
		$senseiSettings.find( `section#${ sectionId }` ).show();
		$senseiSettings
			.find( `[href="#${ sectionId }"]` )
			.addClass( 'current' );
		sensei_log_event( 'settings_view', { view: sectionId } );
	}

	// Show general settings section if no section is selected in url hasn.
	const defaultSectionId = 'default-settings';
	const urlHashSectionId = window.location.hash?.replace( '#', '' );
	hideAllSections();
	if ( urlHashSectionId ) {
		show( urlHashSectionId );
	} else {
		show( defaultSectionId );
	}
	windowResize();
	window.addEventListener( 'resize', windowResize );

	function windowResize() {
		if ( urlHashSectionId === 'woocommerce-settings' ) {
			if ( window.visualViewport.width > 1100 ) {
				$senseiSettings.find( '#sensei-pricing-image-desktop' ).show();
				$senseiSettings.find( '#sensei-pricing-image-mobile' ).hide();
			} else {
				$senseiSettings.find( '#sensei-pricing-image-desktop' ).hide();
				$senseiSettings.find( '#sensei-pricing-image-mobile' ).show();
			}
		}
	}

	$senseiSettings.find( 'a.tab' ).on( 'click', function () {
		const sectionId = $( this ).attr( 'href' )?.replace( '#', '' );
		window.location.hash = '#' + sectionId;
		hideAllSections();
		show( sectionId );
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
