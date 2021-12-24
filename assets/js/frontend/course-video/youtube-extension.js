( () => {
	function handleVisibilityChange( player ) {
		return function () {
			if ( document.hidden ) {
				player.pauseVideo();
			}
		};
	}

	function preventClick( event ) {
		event.preventDefault();
		return false;
	}

	function onYouTubePlayerStateChange( event ) {
		const playerStatus = event.data;

		if (
			window.sensei.courseVideoSettings.courseVideoRequired &&
			playerStatus === YT.PlayerState.ENDED
		) {
			document
				.querySelectorAll( '[data-id="complete-lesson-button"]' )
				.forEach( ( button ) => {
					button.removeEventListener( 'click', preventClick );
					button.disabled = false;
				} );
		}

		if (
			window.sensei.courseVideoSettings.courseVideoAutoComplete &&
			playerStatus === YT.PlayerState.ENDED
		) {
			// submit complete lesson form
			document
				.querySelectorAll( '[data-id="complete-lesson-form"]' )
				.forEach( ( form ) => {
					form.submit();
				} );
		}
	}

	function initPlayer( iframe ) {
		const player = new YT.Player( iframe, {
			events: {
				onStateChange: onYouTubePlayerStateChange,
			},
		} );

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

	window.onYouTubeIframeAPIReady = function () {
		document
			.querySelectorAll(
				'.sensei-course-video-youtube-container > iframe'
			)
			.forEach( initPlayer );
	};

	if ( window.sensei.courseVideoSettings.courseVideoRequired ) {
		document
			.querySelectorAll( '[data-id="complete-lesson-button"]' )
			.forEach( ( button ) => {
				button.disabled = true;
				button.addEventListener( 'click', preventClick );
			} );
	}
} )();
