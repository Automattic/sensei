( () => {
	function handleVisibilityChange( player ) {
		return function () {
			if ( document.hidden ) {
				player.pause();
			}
		};
	}

	function preventClick( event ) {
		event.preventDefault();
		return false;
	}

	function onEnded( data ) {
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
					const action = form.querySelector(
						'input[name=quiz_action]'
					).value;
					if ( action !== 'lesson-complete' ) {
						return true;
					}
					form.submit();
				} );
		}
	}

	function initPlayer( iframe ) {
		const player = new Vimeo.Player( iframe );
		player.on( 'ended', onEnded );

		if (
			window.sensei.courseVideoSettings.courseVideoAutoPause &&
			document.hidden !== undefined
		) {
			// eslint-disable-next-line @wordpress/no-global-event-listener
			document.addEventListener(
				'visibilitychange',
				handleVisibilityChange( player ),
				false
			);
		}
	}

	document
		.querySelectorAll( '.sensei-course-video-vimeo-container > iframe' )
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
