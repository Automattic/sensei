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

	/***************************************************************************************************
	 * 	2 - Grading Overview Functions.
	 ***************************************************************************************************/

	 /**
	 * Course Change Event.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '#grading-course-options' ).on( 'change', '', function() {
		var courseId = jQuery(this).val();
		// Populate the Lessons select box
		
		// Perform the AJAX call to get the select box.
		// jQuery.post(
		// 	ajaxurl,
		// 	{
		// 		action : 'lesson_add_course',
		// 		lesson_add_course_nonce : woo_localized_data.lesson_add_course_nonce,
		// 		data : dataToPost
		// 	},
		// 	function( response ) {
		// 		//ajaxLoaderIcon.fadeTo( 'slow', 0, function () {
		// 		//	jQuery( this ).css( 'visibility', 'hidden' );
		// 		//});
		// 		// Check for a course id
		// 		if ( 0 < response ) {
		// 			jQuery( '#lesson-course-actions' ).show();
		// 		jQuery( '#lesson-course-details' ).addClass( 'hidden' );
		// 		jQuery( '#lesson-course-options' ).append(jQuery( '<option></option>' ).attr( 'value' , response ).text(jQuery( '#course-title' ).attr( 'value' )));
		// 		jQuery( '#lesson-course-options' ).val( response );
		// 		jQuery( '#lesson-course-options' ).trigger( 'liszt:updated' );
		// 		} else {
		// 			// TODO - course creation fail message
		// 		}
		// 	}
		// );
		// return false; // TODO - move this below the next bracket when doing the ajax loader
	});

	/***************************************************************************************************
	 * 	3 - Grading User Profile Functions.
	 ***************************************************************************************************/



	/***************************************************************************************************
	 * 	4 - Load Chosen Dropdowns.
	 ***************************************************************************************************/

	// Grading Overview Drop Downs
	if ( jQuery( '#grading-course-options' ).exists() ) { jQuery( '#grading-course-options' ).chosen(); }


});