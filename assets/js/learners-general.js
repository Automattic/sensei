jQuery( document ).ready( function ( $ ) {
	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/

	/**
	 * exists checks if selector exists
	 * @since  1.6.0
	 * @return boolean
	 */
	jQuery.fn.exists = function () {
		return this.length > 0;
	};

	jQuery( '.edit-start-date-date-picker' ).datepicker( {
		dateFormat: 'yy-mm-dd',
	} );

	/***************************************************************************************************
	 * 	2 - Learner Management Overview Functions.
	 ***************************************************************************************************/

	/**
	 * Used for student enrolment hints.
	 *
	 * @since 3.0.0
	 */
	jQuery( document ).tooltip( {
		items: '.sensei-tooltip',
		tooltipClass: 'sensei-ui-tooltip',
		content: function () {
			return jQuery( this ).data( 'tooltip' );
		},
	} );

	/**
	 * Course Category Change Event.
	 *
	 * @since 1.6.0
	 * @access public
	 */
	jQuery( '#course-category-options' ).on( 'change', '', function () {
		var dataToPost = 'course_cat=' + jQuery( this ).val();

		jQuery.post(
			ajaxurl,
			{
				action: 'get_redirect_url_learners',
				data: dataToPost,
				security:
					window.woo_learners_general_data.course_category_nonce,
			},
			function ( response ) {
				// Check for a response
				if ( '' != response ) {
					window.location = response;
				}
			}
		);
	} );

	$( '.edit-start-date-submit' ).click( function () {
		var $this = $( this );
		var new_date = $this.prev( '.edit-start-date-date-picker' ).val();
		var user_id = $this.attr( 'data-user-id' );
		var post_id = $this.attr( 'data-post-id' );
		var post_type = $this.attr( 'data-post-type' );
		var comment_id = $this.attr( 'data-comment-id' );
		var dataToPost = '';

		if (
			! user_id ||
			! post_id ||
			! post_type ||
			! new_date ||
			! comment_id
		) {
			return;
		}

		dataToPost += 'user_id=' + user_id;
		dataToPost += '&post_id=' + post_id;
		dataToPost += '&post_type=' + post_type;
		dataToPost += '&new_date=' + new_date;
		dataToPost += '&comment_id=' + comment_id;

		$.post(
			ajaxurl,
			{
				action: 'edit_date_started',
				data: dataToPost,
				security: window.woo_learners_general_data.edit_date_nonce,
			},
			function ( response ) {
				if ( response ) {
					location.reload();
				}
			}
		);
	} );

	jQuery( '.learner-action' ).click( function ( event ) {
		var current_action = jQuery( this ).attr( 'data-action' );
		var provider = jQuery( this ).attr( 'data-provider' );

		var actions = {
			withdraw: {
				message:
					window.woo_learners_general_data.remove_from_course_confirm,
				eventName: 'learner_management_remove_enrollment',
			},
			enrol: {
				message:
					window.woo_learners_general_data.enrol_in_course_confirm,
				eventName: 'learner_management_enroll',
			},
			restore_enrollment: {
				message:
					window.woo_learners_general_data.restore_enrollment_confirm,
				eventName: 'learner_management_restore_enrollment',
			},
		};
		var action = actions[ current_action ];

		if ( typeof action === 'undefined' ) {
			return;
		}

		var confirm_message = action.message;

		if ( ! confirm( confirm_message ) ) {
			event.preventDefault();
		} else {
			var properties = provider ? { provider: provider } : null;
			window.sensei_log_event( action.eventName, properties );
		}
	} );

	jQuery( '.learner-async-action' ).click( function ( event ) {
		var dataToPost = '';

		var user_id = jQuery( this ).attr( 'data-user-id' );
		var post_id = jQuery( this ).attr( 'data-post-id' );
		var post_type = jQuery( this ).attr( 'data-post-type' );
		var current_action = jQuery( this ).attr( 'data-action' );

		var confirm_message =
			window.woo_learners_general_data.remove_generic_confirm;

		var actions = {
			remove_progress: {
				lesson:
					window.woo_learners_general_data.remove_from_lesson_confirm,
				course:
					window.woo_learners_general_data.remove_progress_confirm,
				action: 'remove_user_from_post',
			},
			reset_progress: {
				lesson: window.woo_learners_general_data.reset_lesson_confirm,
				course: window.woo_learners_general_data.reset_course_confirm,
				action: 'reset_user_post',
			},
		};

		if ( typeof actions[ current_action ] === 'undefined' ) {
			return;
		}

		confirm_message = actions[ current_action ][ post_type ];

		if ( ! confirm( confirm_message ) ) {
			return;
		}

		var table_row = jQuery( this ).closest( 'tr' );

		if ( user_id && post_id && post_type ) {
			dataToPost += 'user_id=' + user_id;
			dataToPost += '&post_id=' + post_id;
			dataToPost += '&post_type=' + post_type;

			jQuery.post(
				ajaxurl,
				{
					action: actions[ current_action ].action,
					data: dataToPost,
					security:
						window.woo_learners_general_data.modify_user_post_nonce,
				},
				function ( response ) {
					if ( response ) {
						if ( 'removed' === response ) {
							table_row.fadeTo( 400, 0, function () {
								table_row.remove();
							} );

							return;
						}
						switch ( current_action ) {
							case 'reset_progress':
								table_row
									.find( '.graded' )
									.html( window.slgL10n.inprogress )
									.removeClass( 'graded' )
									.addClass( 'in-progress' );
								break;
						}
					}
				}
			);

			window.sensei_log_event( 'learner_management_' + current_action );
		}
	} );

	jQuery( 'select#add_learner_search' ).select2( {
		minimumInputLength: 3,
		placeholder: window.woo_learners_general_data.selectplaceholder,
		width: '300px',

		ajax: {
			// in wp-admin ajaxurl is supplied by WordPress and is available globaly
			url: window.ajaxurl,
			dataType: 'json',
			cache: true,
			id: function ( bond ) {
				return bond._id;
			},
			data: function ( params ) {
				// page is the one-based page number tracked by Select2
				return {
					term: params.term, //search term
					page: params.page || 1,
					action: 'sensei_json_search_users',
					security:
						window.woo_learners_general_data.search_users_nonce,
					default: '',
				};
			},
			processResults: function ( users, page ) {
				var validUsers = [];
				jQuery.each( users, function ( i, val ) {
					if ( ! jQuery.isEmptyObject( val ) ) {
						validUsers.push( { id: i, text: val } );
					}
				} );
				// wrap the users inside results for select 2 usage
				return {
					results: validUsers,
					page: page,
				};
			},
		},
	} ); // end select2

	/***************************************************************************************************
	 * 	3 - Load Select2 Dropdowns.
	 ***************************************************************************************************/

	// Learner Management Drop Downs
	if ( jQuery( '#course-category-options' ).exists() ) {
		jQuery( '#course-category-options' ).select2();
	}
} );
