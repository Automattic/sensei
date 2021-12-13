( () => {
	document.querySelectorAll( '.lesson_button_form' ).forEach( ( element ) => {
		element.addEventListener( 'click', ( e ) => {
			const action = element.querySelector( 'input[name=quiz_action]' )
				.value;
			if ( action !== 'lesson-complete' ) {
				return true;
			}

			if (
				window.videoBasedCourseDisableCompleteButton !== undefined &&
				window.videoBasedCourseDisableCompleteButton
			) {
				e.preventDefault();
				return false;
			}

			return true;
		} );
	} );
} )();
