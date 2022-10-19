/**
 * Adapter name.
 */
export const ADAPTER_NAME = 'videopress';

/**
 * The embed pattern to check if it's the respective type.
 */
export const EMBED_PATTERN = /(videopress|video\.wordpress)\.com\/.+/i;

/**
 * Initialize the player.
 *
 * @param {HTMLIFrameElement} element The player element.
 * @param {Window}            w       A custom window.
 *
 * @return {Promise<HTMLIFrameElement>} The video player through a promise.
 */
export const initializePlayer = ( element, w = window ) =>
	new Promise( ( resolve ) => {
		// It was already initialized earlier.
		const { duration } = element.dataset;
		if ( duration ) {
			resolve( element );
			return;
		}

		const onVideoPressMessage = ( event ) => {
			if ( event.source !== element.contentWindow ) {
				return;
			}

			const { data } = event;

			if (
				data.event === 'videopress_durationchange' &&
				data.durationMs
			) {
				// Set the duration to a dataset in order to be available later,
				// and consider the initialization done.
				element.dataset.duration = data.durationMs / 1000;

				// If current time didn't return yet, set it to `0`.
				if ( ! element.dataset.currentTime ) {
					element.dataset.currentTime = 0;
				}

				resolve( element );
			} else if (
				data.event === 'videopress_timeupdate' &&
				data.currentTimeMs
			) {
				// Set the current time to a dataset in order to be available later.
				element.dataset.currentTime = data.currentTimeMs / 1000;
			} else if ( data.event === 'videopress_play' ) {
				// Identify that video was already played.
				element.dataset.hasPlayed = 'has-played';
			}
		};

		// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
		w.addEventListener( 'message', onVideoPressMessage );
	} );

/**
 * Get the video duration.
 *
 * @param {HTMLIFrameElement} player The player element.
 *
 * @return {Promise<number>} The duration of the video in seconds through a promise.
 */
export const getDuration = ( player ) =>
	new Promise( ( resolve, reject ) => {
		const { duration } = player.dataset;

		if ( ! duration ) {
			reject( new Error( 'Video duration not found' ) );
		}

		resolve( parseFloat( duration ) );
	} );

/**
 * Get the current video time.
 *
 * @param {HTMLIFrameElement} player The player element.
 *
 * @return {Promise<number>} The current video time in seconds through a promise.
 */
export const getCurrentTime = ( player ) =>
	new Promise( ( resolve, reject ) => {
		const { currentTime } = player.dataset;

		if ( ! currentTime ) {
			reject( new Error( 'Video current time not found' ) );
			return;
		}

		resolve( parseFloat( currentTime ) );
	} );

/**
 * Set the video to a current time.
 *
 * @param {HTMLIFrameElement} player  The player element.
 * @param {number}            seconds The video time in seconds to set.
 *
 * @return {Promise} A promise that resolves if the video was set to a current time successfully.
 */
export const setCurrentTime = ( player, seconds ) =>
	new Promise( ( resolve ) => {
		const run = () => {
			player.contentWindow.postMessage(
				{
					event: 'videopress_action_set_currenttime',
					currentTime: seconds,
				},
				'*'
			);
			resolve();
		};

		if ( player.dataset.hasPlayed ) {
			run();
		} else {
			play( player )
				.then( () => pause( player ) )
				.then( run );
		}
	} );

/**
 * Play the video.
 *
 * @param {HTMLIFrameElement} player The player element.
 *
 * @return {Promise} A promise that resolves if the video play was sent successfully.
 */
export const play = ( player ) =>
	new Promise( ( resolve ) => {
		player.contentWindow.postMessage(
			{ event: 'videopress_action_play' },
			'*'
		);
		resolve();
	} );

/**
 * Pause the video.
 *
 * @param {HTMLIFrameElement} player The player element.
 *
 * @return {Promise} A promise that resolves if the video pause was sent successfully.
 */
export const pause = ( player ) =>
	new Promise( ( resolve ) => {
		player.contentWindow.postMessage(
			{ event: 'videopress_action_pause' },
			'*'
		);
		resolve();
	} );

/**
 * Add an timeupdate event listener to the player.
 *
 * @param {HTMLIFrameElement} player   The player element.
 * @param {Function}          callback Listener callback.
 * @param {Window}            w        A custom window.
 *
 * @return {Function} The function to unsubscribe the event.
 */
export const onTimeupdate = ( player, callback, w = window ) => {
	const transformedCallback = ( event ) => {
		if (
			event.source !== player.contentWindow ||
			event.data.event !== `videopress_timeupdate` ||
			! event.data.currentTimeMs
		) {
			return;
		}

		callback( event.data.currentTimeMs / 1000 );
	};

	// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
	w.addEventListener( 'message', transformedCallback );

	return () => {
		// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
		w.removeEventListener( 'message', transformedCallback );
	};
};

/**
 * Add an timeupdate event listener to the player.
 *
 * @param {HTMLIFrameElement} player   The player element.
 * @param {Function}          callback Listener callback.
 * @param {Window}            w        A custom window.
 *
 * @return {Function} The function to unsubscribe the event.
 */
export const onEnded = ( player, callback, w = window ) => {
	const transformedCallback = ( event ) => {
		if (
			event.source !== player.contentWindow ||
			event.data.event !== `videopress_ended`
		) {
			return;
		}

		callback();
	};

	// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
	w.addEventListener( 'message', transformedCallback );

	return () => {
		// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
		w.removeEventListener( 'message', transformedCallback );
	};
};
