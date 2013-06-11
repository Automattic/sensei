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

	/**
	 * Validation of input fields - Add Course.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery.fn.validateCourseInput = function( action ) {
 		// Check for empty course titles
 	    if ( jQuery( '#course-title' ).val().replace(/^\s+|\s+$/g, "").length != 0 ) {
 	    	return true;
 	    } else {
 	    	return false;
 	    }
 	}

	/**
	 * Validation of input fields - Add, Edit Question.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery.fn.validateQuestionInput = function( action, jqueryObject ) {
 		// Validate Actions
 		if ( 'add' == action ) {
 			// Check for empty questions
 	    	if ( jQuery( '#add_question' ).val().replace(/^\s+|\s+$/g, "").length != 0 ) {
 	    		return true;
 	    	} else {
 	    		return false;
 	    	}
 	    } else if ( 'edit' == action ) {
 			// Check for empty questions
 			var tableRowId = jqueryObject.parent('td').parent('tr').prev('tr').find('td:first').text();
 			if ( jQuery( '#question_' + tableRowId ).val().replace(/^\s+|\s+$/g, "").length != 0 ) {
 	    		return true;
 	    	} else {
 	    		return false;
 	    	}
 	    } else {
 			// Default
 			return false
 		}
 	}

	/**
	 * Sets all Edit Question areas in the Questions table to hidden.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery.fn.resetQuestionTable = function() {
 	    jQuery( 'tr.question-quick-edit' ).each( function() {
			if ( !jQuery(this).hasClass( 'hidden' ) ) {
				jQuery(this).addClass('hidden');
			}
		});
 	}

 	/**
	 * Resets the values of the Add Question form to nothing.
	 *
	 * @since 1.0.0
	 * @access public
	 */
 	jQuery.fn.resetAddQuestionForm = function() {
 	    jQuery( '#add-new-question' ).find('div').children('input').each( function() {
			if ( jQuery( this ).attr( 'type' ) != 'radio' ) {
				jQuery(this).attr( 'value', '' );
			} // End If Statement
		});
		jQuery( '#add-new-question' ).find('div').children('textarea').each( function() {
			jQuery(this).attr( 'value', '' );
		});
 	}

 	/**
	 * Checks if the quiz can be automatically graded
	 *
	 * @since 1.3.0
	 * @access public
	 */
 	jQuery.fn.checkQuizGradeType = function( latest_questionType ) {

 		// Fetch all current question types
 		var questionType;
 		var types = []
 		jQuery( '#add-question-metadata > table > tbody > tr input.question_type' ).each( function() {
			questionType = jQuery( this ).val();
			types.push( questionType );
		});

		// Add latest question to array if it exists
		if( latest_questionType ) {
			types.push( latest_questionType );
		}

		var currentType;
		var disableAuto = false;
		for( var i = 0; i < types.length; i++ ) {
		    currentType = types[i];

		    if( ! disableAuto ) {
			    switch ( currentType ) {
					case 'multiple-choice':
						disableAuto = false;
					break;
					case 'boolean':
						disableAuto = false;
					break;
					case 'gap-fill':
						disableAuto = true;
					break;
					case 'essay-paste':
						disableAuto = true;
					break;
					case 'multi-line':
						disableAuto = true;
					break;
					case 'single-line':
						disableAuto = true;
					break;
					default :
						disableAuto = false;
					break;
				} // End Switch Statement
			}

		}

		// Disable/enable field based on question types
		if( disableAuto ) {
			jQuery( 'input#quiz_grade_type' ).prop( 'checked', false );
			jQuery( 'input#quiz_grade_type' ).attr( 'disabled', 'disabled' );
			jQuery( 'input#quiz_grade_type_disabled' ).val( 'disabled' );
		} else {
			jQuery( 'input#quiz_grade_type' ).removeAttr( 'disabled' );
			jQuery( 'input#quiz_grade_type_disabled' ).val( 'enabled' );
		}

		// Save quiz grade type
		jQuery.fn.saveQuizGradeType();
 	}

 	/**
	 * Updates the Number of Questions counter.
	 *
	 * @since 1.0.0
	 * @access public
	 */
 	jQuery.fn.updateQuestionCount = function( increment, operator ) {
 		// Get current value
 		var currentValue = parseInt( jQuery( '#question_counter' ).attr( 'value' ) );
 		var newValue = currentValue;
 		// Increment or Decrement
 		if ( operator == '-' ) {
 			newValue = currentValue - increment;
 		} else {
 			newValue = currentValue + increment;
 		}
 		// Set new value
 	    jQuery( '#question_counter' ).attr( 'value', newValue );
 	    if ( newValue > 0 ) {
 	    	if ( !jQuery( '#no-questions-message' ).hasClass( 'hidden' ) ) {
 	    		jQuery( '#no-questions-message' ).addClass( 'hidden' );
 	    	}
 	    } else {
 	    	jQuery( '#no-questions-message' ).removeClass( 'hidden' );
 	    }
 	}

 	/**
 	 * Save quiz grade type
 	 *
 	 * @since 1.3.0
 	 * access public
 	 */
 	jQuery.fn.saveQuizGradeType = function() {

 		var quiz_grade_type = jQuery( 'input#quiz_grade_type' ).is( ':checked' ) ? 'auto' : 'manual';
 		var quiz_grade_type_disabled = jQuery( 'input#quiz_grade_type_disabled' ).val();

 		var dataToPost = 'quiz_grade_type' + '=' + quiz_grade_type;
 		dataToPost += '&quiz_grade_type_disabled' + '=' + quiz_grade_type_disabled;
 		dataToPost += '&quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );

 		jQuery.post(
			ajaxurl,
			{
				action : 'lesson_update_grade_type',
				lesson_update_grade_type_nonce : woo_localized_data.lesson_update_grade_type_nonce,
				data : dataToPost
			},
			function( response ) {}
		);
		return false;
 	}

 	/**
	 * JS version of PHP htmlentities.
	 *
	 * @since 1.0.8
	 * @access public
	 */
 	jQuery.fn.htmlentities = function( str ) {
 		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	jQuery.fn.ucwords = function( str ) {
		str = str.toLowerCase();
		str = str.replace( '-', ' ' );
		str = str.replace( 'boolean', 'True/False' );
	    return str.replace(/(^([a-zA-Z\p{M}]))|([ -][a-zA-Z\p{M}])/g,
	        function($1){
	            return $1.toUpperCase();
	        });
	}

	/***************************************************************************************************
	 * 	2 - Lesson Quiz Functions.
	 ***************************************************************************************************/

	/**
	 * Add Quiz Click Event.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-quiz-main button.add_quiz' ).click( function() {
		// Display add quiz form and hide add quiz button
		jQuery( '#add-quiz-metadata' ).show();
		jQuery( '#add-quiz-main > p:first' ).hide();
	});

	/**
	 * Gap Fill text change events
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( 'input.gapfill-field' ).each( function() {
		// Handles change events like paste, tabbing, and click change selectors
		jQuery( this ).change(function() {
			var gapPre = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_pre]').val();
			var gapGap = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_gap]').val();
			var gapPost = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_post]').val();
			jQuery( this ).parent('div').find('p.gapfill-preview').html( gapPre + ' <u>' + gapGap + '</u> ' + gapPost );
		});
		// Handles the pressing up of the key, general typing
		jQuery( this ).keyup(function() {
			var gapPre = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_pre]').val();
			var gapGap = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_gap]').val();
			var gapPost = jQuery( this ).parent('div').find('input[name=add_question_right_answer_gapfill_post]').val();
			jQuery( this ).parent('div').find('p.gapfill-preview').html( gapPre + ' <u>' + gapGap + '</u> ' + gapPost );
		});
	});

	/**
	 * Quiz grade type checkbox change event
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '#add-quiz-metadata' ).on( 'change', '#quiz_grade_type', function() {
		jQuery.fn.saveQuizGradeType();
	});

	/***************************************************************************************************
	 * 	3 - Course Functions.
	 ***************************************************************************************************/

	/**
	 * Add Course Click Event.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#lesson-course-add' ).click( function() {
		// Display the add course panel and hide the add course link
		jQuery( '#lesson-course-actions' ).hide();
		jQuery( '#lesson-course-details' ).removeClass( 'hidden' );
	});

	/**
	 * Cancel Events Click Event - add course.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#lesson-course-details p' ).on( 'click', 'a.lesson_course_cancel', function() {
		// Hide the add course panel and show the add course link
		jQuery( '#lesson-course-actions' ).show();
		jQuery( '#lesson-course-details' ).addClass( 'hidden' );
	});

	/**
	 * Save Course Click Event - Ajax.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#lesson-course-details p' ).on( 'click', 'a.lesson_course_save', function() {
	 	// Validate Inputs
	 	var validInput = jQuery.fn.validateCourseInput();
		if ( validInput ) {
			//var ajaxLoaderIcon = jQuery( this ).parent().find( '.ajax-loading' );
	 		//ajaxLoaderIcon.css( 'visibility', 'visible' ).fadeTo( 'slow', 1, function () {
	 			// Setup data
	 			var dataToPost = '';
	 			dataToPost += 'course_prerequisite' + '=' + jQuery( '#course-prerequisite-options' ).val();
	 			dataToPost += '&course_woocommerce_product' + '=' + jQuery( '#course-woocommerce-product-options' ).val();
	 			dataToPost += '&course_category' + '=' + jQuery( '#course-category-options' ).val();
	 			dataToPost += '&course_title' + '=' + jQuery( '#course-title' ).attr( 'value' );
	 			dataToPost += '&course_content' + '=' + jQuery( '#course-content' ).attr( 'value' );
	 			dataToPost += '&action=add';
	 			// Perform the AJAX call.
	 			jQuery.post(
	 				ajaxurl,
	 				{
	 					action : 'lesson_add_course',
	 					lesson_add_course_nonce : woo_localized_data.lesson_add_course_nonce,
	 					data : dataToPost
	 				},
	 				function( response ) {
	 					//ajaxLoaderIcon.fadeTo( 'slow', 0, function () {
	 					//	jQuery( this ).css( 'visibility', 'hidden' );
	 					//});
	 					// Check for a course id
	 					if ( 0 < response ) {
	 						jQuery( '#lesson-course-actions' ).show();
							jQuery( '#lesson-course-details' ).addClass( 'hidden' );
							jQuery( '#lesson-course-options' ).append(jQuery( '<option></option>' ).attr( 'value' , response ).text(jQuery( '#course-title' ).attr( 'value' )));
							jQuery( '#lesson-course-options' ).val( response );
							jQuery( '#lesson-course-options' ).trigger( 'liszt:updated' );
	 					} else {
	 						// TODO - course creation fail message
	 					}
	 				}
	 			);
	 			return false; // TODO - move this below the next bracket when doing the ajax loader
	 	//});
		} else {
			jQuery( '#course-title' ).focus();
			// TODO - add error message
		}
	});

	/***************************************************************************************************
	 * 	4 - Quiz Question Functions.
	 ***************************************************************************************************/

	/**
	 * Add Question Click Event.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-question-actions' ).on( 'change', 'select.question-type-select', function() {
		// Show the correct Question Type
		// REFACTOR
		var questionType = jQuery(this).val();
		jQuery( '#add-new-question' ).find( 'div.question_default_fields' ).hide();
		jQuery( '#add-new-question' ).find( 'div.question_boolean_fields' ).hide();
		jQuery( '#add-new-question' ).find( 'div.question_gapfill_fields' ).hide();
		jQuery( '#add-new-question' ).find( 'div.question_essay_fields' ).hide();
		jQuery( '#add-new-question' ).find( 'div.question_multiline_fields' ).hide();
		jQuery( '#add-new-question' ).find( 'div.question_singleline_fields' ).hide();
		switch ( questionType ) {
			case 'multiple-choice':
				jQuery( '#add-new-question' ).find( 'div.question_default_fields' ).show();
			break;
			case 'boolean':
				jQuery( '#add-new-question' ).find( 'div.question_boolean_fields' ).show();
			break;
			case 'gap-fill':
				jQuery( '#add-new-question' ).find( 'div.question_gapfill_fields' ).show();
			break;
			case 'essay-paste':
				jQuery( '#add-new-question' ).find( 'div.question_essay_fields' ).show();
			break;
			case 'multi-line':
				jQuery( '#add-new-question' ).find( 'div.question_multiline_fields' ).show();
			break;
			case 'single-line':
				jQuery( '#add-new-question' ).find( 'div.question_singleline_fields' ).show();
			break;
			default :
				jQuery( '#add-new-question' ).find( 'div.question_default_fields' ).show();
			break;
		} // End Switch Statement
	});

	/**
	 * Change Question Type Event.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-question-actions' ).on( 'click', 'button.add_question_answer', function() {
		// Prep and Display add question panel and hide the add question button
		jQuery( this ).addClass('hidden');
		jQuery( '#add-new-question' ).removeClass( 'hidden' );
		jQuery.fn.resetQuestionTable();
		jQuery( this ).removeAttr('style');
	});

	/**
	 * Edit Question Click Event.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-question-metadata' ).on( 'click', 'a.question_table_edit', function() {
		// Display the question for edit
		var questionId = jQuery(this).parent('td').parent('tr').find('td:first').text();
		jQuery( '#add-question-actions button.add_question_answer' ).removeClass('hidden');
 		// Hide the add question form and prep the table
		jQuery( '#add-new-question' ).addClass( 'hidden' );
	 	jQuery.fn.resetAddQuestionForm();
	 	jQuery.fn.resetQuestionTable();
		jQuery( '#question_' + questionId ).parent('div').parent('td').parent('tr').removeClass('hidden');
		jQuery( '#question_' + questionId ).focus();
	});

	/**
	 * Cancel Events Click Event - add question.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-new-question' ).on( 'click', 'a.lesson_question_cancel', function() {
		// Hide the add question panel and show the add question button
		jQuery( '#add-question-actions button.add_question_answer' ).removeClass('hidden');
		jQuery( '#add-new-question' ).addClass( 'hidden' );
		jQuery.fn.resetQuestionTable();
	});

	/**
	 * Cancel Events Click Event - edit question.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-question-metadata' ).on( 'click', 'a.lesson_question_cancel', function() {
		// Hide the edit question panel
		var tableRowId = jQuery( this ).parent('td').parent('tr').prev('tr').find('td:first').text();
		jQuery( '#question_' + tableRowId ).parent('div').parent('td').parent('tr').addClass( 'hidden' );
	});

	/**
	 * Add Question Save Click Event - Ajax.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-new-question' ).on( 'click', 'a.add_question_save', function() {
		var dataToPost = '';
		var questionType = 'multiple-choice';
		var radioValue = 'true';
	 	// Validate Inputs
		var validInput = jQuery.fn.validateQuestionInput( 'add', jQuery(this) );
		if ( validInput ) {
			// Setup data to post
	 		dataToPost += 'quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );
	 		dataToPost += '&action=add';
			if ( jQuery( '#add-question-type-options' ).val() != '' ) {
	 			questionType = jQuery( '#add-question-type-options' ).val();
	 		} // End If Statement
	 		var divFieldsClass = 'question_default_fields';
	 		switch ( questionType ) {
				case 'multiple-choice':
					divFieldsClass = 'question_default_fields';
				break;
				case 'boolean':
					divFieldsClass = 'question_boolean_fields';
				break;
				case 'gap-fill':
					divFieldsClass = 'question_gapfill_fields';
				break;
				case 'essay-paste':
					divFieldsClass = 'question_essay_fields';
				break;
				case 'multi-line':
					divFieldsClass = 'question_multiline_fields';
				break;
				case 'single-line':
					divFieldsClass = 'question_singleline_fields';
				break;
				default :
					divFieldsClass = 'question_default_fields';
				break;
			} // End Switch Statement
			// Handle Required Fields
			jQuery( '#add-new-question' ).find( 'div.question_required_fields' ).children( 'input' ).each( function() {
	 			dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + jQuery( this ).attr( 'value' );
 			});
	 		// Handle Question Input Fields
	 		var radioCount = 0;
	 		jQuery( '#add-new-question' ).find( 'div.' + divFieldsClass ).children( 'input' ).each( function() {
	 			if ( jQuery( this ).attr( 'type' ) == 'radio' ) {
	 				// Only get the selected radio button
	 				if ( radioCount == 0 ) {
	 					radioValue = jQuery( 'input[name=' + jQuery( this ).attr( 'name' ) + ']:checked' ).attr( 'value' );
	 					dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + radioValue;
	 					radioCount++;
	 				} // End If Statement
 				} else {
 					dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + jQuery( this ).attr( 'value' );
 				} // End If Statement
	 		});
	 		// Handle Question Textarea Fields
	 		if ( jQuery( '#add_question_right_answer_essay' ).val() != '' && divFieldsClass == 'question_essay_fields' ) {
	 			dataToPost += '&' + jQuery( '#add_question_right_answer_essay' ).attr( 'name' ) + '=' + jQuery( '#add_question_right_answer_essay' ).val();
	 		} // End If Statement
 			if ( jQuery( '#add_question_right_answer_multiline' ).val() != '' && divFieldsClass == 'question_multiline_fields' ) {
 				dataToPost += '&' + jQuery( '#add_question_right_answer_multiline' ).attr( 'name' ) + '=' + jQuery( '#add_question_right_answer_multiline' ).val();
	 		} // End If Statement
	 		dataToPost += '&' + 'question_type' + '=' + questionType;
	 		// Perform the AJAX call.
	 		jQuery.post(
	 		    ajaxurl,
	 		    {
	 		    	action : 'lesson_update_question',
	 		    	lesson_update_question_nonce : woo_localized_data.lesson_update_question_nonce,
	 		    	data : dataToPost
	 		    },
	 		    function( response ) {
	 		    	//ajaxLoaderIcon.fadeTo( 'slow', 0, function () {
	 		    	//	jQuery( this ).css( 'visibility', 'hidden' );
	 		    	//});
	 		    	// Check for a valid question id
	 		    	if ( 0 < response ) {
	 		    		// TODO - Add the question to the table, and clear the add form and hide it
	 		    		jQuery( '#add-question-actions button.add_question_answer' ).removeClass('hidden');
 						// If successful, hide the form and add to the table, clear the values
						jQuery( '#add-new-question' ).addClass( 'hidden' );
	 		    		jQuery.fn.updateQuestionCount( 1, '+' );
	 		    		var tableCount = parseInt( jQuery( '#question_counter' ).attr( 'value' ) );
	 		    		var questionId = response;
	 		    		var addQuestionText = jQuery.fn.htmlentities( jQuery( '#add_question' ).attr( 'value' ) );
	 		    		// TODO - Localize the english labels for translation
	 		    		var outputEditForm = '';
	 		    		outputEditForm += '<tr>';
    						outputEditForm += '<td class="table-count hidden">' + tableCount + '</td>';
				 		    outputEditForm += '<td>' + addQuestionText + '</td>';
				 		    outputEditForm += '<td>' + jQuery.fn.ucwords( questionType ) + '</td>';
				 		    outputEditForm += '<td><a title="Edit Question" href="#question_' + tableCount + '" class="question_table_edit">Edit</a>&nbsp;&nbsp;&nbsp;<a title="Delete Question" href="#add-question-metadata" class="question_table_delete">Delete</a></td>';
				 		outputEditForm += '</tr>';
				 		outputEditForm += '<tr class="question-quick-edit hidden">';
				 		    outputEditForm += '<td colspan="3">';
				 		    	outputEditForm += '<div class="question_required_fields">';
				 		    		outputEditForm += '<label>Question ' + tableCount + '</label> <input type="text" id="question_' + tableCount + '" name="question" value="' + addQuestionText + '" size="25" class="widefat">';
				 		    	outputEditForm += '</div>';
				 		    	switch ( questionType ) {
									case 'multiple-choice':
										var addQuestionRightText = jQuery.fn.htmlentities( jQuery( '#add_question_right_answer' ).attr( 'value' ) );
										var arrayCounter = 0;
					 		    		var addQuestionWrongText = new Array();
					 		    		jQuery( '#add-new-question input[name="question_wrong_answers[]"]' ).each( function() {
					 		    			addQuestionWrongText[arrayCounter] = jQuery.fn.htmlentities( jQuery(this).attr( 'value' ) );
					 		    			arrayCounter++;
					 		    		});
										outputEditForm += '<div class="question_default_fields">';
						 		    		outputEditForm += '<label>Right Answer</label> <input type="text" id="question_' + tableCount + '_right_answer" name="question_right_answer" value="' + addQuestionRightText + '" size="25" class="widefat">';
						 		    		outputEditForm += '<label>Wrong Answers</label> <input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[0] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[1] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[2] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[3] + '" size="25" class="widefat">';
						 		    	outputEditForm += '</div>';
									break;
									case 'boolean':
										outputEditForm += '<div class="question_boolean_fields">';
											var trueChecked = '';
											var falseChecked = '';
											if ( 'true' == radioValue ) {
												trueChecked = 'checked';
											} // End If Statement
						 		    		outputEditForm += '<input type="radio" name="question_' + questionId + '_right_answer_boolean" value="true" ' + trueChecked + ' />&nbsp;&nbsp;True&nbsp;&nbsp;&nbsp;&nbsp;';
						 		    		if ( 'false' == radioValue ) {
												falseChecked = 'checked';
											} // End If Statement
						 		    		outputEditForm += '<input type="radio" name="question_' + questionId + '_right_answer_boolean" value="false" ' + falseChecked + ' />&nbsp;&nbsp;False';
						 		    	outputEditForm += '</div>';
									break;
									case 'gap-fill':
										outputEditForm += '<div class="question_gapfill_fields">';
											outputEditForm += '<label>Right Answer</label> ';
						 		    		outputEditForm += '<input type="text" id="question_' + tableCount + '_add_question_right_answer_gapfill_pre" name="add_question_right_answer_gapfill_pre" value="' + jQuery( '#add_question_right_answer_gapfill_pre' ).val() + '" size="25" class="widefat">';
						 		    		outputEditForm += '<input type="text" id="question_' + tableCount  + '_add_question_right_answer_gapfill_gap" name="add_question_right_answer_gapfill_gap" value="' + jQuery( '#add_question_right_answer_gapfill_gap' ).val() + '" size="25" class="widefat">';
						 		    		outputEditForm += '<input type="text" id="question_' + tableCount  + '_add_question_right_answer_gapfill_post" name="add_question_right_answer_gapfill_post" value="' + jQuery( '#add_question_right_answer_gapfill_post' ).val() + '" size="25" class="widefat">';
						 		    	outputEditForm += '</div>';
									break;
									case 'essay-paste':
										outputEditForm += '<div class="question_essay_fields">';
											outputEditForm += '<label>Right Answer</label> ';
											outputEditForm += '<textarea id="question_' + tableCount + '_add_question_right_answer_essay" name="add_question_right_answer_essay" rows="15" cols="40" class="widefat">' + jQuery( '#add_question_right_answer_essay' ).val() + '</textarea>';
										outputEditForm += '</div>';
									break;
									case 'multi-line':
										outputEditForm += '<div class="question_multiline_fields">';
											outputEditForm += '<label>Right Answer</label> ';
											outputEditForm += '<textarea id="question_' + tableCount + '_add_question_right_answer_multiline" name="add_question_right_answer_multiline" rows="3" cols="40" class="widefat">' + jQuery( '#add_question_right_answer_multiline' ).val() + '</textarea>';
										outputEditForm += '</div>';
									break;
									case 'single-line':
										outputEditForm += '<div class="question_singleline_fields">';
											outputEditForm += '<label>Right Answer</label> ';
											outputEditForm += '<input type="text" id="question_' + tableCount  + '_add_question_right_answer_singleline" name="add_question_right_answer_singleline" value="' + jQuery( '#add_question_right_answer_singleline' ).val() + '" size="25" class="widefat">';
										outputEditForm += '</div>';
									break;
									default :
										var addQuestionRightText = jQuery.fn.htmlentities( jQuery( '#add_question_right_answer' ).attr( 'value' ) );
										var arrayCounter = 0;
					 		    		var addQuestionWrongText = new Array();
					 		    		jQuery( '#add-new-question input[name="question_wrong_answers[]"]' ).each( function() {
					 		    			addQuestionWrongText[arrayCounter] = jQuery.fn.htmlentities( jQuery(this).attr( 'value' ) );
					 		    			arrayCounter++;
					 		    		});
										outputEditForm += '<div class="question_default_fields">';
						 		    		outputEditForm += '<label>Right Answer</label> <input type="text" id="question_' + tableCount + '_right_answer" name="question_right_answer" value="' + addQuestionRightText + '" size="25" class="widefat">';
						 		    		outputEditForm += '<label>Wrong Answers</label> <input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[0] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[1] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[2] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[3] + '" size="25" class="widefat">';
						 		    	outputEditForm += '</div>';
									break;
								} // End Switch Statement
				 		    	outputEditForm += '<input type="hidden" name="question_type" id="question_' + tableCount + '_question_type" value="' + questionType + '">';
				 		    	outputEditForm += '<input type="hidden" name="question_id" id="question_' + tableCount + '_id" value="' + questionId + '">';
				 		    	outputEditForm += '<a title="Update Question" href="#add-question-metadata" class="question_table_save button button-highlighted">Update</a>&nbsp;&nbsp;&nbsp;';
								outputEditForm += '<a title="Cancel" href="#question-edit-cancel" class="lesson_question_cancel">Cancel</a>';
				 		    outputEditForm += '</td>';
				 		outputEditForm += '</tr>';
	 		    		jQuery( '#add-question-metadata table tbody' ).append( outputEditForm );
			    		jQuery.fn.resetAddQuestionForm();
			 			jQuery.fn.checkQuizGradeType( questionType );
	 		    	}
	 		    }
	 		);
	 		return false; // TODO - move this below the next bracket when doing the ajax loader
	 	} else {
			jQuery( '#add_question' ).focus();
			// TODO - add error message
		}
	});

	/**
	 * Edit Question Save Click Event - Ajax.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-question-metadata' ).on( 'click', 'a.question_table_save', function() {
	 	var dataToPost = '';
	 	var tableRowId = '';
	 	// Validate Inputs
	 	var validInput = jQuery.fn.validateQuestionInput( 'edit', jQuery(this) );
		if ( validInput ) {
			//var ajaxLoaderIcon = jQuery( this ).parent().find( '.ajax-loading' );
	 		//ajaxLoaderIcon.css( 'visibility', 'visible' ).fadeTo( 'slow', 1, function () {
	 			// Setup the data to post
	 			dataToPost += 'quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );
	 			dataToPost += '&action=save';
	 			jQuery( this ).parent( 'td' ).children( 'input' ).each( function() {
	 				dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + jQuery( this ).attr( 'value' );
	 			});
	 			tableRowId = jQuery( this ).parent('td').parent('tr').prev('tr').find('td:first').text();
	 			if ( jQuery( this ).parent().find( 'input.question_type' ).val() != '' ) {
		 			questionType = jQuery( this ).parent().find( 'input.question_type' ).val();
		 		} // End If Statement
		 		var divFieldsClass = 'question_default_fields';
		 		switch ( questionType ) {
					case 'multiple-choice':
						divFieldsClass = 'question_default_fields';
					break;
					case 'boolean':
						divFieldsClass = 'question_boolean_fields';
					break;
					case 'gap-fill':
						divFieldsClass = 'question_gapfill_fields';
					break;
					case 'essay-paste':
						divFieldsClass = 'question_essay_fields';
					break;
					case 'multi-line':
						divFieldsClass = 'question_multiline_fields';
					break;
					case 'single-line':
						divFieldsClass = 'question_singleline_fields';
					break;
					default :
						divFieldsClass = 'question_default_fields';
					break;
				} // End Switch Statement
				// Handle Required Fields
				jQuery( this ).parent().find( 'div.question_required_fields' ).children( 'input' ).each( function() {
		 			dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + jQuery( this ).attr( 'value' );
	 			});
		 		// Handle Question Input Fields
		 		var radioCount = 0;
		 		jQuery( this ).parent().find( 'div.' + divFieldsClass ).children( 'input' ).each( function() {
		 			if ( jQuery( this ).attr( 'type' ) == 'radio' ) {
		 				// Only get the selected radio button
		 				if ( radioCount == 0 ) {
		 					dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + jQuery( 'input[name=' + jQuery( this ).attr( 'name' ) + ']:checked' ).attr( 'value' );
		 					radioCount++;
		 				} // End If Statement
	 				} else {
	 					dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + jQuery( this ).attr( 'value' );
	 				} // End If Statement
		 		});
		 		// Handle Question Textarea Fields
		 		if ( jQuery(this).parent().find( 'div.' + divFieldsClass ).find( 'textarea' ).val() != '' && divFieldsClass == 'question_essay_fields' ) {
		 			dataToPost += '&' +  jQuery(this).parent().find( 'div.' + divFieldsClass ).find( 'textarea' ).attr( 'name' ) + '=' +  jQuery(this).parent().find( 'div.' + divFieldsClass ).find( 'textarea' ).val();
		 		} // End If Statement
		 		if ( jQuery(this).parent().find( 'div.' + divFieldsClass ).find( 'textarea' ).val() != '' && divFieldsClass == 'question_multiline_fields' ) {
		 			dataToPost += '&' +  jQuery(this).parent().find( 'div.' + divFieldsClass ).find( 'textarea' ).attr( 'name' ) + '=' +  jQuery(this).parent().find( 'div.' + divFieldsClass ).find( 'textarea' ).val();
		 		} // End If Statement
				dataToPost += '&' + 'question_type' + '=' + questionType;
				// Perform the AJAX call.
				jQuery.post(
	 				ajaxurl,
	 				{
	 					action : 'lesson_update_question',
	 					lesson_update_question_nonce : woo_localized_data.lesson_update_question_nonce,
	 					data : dataToPost
	 				},
	 				function( response ) {
	 					//ajaxLoaderIcon.fadeTo( 'slow', 0, function () {
	 					//	jQuery( this ).css( 'visibility', 'hidden' );
	 					//});
	 					if ( response ) {
	 						// Display the question for edit
	 						jQuery( '#add-question-metadata > table > tbody > tr' ).children('td').each( function() {
	 							if ( jQuery(this).text() == tableRowId ) {
	 								jQuery(this).next('td').text( jQuery( '#question_' + tableRowId ).attr('value') );
	 							}
	 						});
	 						jQuery( '#question_' + tableRowId ).parent('div').parent('td').parent('tr').addClass( 'hidden' );
	 					}
	 				}
	 			);
	 			return false; // TODO - move this below the next bracket when doing the ajax loader
	 	//});
		} else {
			tableRowId = jQuery( this ).parent('td').parent('tr').prev('tr').find('td:first').text();
			jQuery( '#question_' + tableRowId ).focus();
			// TODO - add error message
		}
	});

	/**
	 * Delete Question Click Event - Ajax.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-question-metadata' ).on( 'click', 'a.question_table_delete', function() {
	 	var dataToPost = '';
	 	var questionId = '';
	 	var tableRowId = '';
	 	// TODO - localize this delete message
	 	var confirmDelete = confirm( 'Are you sure you want to delete this question?' );
	 	if ( confirmDelete ) {
	 		//var ajaxLoaderIcon = jQuery( this ).parent().find( '.ajax-loading' );
	 		//ajaxLoaderIcon.css( 'visibility', 'visible' ).fadeTo( 'slow', 1, function () {
	 			// Setup data to post
	 			dataToPost += '&action=delete';
	 			jQuery( this ).parent( 'td' ).parent('tr').next('tr').find('td').children( 'input' ).each( function() {
	 				if ( jQuery( this ).attr( 'name' ) == 'question_id' ) {
	 					questionId = jQuery( this ).attr( 'value' );
	 					dataToPost += '&question_id' + '=' + jQuery( this ).attr( 'value' );
	 				} // End If Statement
	 			});
	 			tableRowId = jQuery( this ).parent('td').parent('tr').find('td:first').text();
	 			// Perform the AJAX call.
	 			jQuery.post(
	 				ajaxurl,
	 				{
	 					action : 'lesson_update_question',
	 					lesson_update_question_nonce : woo_localized_data.lesson_update_question_nonce,
	 					data : dataToPost
	 				},
	 				function( response ) {
	 					//ajaxLoaderIcon.fadeTo( 'slow', 0, function () {
	 					//	jQuery( this ).css( 'visibility', 'hidden' );
	 					//});
	 					if ( response ) {
	 						// Remove the html element for the deleted question
	 						jQuery( '#add-question-metadata > table > tbody > tr' ).children('td').each( function() {
	 							if ( jQuery(this).text() == tableRowId ) {
	 								jQuery(this).parent('tr').next('tr').remove();
	 								jQuery(this).parent('tr').remove();
								}
	 						});
	 						jQuery.fn.updateQuestionCount( 1, '-' );
	 						jQuery.fn.checkQuizGradeType( false );
	 						// TODO - renumber function for reuse when adding
	 					}
	 				}
	 			);
	 			return false; // TODO - move this below the next bracket when doing the ajax loader
	 	//});
		}
	});

	/***************************************************************************************************
	 * 	5 - Load Chosen Dropdowns.
	 ***************************************************************************************************/

	// Lessons Write Panel
	if ( jQuery( '#lesson-complexity-options' ).exists() ) { jQuery( '#lesson-complexity-options' ).chosen(); }
	if ( jQuery( '#lesson-prerequisite-options' ).exists() ) { jQuery( '#lesson-prerequisite-options' ).chosen(); }
	if ( jQuery( '#lesson-course-options' ).exists() ) { jQuery( '#lesson-course-options' ).chosen(); }
	if ( jQuery( '#course-prerequisite-options' ).exists() ) { jQuery( '#course-prerequisite-options' ).chosen(); }
	if ( jQuery( '#course-category-options' ).exists() ) { jQuery( '#course-category-options' ).chosen(); }
	// Courses Write Panel
	if ( jQuery( '#course-woocommerce-product-options' ).exists() && '-' != jQuery( '#course-woocommerce-product-options' ).val() ) { jQuery( '#course-woocommerce-product-options' ).chosen(); }
	if ( jQuery( '#course-prerequisite-options' ).exists() ) { jQuery( '#course-prerequisite-options' ).chosen(); }
	// Sensei Settings Panel
	jQuery( 'div.woothemes-sensei-settings form select' ).each( function() {
		if ( !jQuery( this ).hasClass( 'range-input' ) ) {
			jQuery( this ).chosen();
		} // End If Statement
	});

});