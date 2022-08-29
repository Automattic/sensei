const courseListFeaturedFilterElements = document.querySelectorAll(
	'.course_list_category_filter'
);
courseListFeaturedFilterElements.forEach( ( element ) => {
	element.onchange = ( evt ) => {
		const url = new URL( window.location.href );
		url.searchParams.set(
			'course-filter-query-' + evt.target.dataset.queryId + '-category',
			evt.target.value
		);
		window.location.href = url;
	};
} );
