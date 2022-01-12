( () => {
	const {
		courseVideoRequired,
		courseVideoAutoComplete,
		courseVideoAutoPause,
	} = window.sensei.courseVideoSettings;

	const preventClick = ( event ) => {
		event.preventDefault();
		return false;
	};

	const disableCompleteLessonButton = () => {
		document
			.querySelectorAll( '[data-id="complete-lesson-button"]' )
			.forEach( ( button ) => {
				button.disabled = true;
				button.addEventListener( 'click', preventClick );
			} );
	};

	const enableCompleteLessonButton = () => {
		document
			.querySelectorAll( '[data-id="complete-lesson-button"]' )
			.forEach( ( button ) => {
				button.removeEventListener( 'click', preventClick );
				button.disabled = false;
			} );
	};

	const submitCompleteLessonForm = () => {
		const completeButton = document.querySelector(
			'[data-id="complete-lesson-button"]'
		);
		if ( completeButton ) {
			setTimeout( () => {
				completeButton.click();
			}, 3000 );
		}
	};

	const onYouTubePlayerStateChange = ( event ) => {
		const playerStatus = event.data;

		if ( courseVideoRequired && playerStatus === YT.PlayerState.ENDED ) {
			enableCompleteLessonButton();
		}

		if (
			courseVideoAutoComplete &&
			playerStatus === YT.PlayerState.ENDED
		) {
			submitCompleteLessonForm();
		}
	};

	const initYouTubePlayer = ( iframe ) => {
		const player = new YT.Player( iframe, {
			events: {
				onStateChange: onYouTubePlayerStateChange,
			},
		} );

		if ( courseVideoAutoPause && document.hidden !== undefined ) {
			// eslint-disable-next-line @wordpress/no-global-event-listener
			document.addEventListener(
				'visibilitychange',
				() => {
					if ( document.hidden ) {
						player.pauseVideo();
					}
				},
				false
			);
		}
	};

	// onYouTubeIframeAPIReady is called by YouTube iframe API when it is ready.
	window.onYouTubeIframeAPIReady = () => {
		document
			.querySelectorAll(
				'.sensei-course-video-container.youtube-extension iframe'
			)
			.forEach( initYouTubePlayer );
	};

	const onEnded = () => {
		if ( courseVideoRequired ) {
			enableCompleteLessonButton();
		}

		if ( courseVideoAutoComplete ) {
			submitCompleteLessonForm();
		}
	};

	const initVideoPlayer = ( video ) => {
		video.addEventListener( 'ended', onEnded );

		if ( courseVideoAutoPause && document.hidden !== undefined ) {
			// eslint-disable-next-line @wordpress/no-global-event-listener
			document.addEventListener(
				'visibilitychange',
				() => {
					if ( document.hidden ) {
						video.pause();
					}
				},
				false
			);
		}
	};

	document
		.querySelectorAll( '.sensei-course-video-container video' )
		.forEach( initVideoPlayer );

	const initVimeoPlayer = ( iframe ) => {
		const player = new Vimeo.Player( iframe );
		player.on( 'ended', onEnded );

		if ( courseVideoAutoPause && document.hidden !== undefined ) {
			// eslint-disable-next-line @wordpress/no-global-event-listener
			document.addEventListener(
				'visibilitychange',
				() => {
					if ( document.hidden ) {
						player.pause();
					}
				},
				false
			);
		}
	};

	document
		.querySelectorAll(
			'.sensei-course-video-container.vimeo-extension iframe'
		)
		.forEach( initVimeoPlayer );

	const initVideoPressPlayer = ( iframe ) => {
		iframe.addEventListener( 'load', () => {
			// eslint-disable-next-line @wordpress/no-global-event-listener
			window.addEventListener(
				'message',
				( event ) => {
					if ( event.source !== iframe.contentWindow ) {
						return;
					}
					if ( event.data.event === 'ended' ) {
						onEnded();
					}
				},
				false
			);

			// eslint-disable-next-line @wordpress/no-global-event-listener
			document.addEventListener(
				'visibilitychange',
				() => {
					if ( document.hidden ) {
						iframe.contentWindow.postMessage(
							{
								event: 'videopress_action_pause',
							},
							'*'
						);
					}
				},
				false
			);
		} );
	};

	document
		.querySelectorAll(
			'.sensei-course-video-container.videopress-extension iframe'
		)
		.forEach( initVideoPressPlayer );

	if ( courseVideoRequired ) {
		disableCompleteLessonButton();
	}
} )();
