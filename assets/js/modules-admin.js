jQuery( document ).ready( function ( e ) {

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

    jQuery('select.ajax_chosen_select_courses').ajaxChosen({
        method: 'GET',
        url: ajaxurl,
        dataType: 'json',
        afterTypeDelay: 100,
        minTermLength: 1,
        data: {
            action: 'sensei_json_search_courses',
            security: modulesAdmin.search_courses_nonce,
            default: ''
        }
    }, function (data) {

        var courses = {};

        jQuery.each(data, function (i, val) {
            courses[i] = val;
        });

        return courses;
    });
});