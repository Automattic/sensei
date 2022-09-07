const courseListFeaturedFilterElements = document.querySelectorAll(
	'.wp-sensei-course-list-block-filter select'
);

courseListFeaturedFilterElements.forEach( ( element ) => {
	element.onchange = ( evt ) => {
		const url = new URL( window.location.href );
		url.searchParams.set(
			evt.target.dataset.paramKey + evt.target.dataset.queryId,
			evt.target.value
		);
		window.location.href = url;
	};
} );
