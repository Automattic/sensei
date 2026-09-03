jQuery( document ).ready( function () {
	const $ = jQuery.noConflict(),
		_map = function ( arr, fn ) {
			const result = [];
			$.each( arr, function ( i, v ) {
				result.push( fn( v ) );
			} );
			return result;
		},
		_filter = function ( arr, pred ) {
			const result = [];
			$.each( arr, function ( i, v ) {
				if ( pred( v ) ) {
					result.push( v );
				}
			} );
			return result;
		};

	const bulkUserActions = ( function () {
		let selectedUserIds = [];
		let courseIds = [];
		let bulkAction = '';
		const validTemplate = {
			isValid: true,
			reason: '',
		};

		return {
			updateSelectedUserIdsFromCheckbox( $checkbox ) {
				const val = parseInt( $checkbox.val(), 10 ),
					arrayIndex = selectedUserIds.indexOf( val );
				if ( $checkbox.is( ':checked' ) ) {
					if ( arrayIndex < 0 ) {
						selectedUserIds.push( val );
					}
				} else if ( arrayIndex > -1 ) {
					selectedUserIds.splice( arrayIndex, 1 );
				}

				return this;
			},
			getUserIds() {
				return selectedUserIds;
			},
			setAction( ac ) {
				bulkAction = ac;
				return this;
			},
			setCourseIds( newCourseIds ) {
				courseIds = _map( newCourseIds, function ( v ) {
					return parseInt( v, 10 );
				} );
				return this;
			},
			resetSelectedUserIds() {
				selectedUserIds = [];
				return this;
			},
			resetAll() {
				this.resetSelectedUserIds();
				courseIds = [];
				bulkAction = '';
				return this;
			},
			validator() {
				return {
					validateBulkAction() {
						if ( bulkAction == '' || bulkAction == 0 ) {
							return {
								isValid: false,
								reason: 'Select an action',
							};
						}
						return validTemplate;
					},
					validateCourseIds() {
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
					validateSelectedUserIds() {
						if ( selectedUserIds.length === 0 ) {
							return {
								isValid: false,
								reason: 'Select some learners',
							};
						}
						return validTemplate;
					},
					validate() {
						const validations = [
							this.validateSelectedUserIds,
							this.validateBulkAction,
							this.validateCourseIds,
						];
						let currentValidatorResult;

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
			validate() {
				return this.validator().validate();
			},
		};
	} )();

	( function () {
		const { __ } = wp.i18n;
		const $hiddenSelectedUserIdsField = $( '#bulk-action-user-ids' ),
			$actionSelector = $( '#bulk-action-selector-top' ),
			$courseSelect = $( '.sensei-course-select' ),
			$bulkActionSelect = $( '.sensei-bulk-action-select' ),
			$selectUserCheckboxes = $( '.sensei_user_select_id' ),
			$cbSelectAll = $( '#cb-select-all-1' ),
			$moreLink = $( '.sensei-students__enrolled-courses-more-link' ),
			$cbSelectAllTwo = $( '#cb-select-all-2' ),
			$modalToggle = $( '#sensei-bulk-learner-actions-modal-toggle' );

		const hookSelectAll = function ( $selectAll, $otherSelectAll ) {
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

		const toggleSelectCoursesIfUsersAndBulkActionValid = function () {
			const validator = bulkUserActions.validator(),
				bulkActionValidationResult = validator.validateBulkAction(),
				selectedUserIdsValidationResult =
					validator.validateSelectedUserIds();
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
			const $checkbox = $( this );
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

			const $userId = $( event.target ).attr( 'data-user-id' );
			const $dataNonce = $( event.target ).attr( 'data-nonce' );
			const $hiddenPosts = $( event.target ).prev();

			const data = {
				action: 'get_course_list',
				user_id: $userId,
				nonce: $dataNonce,
			};

			$.ajax( {
				type: 'POST',
				url: ajax_object.ajax_url,
				data,
				success( response ) {
					$hiddenPosts.append( response.data );
				},
				error( errorThrown ) {
					$hiddenPosts.append(
						'<p>' +
							__(
								'There was an error fetching courses:',
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
