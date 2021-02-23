jQuery( document ).ready( function () {
	// Lessons Write Panel.
	const complexityOptionElements = jQuery( '#lesson-complexity-options' );
	if ( complexityOptionElements.length > 0 ) {
		complexityOptionElements.select2( { width: 'resolve' } );
	}

	const prerequisiteOptionElements = jQuery( '#lesson-prerequisite-options' );
	if ( prerequisiteOptionElements.length > 0 ) {
		prerequisiteOptionElements.select2( {
			width: 'resolve',
		} );
	}

	const courseOptionElements = jQuery( '#lesson-course-options' );
	if ( courseOptionElements.length > 0 ) {
		courseOptionElements.select2( { width: 'resolve' } );
	}

	const moduleOptionElements = jQuery( '#lesson-module-options' );
	if ( moduleOptionElements.length > 0 ) {
		moduleOptionElements.select2( { width: 'resolve' } );
	}
} );
