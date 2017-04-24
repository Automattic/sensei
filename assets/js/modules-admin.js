/**
 * Get the url qiuery paramater by name
 *
 * Credit: http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
 *
 * @param name
 * @returns {string}
 */

function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

jQuery( document ).ready( function ( e ) {

    /**
    * Add select to the modules select boxes
    */
    // module order screen
    jQuery( '#module-order-course' ).select2({width:"resolve"});
    // lesson edit screen modules selection
    jQuery( 'select#lesson-module-options' ).select2({width:"resolve"});

    /**
     * Sortable functionality
     */
	jQuery( '.sortable-module-list' ).sortable();
	jQuery( '.sortable-tab-list' ).disableSelection();

	jQuery( '.sortable-module-list' ).bind( 'sortstop', function ( e, ui ) {
		var orderString = '';

		jQuery( this ).find( '.module' ).each( function ( i, e ) {
			if ( i > 0 ) { orderString += ','; }
			orderString += jQuery( this ).find( 'span' ).attr( 'rel' );

			jQuery( this ).removeClass( 'alternate' );
            jQuery( this ).removeClass( 'first' );
            jQuery( this ).removeClass( 'last' );
            if( i == 0 ) {
                jQuery( this ).addClass( 'first alternate' );
            } else {
                var r = ( i % 2 );
                if( 0 == r ) {
                    jQuery( this ).addClass( 'alternate' );
                }
            }

		});

		jQuery( 'input[name="module-order"]' ).attr( 'value', orderString );
	});


    /**
     * Searching for courses on the modules admin edit screen
     */
    jQuery('select.ajax_chosen_select_courses').select2({
        minimumInputLength: 2,
        placeholder: modulesAdmin.selectplaceholder,
        width:'300px',
        multiple: true,
        ajax: {
            // in wp-admin ajaxurl is supplied by WordPress and is available globaly
            url: ajaxurl,
            dataType: 'json',
            cache: true,
            data: function (params) { // page is the one-based page number tracked by Select2
                return {
                    term: params.term, //search term
                    page: params.page || 1,
                    action: 'sensei_json_search_courses',
                    security: 	modulesAdmin.search_courses_nonce,
                    default: ''
                };
            },
            processResults: function (courses, page) {

                var validCourses = [];
                jQuery.each( courses, function (i, val) {
                    if( ! jQuery.isEmptyObject( val )  ){
                        validcourse = { id: i , text: val  };
                        validCourses.push( validcourse );
                    }
                });
                // wrap the users inside results for select 2 usage
                return {
                    results: validCourses,
                    page: page
                };
            }
        }
    }); // end select2



    jQuery( '#sensei-module-add-toggle').on( 'click', function( e ){

        var hidden = 'wp-hidden-child';
        var addBlock = jQuery(this).parent().next( 'p#sensei-module-add');
        var moduleInput = addBlock.children('#newmodule');
        if( addBlock.hasClass( hidden ) ){

            addBlock.removeClass(hidden);
            moduleInput.val('');
            moduleInput.focus();
            return;
        }else{

            addBlock.addClass(hidden);

        }
    });

    jQuery( '#sensei-module-add-submit').on( 'click', function( e ){

        // setup the fields
        var courseId = getParameterByName('post');
        var moduleInput = jQuery(this).parent().children( '#newmodule' );
        var nonceField = jQuery(this).parent().children( '#add_module_nonce' );
        var termListContainer = jQuery( '#module_course_mb #taxonomy-module #module-all ul#modulechecklist' );

        // get the new term value
        var newTerm = moduleInput.val();
        var security = nonceField.val();

        if( _.isEmpty( newTerm ) || _.isEmpty( security ) ){

            moduleInput.focus();
            return;
        }

        var newTermData = {
            newTerm : newTerm,
            security: security,
            action: 'sensei_add_new_module_term',
            course_id: courseId
        };

        jQuery.post( ajaxurl, newTermData, function(response) {

            if( response.success ){

                var termId = response.data.termId;
                var termName = response.data.termName;

                // make sure the return values are valid
                if( ! ( parseInt( termId ) > 0 ) || _.isEmpty( termName ) ){
                    moduleInput.focus();
                    return;
                }

                // setup the new list item
                var li = '<li id="module-' + termId + '">';
                li += '<label class="selectit">';
                li += '<input value="' + termId +  '" type="checkbox" checked="checked" name="tax_input[module][]" id="in-module-' + termId + '">';
                li += termName;
                li += '</label></li>';

                // ad the list item
                termListContainer.prepend( li );

                // clear the input
                moduleInput.val('');
                moduleInput.focus();

                return;

            }else if( typeof response.data.errors != 'undefined'
                    &&  typeof response.data.errors.term_exists != 'undefined' ){

                var termId = response.data.term.id;

                // find term with id and just make sure it is
                var termCheckBox = termListContainer.find( '#module-' + termId  + ' input');

                // checked also move the focus of the user there
                termCheckBox.prop( 'checked', 'checked' );

                // then empty the field that was added
                termCheckBox.focus();
                moduleInput.val('');

            }else{

                console.log( response );

            }
        });
    });
});