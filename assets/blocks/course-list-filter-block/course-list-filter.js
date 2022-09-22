const courseListFeaturedFilterElements = document.querySelectorAll(
	'.wp-block-sensei-lms-course-list-filter select'
);

courseListFeaturedFilterElements.forEach( ( element ) => {
	element.onchange = ( evt ) => {
		const url = new URL( window.location.href );
		url.searchParams.set( evt.target.dataset.paramKey, evt.target.value );
		window.location.href = url;
	};
} );
