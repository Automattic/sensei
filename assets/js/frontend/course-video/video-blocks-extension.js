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
			completeButton.click();
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

	if ( courseVideoRequired ) {
		disableCompleteLessonButton();
	}
} )();
