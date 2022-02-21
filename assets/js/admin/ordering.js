jQuery( document ).ready( function ( $ ) {
	const $lessonList = $( '.sortable-lesson-list' );
	const $courseList = $( '.sortable-course-list' );

	$lessonList.sortable( {
		connectWith: '.sortable-lesson-list',
	} );

	$courseList.sortable();

	$( '#lesson-order-course' ).select2( { width: 'resolve' } );
	$( '.sortable-tab-list' ).disableSelection();

	/* Order Courses */
	$courseList.on( 'sortstop', function () {
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
	$lessonList.on( 'sortstop', ( event, ui ) => {
		const $listItem = $( ui.item[ 0 ] );
		const $destinationList = $listItem.parent();
		const moduleId = $destinationList.data( 'module-id' );

		$listItem.find( 'input' ).val( moduleId );
	} );
} );
