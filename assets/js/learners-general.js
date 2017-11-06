jQuery(document).ready( function() {

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
	};

	jQuery('.edit-start-date-date-picker').datepicker({
		dateFormat: 'yy-mm-dd'
	});

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
    
	jQuery('.edit-start-date-submit').click(function () {
		var new_date =  jQuery('.edit-start-date-date-picker').val();
		var user_id = jQuery( this ).attr( 'data-user_id' );
		var post_id = jQuery( this ).attr( 'data-post_id' );
		var post_type = jQuery( this ).attr( 'data-post_type' );
		var comment_id = jQuery( this ).attr( 'data-comment_id' );
		var dataToPost = '';
		if (!user_id || !post_id || !post_type || !new_date || !comment_id) {
			return;
		}
		dataToPost += 'user_id=' + user_id;
		dataToPost += '&post_id=' + post_id;
		dataToPost += '&post_type=' + post_type;
		dataToPost += '&new_date=' + new_date;
		dataToPost += '&comment_id=' + comment_id;

		jQuery.post(
			ajaxurl,
			{
				action : 'edit_date_started',
				edit_date_nonce : window.woo_learners_general_data.edit_date_nonce,
				data : dataToPost
			},
			function( response ) {
				// console.log(response);
				if (response) {
					location.reload();
				}
			}
		);
	});

	jQuery( '.remove-learner, .reset-learner' ).click( function( event ) {
		var dataToPost = '';

		var user_id = jQuery( this ).attr( 'data-user_id' );
		var post_id = jQuery( this ).attr( 'data-post_id' );
		var post_type = jQuery( this ).attr( 'data-post_type' );

		var confirm_message = window.woo_learners_general_data.remove_generic_confirm;

		var actions = {
			reset: {
				lesson : window.woo_learners_general_data.reset_lesson_confirm,
				course : window.woo_learners_general_data.reset_course_confirm,
				action : 'reset_user_post'
			},
			remove: {
				lesson : window.woo_learners_general_data.remove_from_lesson_confirm,
				course : window.woo_learners_general_data.remove_from_course_confirm,
				action : 'remove_user_from_post'
			},
			edit_date: {
				action: 'edit_date_started'
			}
		};

		var current_action = jQuery( event.target ).hasClass( 'remove-learner' ) ? 'remove' : 'reset';

		confirm_message = actions[current_action][post_type];

		if ( ! confirm( confirm_message ) ) {
			return;
		}

		var table_row = jQuery( this ).closest( 'tr' );

		if ( 'remove' === current_action ) {
			table_row.fadeTo( 'fast', 0.33 );
		}

		if ( user_id && post_id && post_type ) {
			dataToPost += 'user_id=' + user_id;
			dataToPost += '&post_id=' + post_id;
			dataToPost += '&post_type=' + post_type;

			jQuery.post(
				ajaxurl,
				{
					action : actions[current_action].action,
					modify_user_post_nonce : window.woo_learners_general_data.modify_user_post_nonce,
					data : dataToPost
				},
				function( response ) {
					if ( response ) {
						switch ( current_action ) {
						case 'remove':
							table_row.remove();
							break;

						case 'reset':
							table_row.find( '.graded' ).html( window.slgL10n.inprogress ).removeClass( 'graded' ).addClass( 'in-progress' );
							break;
						}
					}
				}
			);
		}
	});


	jQuery('select#add_learner_search').select2({
		minimumInputLength: 3,
		placeholder: window.woo_learners_general_data.selectplaceholder,
		width:'300px',

		ajax: {
			// in wp-admin ajaxurl is supplied by WordPress and is available globaly
			url: window.ajaxurl,
			dataType: 'json',
			cache: true,
			id: function( bond ){ return bond._id; },
			data: function (params) { // page is the one-based page number tracked by Select2
				return {
					term: params.term, //search term
					page: params.page || 1,
					action: 'sensei_json_search_users',
					security: window.woo_learners_general_data.search_users_nonce,
					default: ''
				};
			},
			processResults: function (users, page) {

				var validUsers = [];
				jQuery.each( users, function (i, val) {
					if ( ! jQuery.isEmptyObject( val )  ) {
						validUsers.push( { id: i , text: val  } );
					}
				});
				// wrap the users inside results for select 2 usage
				return {
					results: validUsers,
					page: page
				};
			}
		}
	}); // end select2

	/***************************************************************************************************
     * 	3 - Load Select2 Dropdowns.
     ***************************************************************************************************/

	// Learner Management Drop Downs
	if ( jQuery( '#course-category-options' ).exists() ) { jQuery( '#course-category-options' ).select2(); }

});
