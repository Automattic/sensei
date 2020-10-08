jQuery( document ).ready( function ( $ ) {
	$( '#lesson-order-course' ).select2( { width: 'resolve' } );
	$( '.sortable-course-list, .sortable-lesson-list' ).sortable();
	$( '.sortable-tab-list' ).disableSelection();

	/* Order Courses */
	$( '.sortable-course-list' ).bind( 'sortstop', function () {
		var orderString = '';

		$( this )
			.find( '.course' )
			.each( function ( i ) {
				if ( i > 0 ) {
					orderString += ',';
				}

				orderString += $( this ).find( 'span' ).attr( 'rel' );
			} );

		$( 'input[name="course-order"]' ).val( orderString );
	} );

	/* Order Lessons */
	$( '.sortable-lesson-list' ).bind( 'sortstop', function () {
		var orderString = '';
		var module_id = $( this ).attr( 'data-module-id' );
		var order_input = 'lesson-order';

		if ( 0 != module_id ) {
			order_input = 'lesson-order-module-' + module_id;
		}

		$( this )
			.find( '.lesson' )
			.each( function ( i ) {
				if ( i > 0 ) {
					orderString += ',';
				}

				orderString += $( this ).find( 'span' ).attr( 'rel' );
			} );

		$( 'input[name="' + order_input + '"]' ).val( orderString );
	} );
} );
