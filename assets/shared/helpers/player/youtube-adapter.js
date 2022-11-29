/**
 * Adapter name.
 */
export const ADAPTER_NAME = 'youtube';

/**
 * The embed pattern to check if it's the respective type.
 */
export const EMBED_PATTERN = /(youtu\.be|youtube\.com)\/.+/i;

/**
 * Initialize the player.
 *
 * @param {HTMLIFrameElement} element The player element.
 * @param {Window}            w       A custom window.
 *
 * @return {Object} The YouTube player instance through a promise.
 */
export const initializePlayer = ( element, w = window ) =>
	new Promise( ( resolve ) => {
		w.senseiYouTubeIframeAPIReady.then( () => {
			const player = w.YT.get( element.id ) || new w.YT.Player( element );

			const onReady = () => {
				resolve( player );
			};

			if ( player.getDuration ) {
				// Just in case it's called after the player is ready.
				onReady();
			} else {
				player.addEventListener( 'onReady', onReady );
			}

			// Add a dataset to identify if video has played already.
			const onStateChange = ( e ) => {
				if ( e.data === w.YT.PlayerState.PLAYING ) {
					element.dataset.hasPlayed = 'has-played';
					player.removeEventListener(
						'onStateChange',
						onStateChange
					);
				}
			};
			if ( 'has-played' !== element.dataset.hasPlayed ) {
				player.addEventListener( 'onStateChange', onStateChange );
			}
		} );
	} );

/**
 * Get the video duration.
 *
 * @param {Object} player The YouTube player instance.
 *
 * @return {Promise<number>} The duration of the video in seconds through a promise.
 */
export const getDuration = ( player ) =>
	new Promise( ( resolve ) => {
		resolve( player.getDuration() );
	} );

/**
 * Get the current video time.
 *
 * @param {Object} player The YouTube player instance.
 *
 * @return {Promise<number>} The current video time in seconds through a promise.
 */
export const getCurrentTime = ( player ) =>
	new Promise( ( resolve ) => {
		resolve( player.getCurrentTime() );
	} );

/**
 * Set the video to a current time.
 *
 * @param {Object} player  The YouTube player instance.
 * @param {number} seconds The video time in seconds to set.
 *
 * @return {Promise} A promise that resolves if the video was set to a current time successfully.
 */
export const setCurrentTime = ( player, seconds ) =>
	new Promise( ( resolve ) => {
		if ( player.getIframe().dataset.hasPlayed ) {
			player.seekTo( seconds );
			resolve();
		} else {
			play( player )
				.then( () => pause( player ) )
				.then( () => {
					player.seekTo( seconds );
					resolve();
				} );
		}
	} );

/**
 * Play the video.
 *
 * @param {Object} player The YouTube player instance.
 *
 * @return {Promise} A promise that resolves if the video play was called successfully.
 */
export const play = ( player ) =>
	new Promise( ( resolve ) => {
		player.playVideo();
		resolve();
	} );

/**
 * Pause the video.
 *
 * @param {Object} player The YouTube player instance.
 *
 * @return {Promise} A promise that resolves if the video pause was called successfully.
 */
export const pause = ( player ) =>
	new Promise( ( resolve ) => {
		player.pauseVideo();
		resolve();
	} );

/**
 * Add an timeupdate event listener to the player.
 *
 * @param {Object}   player   The YouTube player instance.
 * @param {Function} callback Listener callback.
 * @param {Window}   w        A custom window.
 *
 * @return {Function} The function to unsubscribe the event.
 */
export const onTimeupdate = ( player, callback, w = window ) => {
	const timer = 250;
	let previousCurrentTime;

	const updateCurrentTime = ( currentTime ) => {
		if ( previousCurrentTime !== currentTime ) {
			callback( currentTime );
			previousCurrentTime = currentTime;
		}
	};

	// Update the current time based on an interval.
	const interval = setInterval( () => {
		if ( player.getPlayerState() !== w.YT.PlayerState.ENDED ) {
			updateCurrentTime( player.getCurrentTime() );
		}
	}, timer );

	// Update the current time when the video is ended.
	const onEnded = () => {
		if ( player.getPlayerState() === w.YT.PlayerState.ENDED ) {
			updateCurrentTime( player.getDuration() );
		}
	};

	player.addEventListener( 'onStateChange', onEnded );

	return () => {
		clearInterval( interval );
		player.removeEventListener( 'onStateChange', onEnded );
	};
};

/**
 * Add an ended event listener to the player.
 *
 * @param {Object}   player   The YouTube player instance.
 * @param {Function} callback Listener callback.
 * @param {Window}   w        A custom window.
 *
 * @return {Function} The function to unsubscribe the event.
 */
export const onEnded = ( player, callback, w = window ) => {
	const transformedCallback = () => {
		if ( player.getPlayerState() === w.YT.PlayerState.ENDED ) {
			callback();
		}
	};

	player.addEventListener( 'onStateChange', transformedCallback );

	return () => {
		player.removeEventListener( 'onStateChange', transformedCallback );
	};
};
