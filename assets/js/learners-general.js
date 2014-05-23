jQuery(document).ready( function($) {

	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/

	 /**
	 * exists checks if selector exists
	 * @since  1.2.0
	 * @return boolean
	 */
	jQuery.fn.exists = function() {
		return this.length>0;
	}

	jQuery.fn.getQueryVariable = function(variable) {
	       var query = window.location.search.substring(1);
	       var vars = query.split("&");
	       for (var i=0;i<vars.length;i++) {
               var pair = vars[i].split("=");
               if(pair[0] == variable){return pair[1];}
	       }
	       return(false);
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

		// Perform the AJAX call to get the select box.
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

	/***************************************************************************************************
	 * 	3 - Load Chosen Dropdowns.
	 ***************************************************************************************************/

	// Grading Overview Drop Downs
	if ( jQuery( '#course-category-options' ).exists() ) { jQuery( '#course-category-options' ).chosen(); }


});