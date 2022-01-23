( () => {
	const customNavigation = document.querySelector(
		'#sensei-custom-navigation'
	);
	if ( ! customNavigation ) {
		return;
	}

	document
		.querySelector( '#wpbody-content > .wrap' )
		.prepend( customNavigation );
	document.querySelector( '.wrap > h1.wp-heading-inline' ).style.display =
		'none';
	document.querySelector( '.wrap > a.page-title-action' ).style.display =
		'none';
} )();
