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

		const onDurationChange = ( event ) => {
			if (
				event.source !== element.contentWindow ||
				event.data.event !== 'videopress_durationchange' ||
				! event.data.durationMs
			) {
				return;
			}

			// Set the duration to a dataset in order to have it available for later.
			element.dataset.duration =
				parseInt( event.data.durationMs, 10 ) / 1000;

			w.removeEventListener( 'message', onDurationChange );
			resolve( element );
		};

		// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
		w.addEventListener( 'message', onDurationChange );
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

		resolve( parseFloat( player.dataset.duration ) );
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
		player.contentWindow.postMessage(
			{
				event: 'videopress_action_set_currenttime',
				currentTime: seconds,
			},
			'*'
		);
		resolve();
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
