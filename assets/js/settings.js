jQuery(document).ready(function($) {

    /***** Colour pickers *****/

    jQuery('.colorpicker').hide();
    jQuery('.colorpicker').each( function() {
        jQuery(this).farbtastic( jQuery(this).prev('.color') );
    });

    jQuery('.color').click(function() {
        jQuery(this).next('.colorpicker').fadeIn();
    });

    jQuery(document).mousedown(function() {
        jQuery('.colorpicker').each(function() {
            var display = jQuery(this).css('display');
            if ( display == 'block' ) {
                jQuery(this).fadeOut();
            }
        });
    });

    jQuery.fn.fixOrderingList = function( container, type ) {

        container.find( '.' + type ).each( function( i, e ) {
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
    }

    /***** Course reordering *****/

    jQuery( '.sortable-course-list' ).sortable();
    jQuery( '.sortable-tab-list' ).disableSelection();

    jQuery( '.sortable-course-list' ).bind( 'sortstop', function ( e, ui ) {
        var orderString = '';

        jQuery( this ).find( '.course' ).each( function ( i, e ) {
            if ( i > 0 ) { orderString += ','; }
            orderString += jQuery( this ).find( 'span' ).attr( 'rel' );
        });

        jQuery( 'input[name="course-order"]' ).attr( 'value', orderString );

        jQuery.fn.fixOrderingList( jQuery( this ), 'course' );
    });

    /***** Lesson reordering *****/

    jQuery( '.sortable-lesson-list' ).sortable();
    jQuery( '.sortable-tab-list' ).disableSelection();

    jQuery( '.sortable-lesson-list' ).bind( 'sortstop', function ( e, ui ) {
        var orderString = '';

        var module_id = jQuery( this ).attr( 'data-module_id' );
        var order_input = 'lesson-order';
        if( 0 != module_id ) {
            order_input = 'lesson-order-module-' + module_id;
        }


        jQuery( this ).find( '.lesson' ).each( function ( i, e ) {
            if ( i > 0 ) { orderString += ','; }
            orderString += jQuery( this ).find( 'span' ).attr( 'rel' );
        });

        jQuery( 'input[name="' + order_input + '"]' ).attr( 'value', orderString );

        jQuery.fn.fixOrderingList( jQuery( this ), 'lesson' );
    });

});