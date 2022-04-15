// export function reloadWPTable() {
// 	jQuery.ajax( {
// 		// /wp-admin/admin-ajax.php
// 		url: ajaxurl,
// 		// Add action and nonce to our collected data
// 		data: jQuery.extend(
// 			{
// 				action: 'fetch_custom_list',
// 			},
// 			{
// 				per_page: '20',
// 				offset: '50',
// 			}
// 		),
// 		// Handle the successful result
// 		success: function ( fnResponse ) {
// 			// WP_List_Table::ajax_response() returns json
// 			let response = jQuery.parseJSON( fnResponse );
//
// 			// Add the requested rows
// 			if ( response.rows.length )
// 				jQuery( '#the-list' ).html( response.rows );
// 			// Update column headers for sorting
// 			if ( response.column_headers.length )
// 				jQuery( 'thead tr, tfoot tr' ).html(
// 					response.column_headers
// 				);
// 			// Update pagination for navigation
// 			if ( response.pagination.bottom.length )
// 				jQuery( '.tablenav.top .tablenav-pages' ).html(
// 					jQuery( response.pagination.top ).html()
// 				);
// 			if ( response.pagination.top.length )
// 				jQuery( '.tablenav.bottom .tablenav-pages' ).html(
// 					jQuery( response.pagination.bottom ).html()
// 				);
//
// 			window.initBulkUserActionsGlobal();
// 			window.attachStudentActionMenuNodes();
// 		},
// 		error: function ( error ) {},
// 	} );
//
// }
