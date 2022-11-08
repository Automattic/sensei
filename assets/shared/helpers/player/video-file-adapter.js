/**
 * Adapter name.
 */
export const ADAPTER_NAME = 'video-file';

/**
 * Initialize the player.
 *
 * @param {HTMLVideoElement} element The player element.
 *
 * @return {Promise<HTMLVideoElement>} The video player through a promise.
 */
export const initializePlayer = ( element ) =>
	new Promise( ( resolve ) => {
		// Return that it's ready when it can get the video duration.
		if ( ! isNaN( element.duration ) ) {
			resolve( element );
		}

		element.addEventListener(
			'durationchange',
			() => {
				resolve( element );
			},
			{ once: true }
		);
	} );

/**
 * Get the video duration.
 *
 * @param {HTMLVideoElement} player The player element.
 *
 * @return {Promise<number>} The duration of the video in seconds through a promise.
 */
export const getDuration = ( player ) =>
	new Promise( ( resolve ) => {
		resolve( player.duration );
	} );

/**
 * Get the current video time.
 *
 * @param {HTMLVideoElement} player The player element.
 *
 * @return {Promise<number>} The current video time in seconds through a promise.
 */
export const getCurrentTime = ( player ) =>
	new Promise( ( resolve ) => {
		resolve( player.currentTime );
	} );

/**
 * Set the video to a current time.
 *
 * @param {HTMLVideoElement} player  The player element.
 * @param {number}           seconds The video time in seconds to set.
 *
 * @return {Promise} A promise that resolves if the video was set to a current time successfully.
 */
export const setCurrentTime = ( player, seconds ) =>
	new Promise( ( resolve ) => {
		player.currentTime = seconds;
		resolve();
	} );

/**
 * Play the video.
 *
 * @param {HTMLVideoElement} player The player element.
 *
 * @return {Promise} The native promise from the video play function.
 */
export const play = ( player ) => player.play();

/**
 * Pause the video.
 *
 * @param {HTMLVideoElement} player The player element.
 *
 * @return {Promise} A promise that resolves if the video was paused successfully.
 */
export const pause = ( player ) =>
	new Promise( ( resolve, reject ) => {
		player.pause();

		if ( player.paused ) {
			resolve();
		}

		reject( new Error( "Video didn't pause" ) );
	} );

/**
 * Add an timeupdate event listener to the player.
 *
 * @param {HTMLVideoElement} player   The player element.
 * @param {Function}         callback Listener callback.
 *
 * @return {Function} The function to unsubscribe the event.
 */
export const onTimeupdate = ( player, callback ) => {
	const transformedCallback = ( event ) => {
		callback( event.target.currentTime );
	};

	player.addEventListener( 'timeupdate', transformedCallback );

	return () => {
		player.removeEventListener( 'timeupdate', transformedCallback );
	};
};

/**
 * Add an ended event listener to the player.
 *
 * @param {HTMLVideoElement} player   The player element.
 * @param {Function}         callback Listener callback.
 *
 * @return {Function} The function to unsubscribe the event.
 */
export const onEnded = ( player, callback ) => {
	player.addEventListener( 'ended', callback );

	return () => {
		player.removeEventListener( 'timeupdate', callback );
	};
};
