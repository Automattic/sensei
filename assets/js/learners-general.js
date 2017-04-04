jQuery(document).ready( function($) {

    /***************************************************************************************************
     * 	1 - Helper Functions.
     ***************************************************************************************************/

    /**
     * exists checks if selector exists
     * @since  1.6.0
     * @return boolean
     */
    jQuery.fn.exists = function() {
        return this.length>0;
    }

    /***************************************************************************************************
     * 	2 - Learner Management Overview Functions.
     ***************************************************************************************************/

    /**
     * Course Category Change Event.
     *
     * @since 1.6.0
     * @access public
     */
    jQuery( '#course-category-options' ).on( 'change', '', function() {

        var dataToPost = 'course_cat=' + jQuery( this ).val();

        jQuery.post(
            ajaxurl,
            {
                action : 'get_redirect_url_learners',
                data : dataToPost
            },
            function( response ) {
                // Check for a response
                if ( '' != response ) {
                    window.location = response;
                }
            }
        );
    });

    jQuery( '.remove-learner, .reset-learner' ).click( function( event ) {
        var dataToPost = '';

        var user_id = jQuery( this ).attr( 'data-user_id' );
        var post_id = jQuery( this ).attr( 'data-post_id' );
        var post_type = jQuery( this ).attr( 'data-post_type' );

        var confirm_message = woo_learners_general_data.remove_generic_confirm;

        var actions = {
            reset: {
                lesson : woo_learners_general_data.reset_lesson_confirm,
                course : woo_learners_general_data.reset_course_confirm,
                action : 'reset_user_post'
            },
            remove: {
                lesson : woo_learners_general_data.remove_from_lesson_confirm,
                course : woo_learners_general_data.remove_from_course_confirm,
                action : 'remove_user_from_post'
            }
        };

        var current_action = jQuery( event.target ).hasClass( 'remove-learner' ) ? 'remove' : 'reset';

        confirm_message = actions[current_action][post_type];

        if ( ! confirm( confirm_message ) ) {
            return;
        }

        var table_row = jQuery( this ).closest( 'tr' );

        if ( 'remove' === current_action ) {
            table_row.fadeTo( 'fast', 0.33 );
        }

        if ( user_id && post_id && post_type ) {
            dataToPost += 'user_id=' + user_id;
            dataToPost += '&post_id=' + post_id;
            dataToPost += '&post_type=' + post_type;

            jQuery.post(
                ajaxurl,
                {
                    action : actions[current_action].action,
                    modify_user_post_nonce : woo_learners_general_data.modify_user_post_nonce,
                    data : dataToPost
                },
                function( response ) {
                    if ( response ) {
                        switch ( current_action ) {
                            case 'remove':
                                table_row.remove();
                            break;

                            case 'reset':
                                table_row.find( '.graded' ).html( slgL10n.inprogress ).removeClass( 'graded' ).addClass( 'in-progress' );
                            break
                        }
                    }
                }
            );
        }
    });

    /**
     * Load chosen on the course
     */

    ajaxData = 	{
        action: 	'sensei_json_search_users',
        security: 	woo_learners_general_data.search_users_nonce,
        default: 	''
    };


    jQuery('select#add_learner_search').select2({
        minimumInputLength: 3,
        placeholder: woo_learners_general_data.selectplaceholder,
        width:'300px',

        ajax: {
            // in wp-admin ajaxurl is supplied by WordPress and is available globaly
            url: ajaxurl,
            dataType: 'json',
            cache: true,
            id: function(user){ return bond._id; },
            data: function (params) { // page is the one-based page number tracked by Select2
                return {
                    term: params.term, //search term
                    page: params.page || 1,
                    action: 'sensei_json_search_users',
                    security: 	woo_learners_general_data.search_users_nonce,
                    default: ''
                };
            },
            processResults: function (users, page) {

                var validUsers = [];
                jQuery.each( users, function (i, val) {
                    if( ! jQuery.isEmptyObject( val )  ){
                        validUser = { id: i , text: val  };
                        validUsers.push( validUser );
                    }
                });
                // wrap the users inside results for select 2 usage
                return {
                    results: validUsers,
                    page: page
                };
            }
        }
    }); // end select2

    /***************************************************************************************************
     * 	3 - Load Select2 Dropdowns.
     ***************************************************************************************************/

    // Learner Management Drop Downs
    if ( jQuery( '#course-category-options' ).exists() ) { jQuery( '#course-category-options' ).select2(); }

});
