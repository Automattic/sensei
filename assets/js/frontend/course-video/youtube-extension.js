( () => {
	const {
		courseVideoRequired,
		courseVideoAutoComplete,
		courseVideoAutoPause,
	} = window.sensei.courseVideoSettings;

	const handleVisibilityChange = ( player ) => () => {
		if ( document.hidden ) {
			player.pauseVideo();
		}
	};

	const preventClick = ( event ) => {
		event.preventDefault();
		return false;
	};

	const onYouTubePlayerStateChange = ( event ) => {
		const playerStatus = event.data;

		if ( courseVideoRequired && playerStatus === YT.PlayerState.ENDED ) {
			document
				.querySelectorAll( '[data-id="complete-lesson-button"]' )
				.forEach( ( button ) => {
					button.removeEventListener( 'click', preventClick );
					button.disabled = false;
				} );
		}

		if (
			courseVideoAutoComplete &&
			playerStatus === YT.PlayerState.ENDED
		) {
			// submit complete lesson form
			const form = document.querySelector(
				'[data-id="complete-lesson-form"]'
			);
			if ( form ) {
				form.submit();
			}
		}
	};

	const initPlayer = ( iframe ) => {
		const player = new YT.Player( iframe, {
			events: {
				onStateChange: onYouTubePlayerStateChange,
			},
		} );

		if ( courseVideoAutoPause && document.hidden !== undefined ) {
			// eslint-disable-next-line @wordpress/no-global-event-listener
			document.addEventListener(
				'visibilitychange',
				handleVisibilityChange( player ),
				false
			);
		}
	};

	window.onYouTubeIframeAPIReady = function () {
		document
			.querySelectorAll(
				'.sensei-course-video-youtube-container > iframe'
			)
			.forEach( initPlayer );
	};

	if ( courseVideoRequired ) {
		document
			.querySelectorAll( '[data-id="complete-lesson-button"]' )
			.forEach( ( button ) => {
				button.disabled = true;
				button.addEventListener( 'click', preventClick );
			} );
	}
} )();
