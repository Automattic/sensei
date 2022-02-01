( () => {
	const customNavigation = document.querySelector(
		'#sensei-custom-navigation'
	);
	if ( ! customNavigation ) {
		return;
	}

	// Move the custom navigation to the top of the page.
	document
		.querySelector( '#wpbody-content > .wrap' )
		.prepend( customNavigation );

	// Find the default heading and hide it.
	const title = document.querySelector( '.wrap > h1.wp-heading-inline' );
	if ( title ) {
		title.style.display = 'none';
	}
	// Find the default "Add New" button and hide it.
	const addNewButton = document.querySelector(
		'.wrap > a.page-title-action'
	);
	if ( addNewButton ) {
		addNewButton.style.display = 'none';
	}
} )();
