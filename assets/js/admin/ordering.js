jQuery( document ).ready( function( $ ) {
	$( '#lesson-order-course' ).select2( { width: 'resolve' } );
	$( '.sortable-course-list, .sortable-lesson-list' ).sortable();
	$( '.sortable-tab-list' ).disableSelection();

	$.fn.fixOrderingList = function( container, type ) {
		container.find( '.' + type ).each( function( i ) {
			$( this ).removeClass( 'alternate' );
			$( this ).removeClass( 'first' );
			$( this ).removeClass( 'last' );

			if ( 0 === i ) {
				$( this ).addClass( 'first alternate' );
			} else {
				var r = ( i % 2 );

				if (  0 === r ) {
					$( this ).addClass( 'alternate' );
				}
			}
		});
	};

	/* Order Courses */
	$( '.sortable-course-list' ).bind( 'sortstop', function () {
		var orderString = '';

		$( this ).find( '.course' ).each( function ( i ) {
			if ( i > 0 ) {
				orderString += ',';
			}

			orderString += $( this ).find( 'span' ).attr( 'rel' );
		});

		$( 'input[name="course-order"]' ).attr( 'value', orderString );

		$.fn.fixOrderingList( $( this ), 'course' );
	} );

	/* Order Lessons */
	$( '.sortable-lesson-list' ).bind( 'sortstop', function() {
		var orderString = '';
		var module_id = $( this ).attr( 'data-module-id' );
		var order_input = 'lesson-order';

		if ( 0 != module_id ) {
			order_input = 'lesson-order-module-' + module_id;
		}

		$( this ).find( '.lesson' ).each( function( i ) {
			if ( i > 0 ) {
				orderString += ',';
			}

			orderString += $( this ).find( 'span' ).attr( 'rel' );
		});

		$( 'input[name="' + order_input + '"]' ).attr( 'value', orderString );

		$.fn.fixOrderingList( $( this ), 'lesson' );
	} );
} );
