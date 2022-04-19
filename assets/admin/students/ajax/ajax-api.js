export function reloadWPTable() {
	// eslint-disable-next-line
	jQuery( function ( $ ) {
		// eslint-disable-line
		$.ajax( {
			// eslint-disable-next-line
			url: ajaxurl,
			// Add action and nonce to our collected data
			data: $.extend(
				{
					action: 'fetch_custom_list',
				},
				getQueryParams()
			),
			// Handle the successful result
			// eslint-disable-next-line
			success: function ( fnResponse ) {
				// WP_List_Table::ajax_response() returns json
				const response = $.parseJSON( fnResponse ); // eslint-disable-line

				// Add the requested rows
				if ( response.rows.length )
					$( '#the-list' ).html( response.rows );
				// Update column headers for sorting
				if ( response.column_headers.length )
					$( 'thead tr, tfoot tr' ).html( response.column_headers );

				window.initBulkUserActionsGlobal();
				window.attachStudentActionMenuNodes();
			},
		} );
	} );
}

function getQueryParams() {
	const queryString = window.location.search;
	const urlParams = new URLSearchParams( queryString );
	const params = {
		...( urlParams.get( 'product' ) && {
			per_page: urlParams.get( 'product' ),
		} ),
		...( urlParams.get( 'offset' ) && {
			offset: urlParams.get( 'offset' ),
		} ),
		...( urlParams.get( 'orderby' ) && {
			orderby: urlParams.get( 'orderby' ),
		} ),
		...( urlParams.get( 'order' ) && { order: urlParams.get( 'order' ) } ),
		...( urlParams.get( 's' ) && { s: urlParams.get( 's' ) } ),
		...( urlParams.get( 'search' ) && { s: urlParams.get( 'search' ) } ),
		...( urlParams.get( 'paged' ) && { paged: urlParams.get( 'paged' ) } ),
		...( urlParams.get( 'filter_by_course_id' ) && {
			filter_by_course_id: urlParams.get( 'filter_by_course_id' ),
		} ),
	};
	return params;
}
