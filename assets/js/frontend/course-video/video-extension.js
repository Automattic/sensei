( () => {
	function handleVisibilityChange( video ) {
		return function () {
			if ( document.hidden ) {
				video.pause();
			}
		};
	}

	function preventClick( event ) {
		event.preventDefault();
		return false;
	}

	function onEnded() {
		if ( window.sensei.courseVideoSettings.courseVideoRequired ) {
			document
				.querySelectorAll( '[data-id="complete-lesson-button"]' )
				.forEach( ( button ) => {
					button.removeEventListener( 'click', preventClick );
					button.disabled = false;
				} );
		}

		if ( window.sensei.courseVideoSettings.courseVideoAutoComplete ) {
			// submit complete lesson form
			document
				.querySelectorAll( '[data-id="complete-lesson-form"]' )
				.forEach( ( form ) => {
					form.submit();
				} );
		}
	}

	function initPlayer( video ) {
		video.addEventListener( 'ended', onEnded );

		if (
			window.sensei.courseVideoSettings.courseVideoAutoPause &&
			document.hidden !== undefined
		) {
			// eslint-disable-next-line @wordpress/no-global-event-listener
			document.addEventListener(
				'visibilitychange',
				handleVisibilityChange( video ),
				false
			);
		}
	}

	document
		.querySelectorAll( '.sensei-course-video-video-container video' )
		.forEach( initPlayer );

	if ( window.sensei.courseVideoSettings.courseVideoRequired ) {
		document
			.querySelectorAll( '[data-id="complete-lesson-button"]' )
			.forEach( ( button ) => {
				button.disabled = true;
				button.addEventListener( 'click', preventClick );
			} );
	}
} )();
