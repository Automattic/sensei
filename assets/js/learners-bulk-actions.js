jQuery( document ).ready( function () {
	var $ = jQuery.noConflict(),
		_map = function ( arr, fn ) {
			var result = [];
			$.each( arr, function ( i, v ) {
				result.push( fn( v ) );
			} );
			return result;
		},
		_filter = function ( arr, pred ) {
			var result = [];
			$.each( arr, function ( i, v ) {
				if ( pred( v ) ) {
					result.push( v );
				}
			} );
			return result;
		};

	var bulkUserActions = ( function () {
		var selectedUserIds = [];
		var courseIds = [];
		var bulkAction = '';
		var validTemplate = {
			isValid: true,
			reason: '',
		};

		return {
			updateSelectedUserIdsFromCheckbox: function ( $checkbox ) {
				var val = parseInt( $checkbox.val(), 10 ),
					arrayIndex = selectedUserIds.indexOf( val );
				if ( $checkbox.is( ':checked' ) ) {
					if ( arrayIndex < 0 ) {
						selectedUserIds.push( val );
					}
				} else {
					if ( arrayIndex > -1 ) {
						selectedUserIds.splice( arrayIndex, 1 );
					}
				}

				return this;
			},
			getUserIds: function () {
				return selectedUserIds;
			},
			setAction: function ( ac ) {
				bulkAction = ac;
				return this;
			},
			setCourseIds: function ( newCourseIds ) {
				courseIds = _map( newCourseIds, function ( v ) {
					return parseInt( v, 10 );
				} );
				return this;
			},
			resetSelectedUserIds: function () {
				selectedUserIds = [];
				return this;
			},
			resetAll: function () {
				this.resetSelectedUserIds();
				courseIds = [];
				bulkAction = '';
				return this;
			},
			validator: function () {
				return {
					validateBulkAction: function () {
						if ( bulkAction == '' || bulkAction == 0 ) {
							return {
								isValid: false,
								reason: 'Select an action',
							};
						}
						return validTemplate;
					},
					validateCourseIds: function () {
						if (
							_filter( courseIds, function ( v ) {
								return ! isNaN( v );
							} ).length === 0
						) {
							return {
								isValid: false,
								reason: 'Select a course',
							};
						}
						return validTemplate;
					},
					validateSelectedUserIds: function () {
						if ( selectedUserIds.length === 0 ) {
							return {
								isValid: false,
								reason: 'Select some learners',
							};
						}
						return validTemplate;
					},
					validate: function () {
						var validations = [
								this.validateSelectedUserIds,
								this.validateBulkAction,
								this.validateCourseIds,
							],
							currentValidatorResult;

						while ( validations.length > 0 ) {
							currentValidatorResult = validations
								.shift()
								.call( this );
							if ( ! currentValidatorResult.isValid ) {
								return currentValidatorResult;
							}
						}
						return validTemplate;
					},
				};
			},
			validate: function () {
				return this.validator().validate();
			},
		};
	} )();

	( function ( bulkUserActions ) {
		const { __ } = wp.i18n;
		var $hiddenSelectedUserIdsField = $( '#bulk-action-user-ids' ),
			$actionSelector = $( '#bulk-action-selector-top' ),
			$courseSelect = $( '.sensei-course-select' ),
			$bulkActionSelect = $( '.sensei-bulk-action-select' ),
			$selectUserCheckboxes = $( '.sensei_user_select_id' ),
			$cbSelectAll = $( '#cb-select-all-1' ),
			$moreLink = $( '.sensei-students__enrolled-courses-more-link' ),
			$cbSelectAllTwo = $( '#cb-select-all-2' ),
			$modalToggle = $( '#sensei-bulk-learner-actions-modal-toggle' );

		var hookSelectAll = function ( $selectAll, $otherSelectAll ) {
			$selectAll.on( 'click', function () {
				bulkUserActions.resetSelectedUserIds();
				if ( $selectAll.is( ':checked' ) ) {
					$otherSelectAll.attr( 'checked', 'checked' );
					$selectUserCheckboxes.attr( 'checked', 'checked' );
					$selectUserCheckboxes.each( function ( i, checkbox ) {
						bulkUserActions.updateSelectedUserIdsFromCheckbox(
							$( checkbox )
						);
					} );
				} else {
					$selectUserCheckboxes.removeAttr( 'checked' );
					$otherSelectAll.removeAttr( 'checked' );
				}
				toggleSelectCoursesIfUsersAndBulkActionValid();
			} );
		};

		var toggleSelectCoursesIfUsersAndBulkActionValid = function () {
			var validator = bulkUserActions.validator(),
				bulkActionValidationResult = validator.validateBulkAction(),
				selectedUserIdsValidationResult = validator.validateSelectedUserIds();
			global.dispatchEvent(
				new CustomEvent( 'enableDisableCourseSelectionToggle', {
					detail: {
						enable:
							bulkActionValidationResult.isValid &&
							selectedUserIdsValidationResult.isValid,
					},
				} )
			);
			$hiddenSelectedUserIdsField.val(
				JSON.stringify( bulkUserActions.getUserIds() )
			);
		};

		$courseSelect.select2( {
			placeholder:
				window.sensei_learners_bulk_data.select_course_placeholder,
			width: '200px',
		} );

		$bulkActionSelect.select2( {
			minimumResultsForSearch: -1,
			width: '200px',
		} );

		$selectUserCheckboxes.on( 'change', function ( evt ) {
			var $checkbox = $( this );
			evt.preventDefault();
			evt.stopPropagation();
			bulkUserActions.updateSelectedUserIdsFromCheckbox( $checkbox );
			toggleSelectCoursesIfUsersAndBulkActionValid();
		} );

		hookSelectAll( $cbSelectAll, $cbSelectAllTwo );
		hookSelectAll( $cbSelectAllTwo, $cbSelectAll );

		$modalToggle.attr( 'disabled', true );

		$moreLink.on( 'click', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			$( event.target )
				.addClass( 'hidden' )
				.prev()
				.removeClass( 'hidden' );

			let $userId = $( event.target ).attr( 'data-user-id' );
			let $dataNonce = $( event.target ).attr( 'data-nonce' );
			let $hiddenPosts = $( event.target ).prev();

			let data = {
				action: 'get_course_list',
				user_id: $userId,
				nonce: $dataNonce,
			};

			$.ajax( {
				type: 'POST',
				url: ajax_object.ajax_url,
				data: data,
				success: function ( data ) {
					$hiddenPosts.append( data.data );
				},
				error: function ( errorThrown ) {
					$hiddenPosts.append(
						'<p>' +
							__(
								'There was an error fetching courses: ',
								'sensei-lms'
							) +
							errorThrown.statusText +
							': ' +
							errorThrown.status +
							'</p>'
					);
				},
			} );
		} );

		$actionSelector.on( 'change', function () {
			bulkUserActions.setAction( $actionSelector.val().trim() );
			toggleSelectCoursesIfUsersAndBulkActionValid();
		} );
	} )( bulkUserActions );
} );
