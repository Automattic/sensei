jQuery( document ).ready( function ( $ ) {
	/***** Settings Tabs *****/
	const $senseiSettings = $( '#woothemes-sensei.sensei-settings' );
	const PREVIOUS_SECTION_ID_KEY = 'sensei-settings-previous-section-id';

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

	/**
	 * Get section id from the URL hash.
	 *
	 * @returns string|null
	 */
	function getSectionIdFromUrl() {
		return window.location.hash?.replace( '#', '' );
	}

	// Hide header and submit on page load if needed
	hideSettingsFormElements();

	function hideSettingsFormElements() {
		const urlHashSectionId = getSectionIdFromUrl();
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

	// Show general settings section if no section is selected in url hash.
	// Otherwise, show the section from the URL hash or the last visited section.
	const defaultSectionId = 'default-settings';
	const urlHashSectionId = getSectionIdFromUrl();
	hideAllSections();
	if ( urlHashSectionId ) {
		show( urlHashSectionId );
	} else if ( hasPreviousSectionId() ) {
		showPreviousSection();
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

	// Store the current section id in the session when the form is submitted.
	// This is used to redirect back to the last visited section when the user submits the form.
	$senseiSettings
		.find( '#sensei-settings-form' )
		.on( 'submit', storeCurrentSectionId );

	/**
	 * Store the current section id in the session.
	 */
	function storeCurrentSectionId() {
		const sectionId = getSectionIdFromUrl();

		if ( sectionId ) {
			window.sessionStorage.setItem( PREVIOUS_SECTION_ID_KEY, sectionId );
		}
	}

	/**
	 * Get the last visited section id from the session.
	 *
	 * @returns string|null
	 */
	function getPreviousSectionId() {
		return window.sessionStorage.getItem( PREVIOUS_SECTION_ID_KEY );
	}

	/**
	 * Check if the last visited section id is stored in the session.
	 *
	 * @returns boolean
	 */
	function hasPreviousSectionId() {
		return !! getPreviousSectionId();
	}

	/**
	 * Show the last visited section and update the URL.
	 */
	function showPreviousSection() {
		const previousSectionId = getPreviousSectionId();
		if ( ! previousSectionId ) {
			return;
		}

		window.location.hash = '#' + previousSectionId;
		show( previousSectionId );

		window.sessionStorage.removeItem( PREVIOUS_SECTION_ID_KEY );
	}

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
