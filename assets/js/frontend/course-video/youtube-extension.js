( () => {
	const tag = document.createElement( 'script' );
	tag.id = 'iframe-api';
	tag.src = 'https://www.youtube.com/iframe_api';
	const firstScriptTag = document.getElementsByTagName( 'script' )[ 0 ];
	firstScriptTag.parentNode.insertBefore( tag, firstScriptTag );

	window.onYouTubeIframeAPIReady = function () {
		document
			.querySelectorAll(
				'.sensei-course-video-youtube-container > iframe'
			)
			.forEach( ( element ) => {
				const player = new YT.Player( element, {
					events: {
						onStateChange: ( event ) => {
							const playerStatus = event.data;

							if (
								window.videoBasedCourseDisableCompleteButton !==
									undefined &&
								window.videoBasedCourseDisableCompleteButton &&
								playerStatus === YT.PlayerState.ENDED
							) {
								window.videoBasedCourseDisableCompleteButton = false;
							}

							if (
								window.videoBasedCourseAutoComplete !==
									undefined &&
								window.videoBasedCourseAutoComplete &&
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
						},
					},
				} );
				if ( window.videoBasedCourseAutoPause ) {
					// eslint-disable-next-line @wordpress/no-global-event-listener
					window.addEventListener( 'blur', () => {
						player.pauseVideo();
					} );
				}
			} );
	};
} )();
