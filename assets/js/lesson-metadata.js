jQuery(document).ready( function($) {
	
	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/
	
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
 	    jQuery( '#add-new-question' ).children('input').each( function() {
			jQuery(this).attr( 'value', '' );
		});
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
	jQuery( '#lesson-course-details p ' ).on( 'click', 'a.lesson_course_cancel', function() {
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
	jQuery( '#lesson-course-details p ' ).on( 'click', 'a.lesson_course_save', function() {
	 	// Validate Inputs
	 	var validInput = jQuery.fn.validateCourseInput();
		if ( validInput ) {
			//var ajaxLoaderIcon = jQuery( this ).parent().find( '.ajax-loading' );
	 		//ajaxLoaderIcon.css( 'visibility', 'visible' ).fadeTo( 'slow', 1, function () {
	 			// Setup data
	 			var dataToPost = '';
	 			dataToPost += 'course_prerequisite' + '=' + jQuery( '#course-prerequisite-options' ).val();
	 			dataToPost += '&course_woocommerce_product' + '=' + jQuery( '#course-woocommerce-product-options' ).val();
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
		jQuery( '#question_' + questionId ).parent('td').parent('tr').removeClass('hidden');
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
		jQuery( '#question_' + tableRowId ).parent('td').parent('tr').addClass( 'hidden' );
	});
	
	/**
	 * Add Question Save Click Event - Ajax.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	jQuery( '#add-new-question' ).on( 'click', 'a.add_question_save', function() {
		var dataToPost = '';
	 	// Validate Inputs
		var validInput = jQuery.fn.validateQuestionInput( 'add', jQuery(this) );
		if ( validInput ) {
			// Setup data to post	
	 		dataToPost += 'quiz_id' + '=' + jQuery( '#quiz_id' ).attr( 'value' );
	 		dataToPost += '&action=add';
	 		jQuery( '#add-new-question' ).children( 'input' ).each( function() {
	 		    dataToPost += '&' + jQuery( this ).attr( 'name' ) + '=' + jQuery( this ).attr( 'value' );
	 		});
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
	 		    		var addQuestionText = jQuery( '#add_question' ).attr( 'value' );
	 		    		var addQuestionRightText = jQuery( '#add_question_right_answer' ).attr( 'value' );
	 		    		var arrayCounter = 0;
	 		    		var addQuestionWrongText = new Array();
	 		    		jQuery( '#add-new-question input[name="question_wrong_answers[]"]' ).each( function() {
	 		    			addQuestionWrongText[arrayCounter] = jQuery(this).attr( 'value' );
	 		    			arrayCounter++;
	 		    		});
	 		    		// TODO - Localize the english labels for translation
	 		    		jQuery( '#add-question-metadata table tbody' ).append('<tr><td class="table-count hidden">' + tableCount + '</td><td>' + addQuestionText + '</td><td><a title="Edit Question" href="#question_' + tableCount + '" class="question_table_edit">Edit</a>&nbsp;&nbsp;&nbsp;<a title="Delete Question" href="#add-question-metadata" class="question_table_delete">Delete</a></td></tr><tr class="question-quick-edit hidden"><td colspan="3"><label>Question ' + tableCount + '</label> <input type="text" id="question_' + tableCount + '" name="question" value="' + addQuestionText + '" size="25" class="widefat"><label>Right Answer</label> <input type="text" id="question_' + tableCount + '_right_answer" name="question_right_answer" value="' + addQuestionRightText + '" size="25" class="widefat"><label>Wrong Answers</label> <input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[0] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[1] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[2] + '" size="25" class="widefat"><input type="text" name="question_wrong_answers[]" value="' + addQuestionWrongText[3] + '" size="25" class="widefat"><input type="hidden" name="question_id" id="question_' + tableCount + '_id" value="' + questionId + '"><a title="Save Question" href="#add-question-metadata" class="question_table_save button-primary">Save</a></td></tr>');		
			    		jQuery.fn.resetAddQuestionForm();
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
	 						jQuery( '#question_' + tableRowId ).parent('td').parent('tr').addClass( 'hidden' );
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
	 						// TODO - renumber function for reuse when adding
	 					}
	 				}	
	 			);
	 			return false; // TODO - move this below the next bracket when doing the ajax loader
	 	//});
		}
	});	
});