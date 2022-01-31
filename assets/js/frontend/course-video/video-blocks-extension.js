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
					if (
						document.hidden &&
						typeof player.pauseVideo === 'function'
					) {
						player.pauseVideo();
					}
				},
				false
			);
		}
	};

	// onYouTubeIframeAPIReady is called by YouTube iframe API when it is ready.
	const previousYouTubeIframeAPIReady =
		window.onYouTubeIframeAPIReady !== undefined
			? window.onYouTubeIframeAPIReady
			: () => {};
	window.onYouTubeIframeAPIReady = () => {
		document
			.querySelectorAll(
				'.sensei-course-video-container.youtube-extension iframe'
			)
			.forEach( initYouTubePlayer );
		previousYouTubeIframeAPIReady();
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
					if (
						document.hidden &&
						typeof video.pause === 'function'
					) {
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
					if (
						document.hidden &&
						typeof player.pause === 'function'
					) {
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

	const extractVideoPressIdFromUrl = ( url ) => {
		const urlWithoutQuery = url.split( '?' )[ 0 ];
		const parts = urlWithoutQuery.split( '/' );
		return parts[ parts.length - 1 ];
	};

	const initVideoPressPlayer = ( iframe ) => {
		const videoId = extractVideoPressIdFromUrl( iframe.src );

		iframe.addEventListener( 'load', () => {
			// eslint-disable-next-line @wordpress/no-global-event-listener
			window.addEventListener(
				'message',
				( event ) => {
					if ( event.source !== iframe.contentWindow ) {
						return;
					}
					if (
						event.data.event === 'ended' &&
						event.data.id === videoId
					) {
						onEnded();
					}
				},
				false
			);

			if ( courseVideoAutoPause && document.hidden !== undefined ) {
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
			}
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
