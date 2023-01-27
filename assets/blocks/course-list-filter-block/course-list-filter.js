const courseListFeaturedFilterElements = document.querySelectorAll(
	'.wp-block-sensei-lms-course-list-filter select'
);

courseListFeaturedFilterElements.forEach( ( element ) => {
	element.onchange = ( evt ) => {
		const url = new URL( window.location.href );
		const queryId = evt.target.dataset.paramKey
			.split( '-' )
			.slice( -1 )[ 0 ];

		url.pathname = url.pathname.replace( /\/page\/[0-9]+\//, '/' );
		url.searchParams.delete( `query-${ queryId }-page` );
		url.searchParams.set( evt.target.dataset.paramKey, evt.target.value );
		window.location.href = url;
	};
} );
