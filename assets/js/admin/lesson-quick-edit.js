( function ( $ ) {
	// we create a copy of the WP inline edit post function
	var $wp_inline_edit = window.inlineEditPost.edit;
	// and then we overwrite the function with our own code
	window.inlineEditPost.edit = function ( id ) {
		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		$wp_inline_edit.apply( this, arguments );

		// now we take care of our business

		// get the post ID
		var postId = 0;
		if ( id instanceof Element ) {
			postId = parseInt( this.getId( id ) );
		}

		if ( postId > 0 ) {
			// define the edit row
			var editRow = $( '#edit-' + postId );
			var senseiFieldValues = window[ 'sensei_quick_edit_' + postId ];

			//on the save button click, set senseiFieldValues to the values user entered in the form fields
			editRow.find( '.save' ).on( 'click', function () {
				const $inputs = $( '.sensei-quiz-settings :input', editRow );

				$inputs.each( function () {
					const inputName = $( this ).attr( 'name' );
					const inputValue = $( this ).val();

					senseiFieldValues[ inputName ] = inputValue;
				} );
			} );

			// populate the data
			//data is localized in sensei_quick_edit object

			if (
				'on' == senseiFieldValues.pass_required ||
				'1' == senseiFieldValues.pass_required
			) {
				senseiFieldValues.pass_required = 1;
			} else {
				senseiFieldValues.pass_required = 0;
			}

			if (
				'on' == senseiFieldValues.enable_quiz_reset ||
				'1' == senseiFieldValues.enable_quiz_reset
			) {
				senseiFieldValues.enable_quiz_reset = 1;
			} else {
				senseiFieldValues.enable_quiz_reset = 0;
			}

			if (
				'auto' === senseiFieldValues.quiz_grade_type ||
				'1' === senseiFieldValues.quiz_grade_type
			) {
				senseiFieldValues.quiz_grade_type = 1;
			} else {
				senseiFieldValues.quiz_grade_type = 0;
			}

			if (
				'yes' == senseiFieldValues.random_question_order ||
				'1' == senseiFieldValues.random_question_order
			) {
				senseiFieldValues.random_question_order = 1;
			} else {
				senseiFieldValues.random_question_order = 0;
			}

			for ( const [ key, value ] of Object.entries(
				senseiFieldValues
			) ) {
				var elem = $( ':input[name="' + key + '"]', editRow );
				if ( elem.prop( 'nodeName' ) == 'INPUT' ) {
					elem.val( parseInt( value ) );
				} else {
					$(
						':input[name="' +
							key +
							'"] option[value="' +
							value +
							'"] ',
						editRow
					).attr( 'selected', true );
				}
			}
		}
	};
} )( jQuery );
