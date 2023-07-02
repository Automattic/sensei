jQuery( document ).ready( function ( $ ) {
	/***** Settings Tabs *****/
	const $senseiSettings = $( '#woothemes-sensei.sensei-settings' );

	function hideAllSections() {
		$senseiSettings.find( 'section' ).hide();
		removeCurrentTab();
	}

	function show( section = '' ) {
		$senseiSettings.find( `section#${ section }` ).show();
		markCurrentTab( section );
		sensei_log_event( 'settings_view', { view: section } );
		markSectionAsVisited( section );
	}

	function removeCurrentTab() {
		$senseiSettings.find( 'a.tab' ).removeClass( 'current' );
	}

	function markCurrentTab( section ) {
		$senseiSettings.find( `[href$="${ section }"]` ).addClass( 'current' );
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

	// Show `General` settings section if no section is selected in url hasn.
	const defaultSectionId = 'default-settings';
	const urlHashSectionId = window.location.hash?.replace( '#', '' );
	const queryString = window.location.search;
	const urlParams = new URLSearchParams( queryString );
	hideAllSections();
	let sectionSet = false;
	if ( urlHashSectionId ) {
		show( urlHashSectionId );
	} else if ( urlParams.has( 'tab' ) || ! href?.includes( '#' ) ) {
		markCurrentTab( urlParams.get( 'tab' ) || defaultSectionId );
	} else {
		show( defaultSectionId );
	}

	$senseiSettings.find( 'a.tab' ).on( 'click', function ( e ) {
		$senseiSettings.find( 'a.tab' ).removeClass( 'current' );
		const href = $( this ).attr( 'href' );
		if ( urlParams.has( 'tab' ) || ! href?.includes( '#' ) ) {
			markCurrentTab( urlParams.get( 'tab' ) || defaultSectionId );
			return true;
		}

		const sectionId = href.split( '#' )[ 1 ] || defaultSectionId;
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
