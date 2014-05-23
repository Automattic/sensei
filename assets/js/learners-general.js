jQuery(document).ready( function($) {

	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/

	 /**
	 * exists checks if selector exists
	 * @since  1.6.0
	 * @return boolean
	 */
	jQuery.fn.exists = function() {
		return this.length>0;
	}

	/***************************************************************************************************
	 * 	2 - Learner Management Overview Functions.
	 ***************************************************************************************************/

	 /**
	 * Course Category Change Event.
	 *
	 * @since 1.6.0
	 * @access public
	 */
	jQuery( '#course-category-options' ).on( 'change', '', function() {

	 	var dataToPost = 'course_cat=' + jQuery( this ).val();

		jQuery.post(
			ajaxurl,
			{
				action : 'get_redirect_url_learners',
				data : dataToPost
			},
			function( response ) {
				// Check for a response
				if ( '' != response ) {
					window.location = response;
				}
			}
		);
	});

	jQuery( '.remove-learner' ).click( function() {
		var dataToPost = '';

		var user_id = jQuery( this ).attr( 'data-user_id' );
		var post_id = jQuery( this ).attr( 'data-post_id' );
		var post_type = jQuery( this ).attr( 'data-post_type' );

		if( user_id && post_id && post_type ) {

			dataToPost += 'user_id=' + user_id;
			dataToPost += '&post_id=' + post_id;
			dataToPost += '&post_type=' + post_type;

			jQuery.post(
				ajaxurl,
				{
					action : 'remove_user_from_post',
					data : dataToPost
				},
				function( response ) {
					alert( response );
				}
			);
		}
	});

	/***************************************************************************************************
	 * 	3 - Load Chosen Dropdowns.
	 ***************************************************************************************************/

	// Learner Management Drop Downs
	if ( jQuery( '#course-category-options' ).exists() ) { jQuery( '#course-category-options' ).chosen(); }

});