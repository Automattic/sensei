/**
 * Get the url qiuery paramater by name
 *
 * Credit: http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
 *
 * @param {string} name
 * @returns {string}
 */

function getParameterByName( name ) {
	name = name.replace( /[\[]/, '\\[' ).replace( /[\]]/, '\\]' );
	var regex = new RegExp( '[\\?&]' + name + '=([^&#]*)' ),
		results = regex.exec( location.search );
	return results === null
		? ''
		: decodeURIComponent( results[ 1 ].replace( /\+/g, ' ' ) );
}

jQuery( document ).ready( function () {
	var _ = window._;

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

	jQuery( '.sortable-module-list' ).bind( 'sortstop', function () {
		var orderString = '';

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
			data: function ( params ) {
				// page is the one-based page number tracked by Select2
				return {
					term: params.term, //search term
					page: params.page || 1,
					action: 'sensei_json_search_courses',
					security: window.modulesAdmin.search_courses_nonce,
					default: '',
				};
			},
			processResults: function ( courses, page ) {
				var validCourses = [];
				jQuery.each( courses, function ( i, val ) {
					if ( ! jQuery.isEmptyObject( val ) ) {
						var validcourse = { id: i, text: val };
						validCourses.push( validcourse );
					}
				} );
				// wrap the users inside results for select 2 usage
				return {
					results: validCourses,
					page: page,
				};
			},
		},
	} ); // end select2

	jQuery( '#sensei-module-add-toggle' ).on( 'click', function () {
		var hidden = 'wp-hidden-child';
		var addBlock = jQuery( this ).parent().next( 'p#sensei-module-add' );
		var moduleInput = addBlock.children( '#newmodule' );
		if ( addBlock.hasClass( hidden ) ) {
			addBlock.removeClass( hidden );
			moduleInput.val( '' );
			moduleInput.focus();
			return;
		} else {
			addBlock.addClass( hidden );
		}
	} );

	jQuery( '#sensei-module-add-submit' ).on( 'click', function () {
		// setup the fields
		var courseId = getParameterByName( 'post' );
		var moduleInput = jQuery( this ).parent().children( '#newmodule' );
		var nonceField = jQuery( this )
			.parent()
			.children( '#add_module_nonce' );
		var termListContainer = jQuery(
			'#module_course_mb #taxonomy-module #module-all ul#modulechecklist'
		);

		// get the new term value
		var newTerm = moduleInput.val();
		var security = nonceField.val();

		if ( _.isEmpty( newTerm ) || _.isEmpty( security ) ) {
			moduleInput.focus();
			return;
		}

		var newTermData = {
			newTerm: newTerm,
			security: security,
			action: 'sensei_add_new_module_term',
			course_id: courseId,
			from_page: 'course',
		};

		jQuery.post( ajaxurl, newTermData, function ( response ) {
			var termId, termName;
			if ( response.success ) {
				termId = response.data.termId;
				termName = response.data.termName;

				// make sure the return values are valid
				if ( ! ( parseInt( termId ) > 0 ) || _.isEmpty( termName ) ) {
					moduleInput.focus();
					return;
				}

				// setup the new list item
				var li = '<li id="module-' + termId + '">';
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

				return;
			} else if (
				typeof response.data.errors != 'undefined' &&
				typeof response.data.errors.term_exists != 'undefined'
			) {
				termId = response.data.term.id;

				// find term with id and just make sure it is
				var termCheckBox = termListContainer.find(
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
			const modulesMetabox = document.querySelector(
				'#module_course_mb'
			);

			if ( modulesMetabox ) {
				modulesMetabox.parentNode.removeChild( modulesMetabox );
			}
		} );
	}

	// Get Course modules (if any) on course select change
	var $courseSelect = jQuery( '#lesson-course-options' ),
		$lessonModuleMetaboxSelectContainer = jQuery(
			'div#lesson-module-metabox-select'
		);

	$courseSelect.on( 'change', function () {
		if ( ! window.modulesAdmin.getCourseModulesNonce ) {
			// console.log( 'missing modulesAdmin.getCourseModulesNonce' );
			return;
		}
		var courseId = $courseSelect.val(),
			data = {
				security: window.modulesAdmin.getCourseModulesNonce,
				action: 'sensei_get_course_modules',
				course_id: courseId,
			};
		if ( ! data.course_id ) {
			// console.log( 'missing data.course_id' );
			return;
		}

		jQuery.post( ajaxurl, data, function ( response ) {
			if ( true === response.success ) {
				var content = response.data.content;
				$lessonModuleMetaboxSelectContainer.html( content );
				jQuery( 'select#lesson-module-options' ).select2( {
					width: 'resolve',
				} );
			}
		} );
	} );
} );
