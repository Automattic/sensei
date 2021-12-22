( () => {
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
				if ( window.sensei.courseVideoSettings.courseVideoAutoPause ) {
					// eslint-disable-next-line @wordpress/no-global-event-listener
					window.addEventListener( 'blur', () => {
						player.pauseVideo();
					} );
				}
			} );
	};
} )();
