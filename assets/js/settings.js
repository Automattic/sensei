jQuery( document ).ready( function ( $ ) {
	/***** Settings Tabs *****/
	const $senseiSettings = $( '#woothemes-sensei.sensei-settings' );

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
		markSectionAsVisited( sectionId );
	}

	// Hide header and submit on page load if needed
	hideSettingsFormElements();

	function hideSettingsFormElements() {
		const urlHashSectionId = window.location.hash?.replace( '#', '' );
		if ( urlHashSectionId === 'woocommerce-settings' ) {
			const formRows = $senseiSettings.find( '#woocommerce-settings tr' );
			// Hide header and submit if there is not settings form in section
			hideHeaderAndSubmit(
				! formRows.length &&
					$senseiSettings.find( '#sensei-promo-banner' )
			);
		} else if ( urlHashSectionId === 'sensei-content-drip-settings' ) {
			const formRows = $senseiSettings.find(
				'#sensei-content-drip-settings tr'
			);
			// Hide header and submit if there is not settings form in section
			hideHeaderAndSubmit(
				! formRows.length &&
					$senseiSettings.find( '#sensei-promo-banner' )
			);
		} else {
			hideHeaderAndSubmit( false );
		}
	}

	function hideHeaderAndSubmit( shouldHide ) {
		if ( shouldHide ) {
			$senseiSettings.find( '#submit' ).hide();
			$senseiSettings.find( 'h2' ).hide();
		} else {
			$senseiSettings.find( '#submit' ).show();
			$senseiSettings.find( 'h2' ).show();
		}
	}

	window.onhashchange = hideSettingsFormElements;

	// Show general settings section if no section is selected in url hasn.
	const defaultSectionId = 'default-settings';
	const urlHashSectionId = window.location.hash?.replace( '#', '' );
	hideAllSections();
	if ( urlHashSectionId ) {
		show( urlHashSectionId );
	} else {
		show( defaultSectionId );
	}

	$senseiSettings.find( 'a.tab' ).on( 'click', function ( e ) {
		const queryString = window.location.search;
		const urlParams = new URLSearchParams( queryString );

		const href = $( this ).attr( 'href' );
		if ( urlParams.has( 'tab' ) || ! href?.includes( '#' ) ) {
			return true;
		}

		e.preventDefault();
		const sectionId = href.split( '#' )[ 1 ];
		window.location.hash = '#' + sectionId;
		hideAllSections();
		show( sectionId );
		return false;
	} );

	function markSectionAsVisited( sectionId ) {
		const data = new FormData();
		data.append( 'action', 'sensei_settings_section_visited' );
		data.append( 'section_id', sectionId );
		data.append( 'nonce', window.senseiSettingsSectionVisitNonce );
		fetch( ajaxurl, { method: 'POST', body: data } );
	}

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
