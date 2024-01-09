jQuery( document ).ready( function ( $ ) {
	/***** Settings Tabs *****/
	const $senseiSettings = $( '#woothemes-sensei.sensei-settings' );

	// Show the current section.
	showSection( getCurrentSectionId() );

	// Switch to the section when the tab is clicked.
	$senseiSettings.find( 'a.tab:not(.external)' ).on( 'click', function ( e ) {
		const sectionUrl = $( this ).attr( 'href' );
		const sectionId = getSectionIdFromUrl( sectionUrl );

		if ( ! sectionExists( sectionId ) ) {
			return true;
		}

		changeCurrentUrl( sectionUrl );
		updateReferer( sectionUrl );
		showSection( sectionId );

		e.preventDefault();
	} );

	// Change the section when the user navigates the session history.
	addEventListener( 'popstate', ( e ) => {
		const sectionId = getSectionIdFromUrl( window.location.href );

		if ( sectionExists( sectionId ) ) {
			updateReferer( window.location.href );
			showSection( sectionId );
		}
	} );

	/**
	 * Change the current browser URL.
	 *
	 * @param {string} url
	 */
	function changeCurrentUrl( url ) {
		window.history.pushState( {}, null, url );
	}

	/**
	 * Update the hidden referer field.
	 *
	 * @param {string} url
	 */
	function updateReferer( url ) {
		const urlObject = new URL( url );

		$senseiSettings.find( 'input[name="_wp_http_referer"]' )
			.val( urlObject.pathname + urlObject.search );
	}

	/**
	 * Hide all sections.
	 */
	function hideAllSections() {
		$senseiSettings.find( 'section' )
			.hide();
	}

	/**
	 * Show a settings section.
	 *
	 * @param {string} sectionId
	 */
	function showSection( sectionId ) {
		hideAllSections();
		hideSettingsFormElements( sectionId );

		$senseiSettings.find( `section#${ sectionId }` )
			.show();

		$senseiSettings.find( 'a.tab.current' )
			.removeClass( 'current' )

		$senseiSettings
			.find( `a.tab[href*="tab=${ sectionId }"]` )
			.addClass( 'current' );

		sensei_log_event( 'settings_view', { view: sectionId } );
		markSectionAsVisited( sectionId );
	}

	/**
	 * Get section id from the current URL.
	 *
	 * @returns {string}
	 */
	function getCurrentSectionId() {
		return getSectionIdFromUrl( window.location.href );
	}

	/**
	 * Get section id from a URL.
	 *
	 * @param {string} url
	 * @returns {string}
	 */
	function getSectionIdFromUrl( url ) {
		const urlParams = new URLSearchParams( url );

		return urlParams.get( 'tab' )
			|| url.split( '#' )[1]
			|| 'default-settings';
	}

	/**
	 * Check if a section exists.
	 *
	 * @param {string} sectionId
	 * @returns {boolean}
	 */
	function sectionExists( sectionId ) {
		return $( '#' + sectionId ).length > 0;
	}

	/**
	 * Hide the header and submit button if there are no settings in the section.
	 *
	 * @param {string} sectionId
	 */
	function hideSettingsFormElements( sectionId ) {
		if ( sectionId === 'woocommerce-settings' ) {
			const formRows = $senseiSettings.find( '#woocommerce-settings tr' );
			// Hide header and submit if there is not settings form in section
			hideHeaderAndSubmit(
				! formRows.length &&
					$senseiSettings.find( '#sensei-promo-banner' )
			);
		} else if ( sectionId === 'sensei-content-drip-settings' ) {
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

	/**
	 * Hide the header and submit button.
	 *
	 * @param {boolean} shouldHide
	 */
	function hideHeaderAndSubmit( shouldHide ) {
		if ( shouldHide ) {
			$senseiSettings.find( '#submit' ).hide();
			$senseiSettings.find( 'h2' ).hide();
		} else {
			$senseiSettings.find( '#submit' ).show();
			$senseiSettings.find( 'h2' ).show();
		}
	}

	/**
	 * Mark a section as visited.
	 * This is used to track which sections are being used.
	 *
	 * @param {string} sectionId
	 */
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
