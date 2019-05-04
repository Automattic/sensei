jQuery(document).ready(function($) {

	/***** Settings Tabs *****/

	// Make sure each heading has a unique ID.
	jQuery( 'ul#settings-sections.subsubsub' ).find( 'a' ).each( function () {
		var id_value = jQuery( this ).attr( 'href' ).replace( '#', '' );
		jQuery( 'h3:contains("' + jQuery( this ).text() + '")' ).attr( 'id', id_value ).addClass( 'section-heading' );
	});

	// Only show the General settings.
	var defaultSettingsSlug = 'default-settings';
	$( '#woothemes-sensei section' ).each( function () {
		if ( this.id !== defaultSettingsSlug ) {
			$( this ).hide();
		}
	} );
	sensei_log_event( 'settings_view', { view: defaultSettingsSlug } );

	jQuery( '#woothemes-sensei .subsubsub a.tab' ).click( function () {
		// Move the "current" CSS class.
		jQuery( this ).parents( '.subsubsub' ).find( '.current' ).removeClass( 'current' );
		jQuery( this ).addClass( 'current' );

		// Hide all sections.
		jQuery( '#woothemes-sensei section' ).hide();

		// If the link is a tab, show only the specified tab.
		var toShow = jQuery( this ).attr( 'href' );
		// Remove the first occurance of # from the selected string (will be added manually below).
		toShow = toShow.replace( '#', '' );
		jQuery('#'+toShow).show();

		sensei_log_event( 'settings_view', { view: toShow } );

		return false;
	});

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

		container.find( '.' + type ).each( function( i ) {
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
	};

	/***** Course reordering *****/

	jQuery( '.sortable-course-list' ).sortable();
	jQuery( '.sortable-tab-list' ).disableSelection();

	jQuery( '.sortable-course-list' ).bind( 'sortstop', function () {
		var orderString = '';

		jQuery( this ).find( '.course' ).each( function ( i ) {
			if ( i > 0 ) { orderString += ','; }
			orderString += jQuery( this ).find( 'span' ).attr( 'rel' );
		});

		jQuery( 'input[name="course-order"]' ).attr( 'value', orderString );

		jQuery.fn.fixOrderingList( jQuery( this ), 'course' );
	});

	/***** Lesson reordering *****/

	jQuery( '.sortable-lesson-list' ).sortable();
	jQuery( '.sortable-tab-list' ).disableSelection();

	jQuery( '.sortable-lesson-list' ).bind( 'sortstop', function () {
		var orderString = '';
		var module_id = jQuery( this ).attr( 'data-module-id' );
		var order_input = 'lesson-order';
		if( 0 != module_id ) {
			order_input = 'lesson-order-module-' + module_id;
		}


		jQuery( this ).find( '.lesson' ).each( function ( i ) {
			if ( i > 0 ) { orderString += ','; }
			orderString += jQuery( this ).find( 'span' ).attr( 'rel' );
		});

		jQuery( 'input[name="' + order_input + '"]' ).attr( 'value', orderString );

		jQuery.fn.fixOrderingList( jQuery( this ), 'lesson' );
	});

});
