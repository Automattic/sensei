( () => {
	// Use of Page Visibility API. Unfortunately, it isn't standardized.
	// https://developer.mozilla.org/en-US/docs/Web/API/Page_Visibility_API
	let hidden, visibilityChange;
	if ( typeof document.hidden !== 'undefined' ) {
		// Opera 12.10 and Firefox 18 and later support
		hidden = 'hidden';
		visibilityChange = 'visibilitychange';
	} else if ( typeof document.msHidden !== 'undefined' ) {
		hidden = 'msHidden';
		visibilityChange = 'msvisibilitychange';
	} else if ( typeof document.webkitHidden !== 'undefined' ) {
		hidden = 'webkitHidden';
		visibilityChange = 'webkitvisibilitychange';
	}

	function handleVisibilityChange( player ) {
		return function () {
			if ( document[ hidden ] ) {
				player.pauseVideo();
			}
		};
	}

	function onYouTubePlayerStateChange( event ) {
		const playerStatus = event.data;

		if (
			window.sensei.courseVideoSettings.courseVideoRequired &&
			playerStatus === YT.PlayerState.ENDED
		) {
			document.querySelector(
				'.wp-block-sensei-lms-button-complete-lesson > button'
			).disabled = false;
		}

		if (
			window.sensei.courseVideoSettings.courseVideoAutoComplete &&
			playerStatus === YT.PlayerState.ENDED
		) {
			// submit complete lesson form
			document
				.querySelectorAll( '.lesson_button_form' )
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

	window.onYouTubeIframeAPIReady = function () {
		document
			.querySelectorAll(
				'.sensei-course-video-youtube-container > iframe'
			)
			.forEach( ( element ) => {
				const player = new YT.Player( element, {
					events: {
						onStateChange: onYouTubePlayerStateChange,
					},
				} );

				if (
					window.sensei.courseVideoSettings.courseVideoAutoPause &&
					hidden !== undefined
				) {
					// eslint-disable-next-line @wordpress/no-global-event-listener
					document.addEventListener(
						visibilityChange,
						handleVisibilityChange( player ),
						false
					);
				}
			} );
	};
} )();
