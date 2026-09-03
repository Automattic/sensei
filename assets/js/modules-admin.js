/**
 * Get the url qiuery paramater by name
 *
 * Credit: http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
 *
 * @param {string} name
 * @return {string}
 */

function getParameterByName( name ) {
	name = name.replace( /[\[]/, '\\[' ).replace( /[\]]/, '\\]' );
	const regex = new RegExp( '[\\?&]' + name + '=([^&#]*)' ),
		results = regex.exec( location.search );
	return results === null
		? ''
		: decodeURIComponent( results[ 1 ].replace( /\+/g, ' ' ) );
}

jQuery( document ).ready( function () {
	const _ = window._;

	/**
	 * Add select to the modules select boxes
	 */
	// module order screen
	jQuery( '#module-order-course' ).select2( { width: 'resolve' } );

	/**
	 * Sortable functionality
	 */
	jQuery( '.sortable-module-list' ).sortable();
	jQuery( '.sortable-tab-list' ).disableSelection();

	jQuery( '.sortable-module-list' ).on( 'sortstop', function () {
		let orderString = '';

		jQuery( this )
			.find( '.module' )
			.each( function ( i ) {
				if ( i > 0 ) {
					orderString += ',';
				}
				orderString += jQuery( this ).find( 'span' ).attr( 'rel' );
			} );

		jQuery( 'input[name="module-order"]' ).val( orderString );
	} );

	/**
	 * Searching for courses on the modules admin edit screen
	 */
	jQuery( 'select.ajax_chosen_select_courses' ).select2( {
		minimumInputLength: 2,
		placeholder: window.modulesAdmin.selectplaceholder,
		width: '300px',
		multiple: true,
		ajax: {
			// in wp-admin ajaxurl is supplied by WordPress and is available globaly
			url: ajaxurl,
			delay: 250,
			dataType: 'json',
			cache: true,
			data( params ) {
				// page is the one-based page number tracked by Select2
				return {
					term: params.term, //search term
					page: params.page || 1,
					action: 'sensei_json_search_courses',
					security: window.modulesAdmin.search_courses_nonce,
					default: '',
				};
			},
			processResults( courses, page ) {
				const validCourses = [];
				jQuery.each( courses, function ( i, val ) {
					if ( ! jQuery.isEmptyObject( val ) ) {
						const validcourse = { id: i, text: val };
						validCourses.push( validcourse );
					}
				} );
				// wrap the users inside results for select 2 usage
				return {
					results: validCourses,
					page,
				};
			},
		},
	} ); // end select2

	jQuery( '#sensei-module-add-toggle' ).on( 'click', function () {
		const hidden = 'wp-hidden-child';
		const addBlock = jQuery( this ).parent().next( 'p#sensei-module-add' );
		const moduleInput = addBlock.children( '#newmodule' );
		if ( addBlock.hasClass( hidden ) ) {
			addBlock.removeClass( hidden );
			moduleInput.val( '' );
			moduleInput.focus();
			return;
		}
		addBlock.addClass( hidden );
	} );

	jQuery( '#sensei-module-add-submit' ).on( 'click', function () {
		// setup the fields
		const moduleInput = jQuery( this ).parent().children( '#newmodule' );
		const nonceField = jQuery( this )
			.parent()
			.children( '#add_module_nonce' );

		// get the new term value
		const newTerm = moduleInput.val();
		const security = nonceField.val();

		if ( _.isEmpty( newTerm ) || _.isEmpty( security ) ) {
			moduleInput.focus();
			return;
		}

		const courseId = getParameterByName( 'post' );
		const termListContainer = jQuery(
			'#module_course_mb #taxonomy-module #module-all ul#modulechecklist'
		);

		const newTermData = {
			newTerm,
			security,
			action: 'sensei_add_new_module_term',
			course_id: courseId,
			from_page: 'course',
		};

		jQuery.post( ajaxurl, newTermData, function ( response ) {
			let termId, termName;
			if ( response.success ) {
				termId = response.data.termId;
				termName = response.data.termName;

				// make sure the return values are valid
				if ( ! ( parseInt( termId ) > 0 ) || _.isEmpty( termName ) ) {
					moduleInput.focus();
					return;
				}

				// setup the new list item
				let li = '<li id="module-' + termId + '">';
				li += '<label class="selectit">';
				li +=
					'<input value="' +
					termId +
					'" type="checkbox" checked="checked" name="tax_input[module][]" id="in-module-' +
					termId +
					'">';
				li += termName;
				li += '</label></li>';

				// ad the list item
				termListContainer.prepend( li );

				// clear the input
				moduleInput.val( '' );
				moduleInput.focus();
			} else if (
				typeof response.data.errors != 'undefined' &&
				typeof response.data.errors.term_exists != 'undefined'
			) {
				termId = response.data.term.id;

				// find term with id and just make sure it is
				const termCheckBox = termListContainer.find(
					'#module-' + termId + ' input'
				);

				// checked also move the focus of the user there
				termCheckBox.prop( 'checked', 'checked' );

				// then empty the field that was added
				termCheckBox.focus();
				moduleInput.val( '' );
			}
		} );
	} );

	/**
	 * After changing the course teacher, it prevents updating the modules
	 * until the next page refresh. Otherwise, some issues can happen because
	 * the modules list in the frontend can be out of date with the server.
	 */
	const courseTeacherInput = document.querySelector(
		'select[name="sensei-course-teacher-author"]'
	);
	if ( courseTeacherInput ) {
		courseTeacherInput.addEventListener( 'change', () => {
			const modulesMetabox =
				document.querySelector( '#module_course_mb' );

			if ( modulesMetabox ) {
				modulesMetabox.parentNode.removeChild( modulesMetabox );
			}
		} );
	}

	// Refresh the modules meta box on course select change.
	jQuery( '#lesson-course-options' ).on( 'change', function () {
		// Try to get the lesson ID from the wp data store. If not present, fallback to getting it from the DOM.
		const lessonId =
			wp.data.select( 'core/editor' )?.getCurrentPostId() ||
			jQuery( '#post_ID' ).val();
		const courseId = jQuery( this ).val();

		jQuery.get(
			ajaxurl,
			{
				action: 'sensei_get_lesson_module_metabox',
				lesson_id: lessonId,
				course_id: courseId,
				security: window.modulesAdmin.getLessonModuleMetaBoxNonce,
			},
			function ( response ) {
				if ( '' !== response ) {
					// Replace the meta box and re-initialize select2.
					jQuery( '> .inside', '#module_select' ).html( response );
					jQuery( '#lesson-module-options' ).select2( {
						width: 'resolve',
					} );
				}
			}
		);
	} );
} );
