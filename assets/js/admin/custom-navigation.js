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

	const title = document.querySelector( '.wrap > h1.wp-heading-inline' );
	if ( title ) {
		title.style.display = 'none';
	}
	const newCourseButton = document.querySelector(
		'.wrap > a.page-title-action'
	);
	if ( newCourseButton ) {
		newCourseButton.style.display = 'none';
	}
} )();
