jQuery( document ).ready( () => {
	function toggleModuleDetails() {
		const moduleDetails = jQuery( this )
			.closest( '.wp-block-sensei-lms-course-outline-module' )
			.children( '.wp-block-sensei-lms-collapsible' );

		moduleDetails.toggleClass( 'collapsed' );
		jQuery( this ).toggleClass( 'dashicons-arrow-up-alt2' );
		jQuery( this ).toggleClass( 'dashicons-arrow-down-alt2' );
	}

	const arrowButton = jQuery( '.wp-block-sensei-lms-course-outline__arrow' );

	arrowButton.click( toggleModuleDetails );
	arrowButton.keydown( function ( e ) {
		if ( 13 === e.which ) {
			toggleModuleDetails.call( this );
		}
	} );
} );
