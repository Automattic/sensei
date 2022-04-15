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
				{
					per_page: '20',
					offset: '50',
					orderby: 'learner',
					order: 'desc',
					s: 'will',
					filter_by_course_id: 0,
				}
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
				// Update pagination for navigation
				if ( response.pagination.bottom.length )
					$( '.tablenav.top .tablenav-pages' ).html(
						$( response.pagination.top ).html()
					);
				if ( response.pagination.top.length )
					$( '.tablenav.bottom .tablenav-pages' ).html(
						$( response.pagination.bottom ).html()
					);

				window.initBulkUserActionsGlobal();
				window.attachStudentActionMenuNodes();
			},
		} );
	} );
}
