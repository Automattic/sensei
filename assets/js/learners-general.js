jQuery( document ).ready( function ( $ ) {
	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/

	/**
	 * exists checks if selector exists
	 * @since  1.6.0
	 * @return {boolean} Whether the selector matches any elements.
	 */
	jQuery.fn.exists = function () {
		return this.length > 0;
	};

	jQuery( '.edit-date-date-picker' ).datepicker( {
		// The space and colon characters are added to allow users typing a datetime manually.
		dateFormat: 'yy-mm-dd :',
		onSelect( newDate ) {
			const oldDate = $( this ).attr( 'value' ).split( ' ' );

			if ( newDate.indexOf( ' :' ) > -1 ) {
				newDate = newDate.substring( 0, newDate.length - 2 );
			}

			if ( oldDate[ 1 ] ) {
				newDate = newDate + ' ' + oldDate[ 1 ];
			}

			$( this ).val( newDate );
		},
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
		content() {
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
		const dataToPost = 'course_cat=' + jQuery( this ).val();

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
		const $this = $( this );
		const userId = $this.attr( 'data-user-id' );
		const postId = $this.attr( 'data-post-id' );
		const postType = $this.attr( 'data-post-type' );
		const commentId = $this.attr( 'data-comment-id' );
		const newDates = {};

		$this
			.parents( 'tr' )
			.find( '.edit-date-date-picker' )
			.each( ( index, element ) => {
				newDates[ element.getAttribute( 'data-name' ) ] = element.value;
			} );

		if (
			! userId ||
			! postId ||
			! postType ||
			! commentId ||
			Object.keys( newDates ).length === 0
		) {
			return;
		}

		const dataToPost = {
			user_id: userId,
			post_id: postId,
			post_type: postType,
			comment_id: commentId,
			new_dates: newDates,
		};

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
		const current_action = jQuery( this ).attr( 'data-action' );

		const actions = {
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
		const action = actions[ current_action ];

		if ( typeof action === 'undefined' ) {
			return;
		}

		const confirm_message = action.message;

		if ( ! confirm( confirm_message ) ) {
			event.preventDefault();
		} else {
			const provider = jQuery( this ).attr( 'data-provider' );
			const properties = provider ? { provider } : null;
			window.sensei_log_event( action.eventName, properties );
		}
	} );

	jQuery( '.learner-async-action' ).click( function () {
		let dataToPost = '';

		const current_action = jQuery( this ).attr( 'data-action' );

		let confirm_message =
			window.woo_learners_general_data.remove_generic_confirm;

		const actions = {
			remove_progress: {
				lesson: window.woo_learners_general_data
					.remove_from_lesson_confirm,
				course: window.woo_learners_general_data
					.remove_progress_confirm,
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

		const post_type = jQuery( this ).attr( 'data-post-type' );

		confirm_message = actions[ current_action ][ post_type ];

		if ( ! confirm( confirm_message ) ) {
			return;
		}

		const user_id = jQuery( this ).attr( 'data-user-id' );
		const post_id = jQuery( this ).attr( 'data-post-id' );
		const table_row = jQuery( this ).closest( 'tr' );

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

	const $learnerSearchSelect = jQuery( 'select#add_learner_search' );
	const $learnerAddToCourseSubmitButton = jQuery(
		"[name='add_learner_submit']"
	).first();
	const $learnerSearchboxFormContainer = jQuery(
		'.sensei-learners-extra .add-student-form-container'
	);
	$learnerSearchSelect.select2( {
		minimumInputLength: 3,
		placeholder: window.woo_learners_general_data.selectplaceholder,
		width: '100%',
		ajax: {
			// in wp-admin ajaxurl is supplied by WordPress and is available globaly
			url: window.ajaxurl,
			dataType: 'json',
			cache: true,
			id( bond ) {
				return bond._id;
			},
			data( params ) {
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
			processResults( users, page ) {
				const validUsers = [];
				jQuery.each( users, function ( i, val ) {
					if ( ! jQuery.isEmptyObject( val ) ) {
						validUsers.push( { id: i, text: val } );
					}
				} );
				// wrap the users inside results for select 2 usage
				return {
					results: validUsers,
					page,
				};
			},
		},
	} ); // end select2
	$learnerSearchSelect.on( 'change.select2', () => {
		const isNoStudentSelected =
			$learnerSearchSelect.select2( 'data' ).length < 1;
		$learnerAddToCourseSubmitButton.prop( 'disabled', isNoStudentSelected );
		if ( isNoStudentSelected ) {
			$learnerSearchboxFormContainer.addClass( 'student-search-empty' );
		} else {
			$learnerSearchboxFormContainer.removeClass(
				'student-search-empty'
			);
		}
	} );
	// For mobile devices (below 783px) put the filters and bulk actions below the table, else keep above.
	const $bulkActionContainer = $(
			'.tablenav.top > .sensei-student-bulk-actions__wrapper'
		).first(),
		$tableBottomActionContainer = $( '.tablenav.bottom > .tablenav-pages' ),
		$tableTopActionContainer = $( '.tablenav.top > .tablenav-pages' ),
		$themeContainer = $( '#woothemes-sensei' ),
		placeElementsBasedOnScreenSize = () => {
			const width = $( window ).width();
			const $targetContainer =
				width < 783
					? $tableBottomActionContainer
					: $tableTopActionContainer;
			const themeDivPosition = width < 783 ? 'inherit' : '';
			if ( $targetContainer.has( $bulkActionContainer ).length ) {
				return;
			}
			$targetContainer.before( $bulkActionContainer );
			$themeContainer.css( { position: themeDivPosition } );
		};
	placeElementsBasedOnScreenSize();
	$( window ).resize( placeElementsBasedOnScreenSize );
	/***************************************************************************************************
	 * 	3 - Load Select2 Dropdowns.
	 ***************************************************************************************************/

	// Learner Management Drop Downs
	if ( jQuery( '#course-category-options' ).exists() ) {
		jQuery( '#course-category-options' ).select2();
	}
} );
