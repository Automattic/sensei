/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import * as videoFileAdapter from './video-file-adapter';
import * as videopressAdapter from './videopress-adapter';
import * as youtubeAdapter from './youtube-adapter';
import * as vimeoAdapter from './vimeo-adapter';
import roundWithDecimals from './round-with-decimals';

const VIDEO_TYPE = videoFileAdapter.ADAPTER_NAME;
const VIDEOPRESS_TYPE = videopressAdapter.ADAPTER_NAME;
const YOUTUBE_TYPE = youtubeAdapter.ADAPTER_NAME;
const VIMEO_TYPE = vimeoAdapter.ADAPTER_NAME;

const ADAPTERS = {
	[ VIDEO_TYPE ]: videoFileAdapter,
	[ VIDEOPRESS_TYPE ]: videopressAdapter,
	[ YOUTUBE_TYPE ]: youtubeAdapter,
	[ VIMEO_TYPE ]: vimeoAdapter,
};

/**
 * A class that abstracts the use of the player APIs: Video, VideoPress, YouTube, and Vimeo.
 */
class Player {
	/**
	 * Player constructor.
	 *
	 * @param {HTMLVideoElement|HTMLIFrameElement} element The player element.
	 * @param {Window}                             w       A custom window.
	 */
	constructor( element, w = window ) {
		this.playerPromise = null;
		this.adapterName = null;
		this.element = element;
		this.w = w;

		this.setAdapter();
	}

	/**
	 * Set the player adapter.
	 */
	setAdapter() {
		if ( this.element instanceof this.w.HTMLVideoElement ) {
			this.adapterName = VIDEO_TYPE;
		} else if ( this.element instanceof this.w.HTMLIFrameElement ) {
			this.adapterName = Object.entries( ADAPTERS ).find(
				( [ , { EMBED_PATTERN = null } ] ) =>
					EMBED_PATTERN && this.element.src?.match( EMBED_PATTERN )
			)?.[ 0 ];
		}

		if ( ! this.adapterName ) {
			// eslint-disable-next-line no-console -- We want to expose the element with problem.
			console.error( 'Video adapter not found', this.element );
		}
	}

	/**
	 * Get the adapter.
	 *
	 * @access private
	 *
	 * @return {Object} The adapter.
	 */
	getAdapter() {
		return ADAPTERS[ this.adapterName ];
	}

	/**
	 * Get the video player.
	 *
	 * @return {Promise<Object|HTMLVideoElement|HTMLIFrameElement>} The video player through a promise.
	 */
	getPlayer() {
		if ( ! this.playerPromise ) {
			this.playerPromise =
				this.getAdapter()?.initializePlayer( this.element, this.w ) ||
				// A promise that never resolves if it doesn't exist.
				Promise.reject( new Error( 'Failed getting the player' ) );
		}

		return this.playerPromise;
	}

	/**
	 * Get the video duration.
	 *
	 * @return {Promise<number>} The duration of the video in seconds through a promise.
	 */
	getDuration() {
		return this.getPlayer().then( ( player ) =>
			this.getAdapter().getDuration( player )
		);
	}

	/**
	 * Get the video current time.
	 *
	 * @return {Promise<number>} The current video time in seconds through a promise.
	 */
	getCurrentTime() {
		return this.getPlayer()
			.then( ( player ) => this.getAdapter().getCurrentTime( player ) )
			.then( ( seconds ) => {
				return roundWithDecimals( seconds, 3 );
			} );
	}

	/**
	 * Set the video to a current time.
	 *
	 * @param {number} seconds The video time in seconds to set.
	 *
	 * @return {Promise} A promise that resolves if the video was set to a current time successfully.
	 */
	setCurrentTime( seconds ) {
		return this.getPlayer().then( ( player ) =>
			this.getAdapter().setCurrentTime( player, seconds )
		);
	}

	/**
	 * Play the video.
	 *
	 * @return {Promise} A promise that resolves if the video play was called successfully.
	 */
	play() {
		return this.getPlayer().then( ( player ) =>
			this.getAdapter().play( player )
		);
	}

	/**
	 * Pause the video.
	 *
	 * @return {Promise} A promise that resolves if the video pause was called successfully.
	 */
	pause() {
		return this.getPlayer().then( ( player ) =>
			this.getAdapter().pause( player )
		);
	}

	/**
	 * Add an event listener to the player.
	 *
	 * @param {string}   eventName Event name (supported: `timeupdate`).
	 * @param {Function} callback  Listener callback.
	 *
	 * @throws Will throw an error if the event is not supported.
	 *
	 * @return {Promise<Function>} The function to unsubscribe the event through a promise.
	 */
	on( eventName, callback ) {
		// Supported events.
		const events = {
			timeupdate: this.onTimeUpdate.bind( this ),
			ended: this.onEnded.bind( this ),
		};

		const event = events[ eventName ];

		if ( ! event ) {
			throw new Error( `Event ${ eventName } not supported` );
		}

		return event( callback );
	}

	/**
	 * Wrapper to the `onTimeUpdate` event from the adapters.
	 *
	 * @access private
	 *
	 * @param {Function} callback Listener callback.
	 *
	 * @return {Promise<Function>} The function to unsubscribe the event through a promise.
	 */
	onTimeUpdate( callback ) {
		const transformedCallback = ( seconds ) => {
			callback( roundWithDecimals( seconds, 3 ) );
		};

		return this.getPlayer().then( ( player ) =>
			this.getAdapter().onTimeupdate(
				player,
				transformedCallback,
				this.w
			)
		);
	}

	/**
	 * Wrapper to the `onEnded` event from the adapters.
	 *
	 * @access private
	 *
	 * @param {Function} callback Listener callback.
	 *
	 * @return {Promise<Function>} The function to unsubscribe the event through a promise.
	 */
	onEnded( callback ) {
		return this.getPlayer().then( ( player ) =>
			this.getAdapter().onEnded( player, callback, this.w )
		);
	}
}

/**
 * Hook to get the video duration.
 *
 * @param {Object} player Player instance.
 *
 * @return {number|undefined} The video duration.
 */
export const useVideoDuration = ( player ) => {
	const [ duration, setDuration ] = useState();

	useEffect( () => {
		player?.getDuration().then( setDuration );
	}, [ player ] );

	return duration;
};

export default Player;
