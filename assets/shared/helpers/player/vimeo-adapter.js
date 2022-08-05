/**
 * Adapter name.
 */
export const ADAPTER_NAME = 'vimeo';

/**
 * The embed pattern to check if it's the respective type.
 */
export const EMBED_PATTERN = /vimeo\.com\/.+/i;

/**
 * Initialize the player.
 *
 * @param {HTMLIFrameElement} element The player element.
 * @param {Window}            w       A custom window.
 *
 * @return {Object} The Vimeo player instance through a promise.
 */
export const initializePlayer = ( element, w = window ) =>
	Promise.resolve( new w.Vimeo.Player( element ) );

/**
 * Get the video duration.
 *
 * @param {Object} player The Vimeo player instance.
 *
 * @return {Promise<number>} The duration of the video in seconds through a promise
 *                           (original return from Vimeo API).
 */
export const getDuration = ( player ) => player.getDuration();

/**
 * Get the current video time.
 *
 * @param {Object} player The Vimeo player instance.
 *
 * @return {Promise<number>} The current video time in seconds through a promise.
 */
export const getCurrentTime = ( player ) => player.getCurrentTime();

/**
 * Set the video to a current time.
 *
 * @param {Object} player  The Vimeo player instance.
 * @param {number} seconds The video time in seconds to set.
 *
 * @return {Promise} A promise that resolves if the video was set to a current time successfully.
 *                   (original return from Vimeo API).
 */
export const setCurrentTime = ( player, seconds ) =>
	player.setCurrentTime( seconds );

/**
 * Play the video.
 *
 * @param {Object} player The Vimeo player instance.
 *
 * @return {Promise} A promise that resolves if the video was played successfully.
 *                   (original return from Vimeo API).
 */
export const play = ( player ) => player.play();

/**
 * Pause the video.
 *
 * @param {Object} player The Vimeo player instance.
 *
 * @return {Promise} A promise that resolves if the video was paused successfully.
 *                   (original return from Vimeo API).
 */
export const pause = ( player ) => player.pause();

/**
 * Add an timeupdate event listener to the player.
 *
 * @param {Object}   player   The Vimeo player instance.
 * @param {Function} callback Listener callback.
 *
 * @return {Function} The function to unsubscribe the event.
 */
export const onTimeupdate = ( player, callback ) => {
	const transformedCallback = ( event ) => {
		callback( event.seconds );
	};

	player.on( 'timeupdate', transformedCallback );

	return () => {
		player.off( 'timeupdate', transformedCallback );
	};
};
