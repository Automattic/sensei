const VIDEO_TYPE = 'video';
const VIDEOPRESS_TYPE = 'videopress';
const YOUTUBE_TYPE = 'youtube';
const VIMEO_TYPE = 'vimeo';

const players = {
	[ VIDEO_TYPE ]: {
		/**
		 * Initialize the player.
		 *
		 * @param {HTMLVideoElement} element The player element.
		 *
		 * @return {Promise<HTMLVideoElement>} The video player through a promise.
		 */
		initializePlayer: ( element ) => {
			// Return that it's ready when it can get the video duration.
			if ( ! isNaN( element.duration ) ) {
				return Promise.resolve( element );
			}

			return new Promise( ( resolve ) => {
				element.addEventListener( 'durationchange', () => {
					resolve( element );
				} );
			} );
		},

		/**
		 * Get the video duration.
		 *
		 * @param {HTMLVideoElement} player The player element.
		 *
		 * @return {Promise<number>} The duration of the video in seconds through a promise.
		 */
		getDuration: ( player ) => Promise.resolve( player.duration ),

		/**
		 * Set the video to a current time.
		 *
		 * @param {HTMLVideoElement} player  The player element.
		 * @param {number}           seconds The video time in seconds to set.
		 *
		 * @return {Promise} A promise that resolves if the video was set to a current time successfully.
		 */
		setCurrentTime: ( player, seconds ) => {
			try {
				player.currentTime = seconds;
				return Promise.resolve();
			} catch ( e ) {
				return Promise.reject( e );
			}
		},

		/**
		 * Play the video.
		 *
		 * @param {HTMLVideoElement} player The player element.
		 *
		 * @return {Promise} The native promise from the video play function.
		 */
		play: ( player ) => player.play(),

		/**
		 * Pause the video.
		 *
		 * @param {HTMLVideoElement} player The player element.
		 *
		 * @return {Promise} A promise that resolves if the video was paused successfully.
		 */
		pause: ( player ) => {
			try {
				player.pause();

				if ( player.paused ) {
					return Promise.resolve();
				}

				return Promise.reject( new Error( "Video didn't pause" ) );
			} catch ( e ) {
				return Promise.reject( e );
			}
		},

		/**
		 * Add an event listener to the player.
		 *
		 * @param {HTMLVideoElement} player    The player element.
		 * @param {string}           eventName Event name (currently only `timeupdate` is supported)
		 * @param {Function}         callback  Listener callback.
		 *
		 * @return {Function} The function to unsubscribe the event.
		 */
		on: ( player, eventName, callback ) => {
			const transformedCallback = ( event ) => {
				callback( event.target.currentTime );
			};

			player.addEventListener( eventName, transformedCallback );

			return () => {
				player.removeEventListener( eventName, transformedCallback );
			};
		},
	},
	[ VIDEOPRESS_TYPE ]: {
		/**
		 * The embed pattern to check if it's the respective type.
		 */
		embedPattern: /(videopress|video\.wordpress)\.com\/.+/i,

		/**
		 * Initialize the player.
		 *
		 * @param {HTMLIFrameElement} element The player element.
		 *
		 * @return {Promise<HTMLIFrameElement>} The video player through a promise.
		 */
		initializePlayer: ( element ) =>
			new Promise( ( resolve ) => {
				// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
				window.addEventListener( 'message', ( event ) => {
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

					resolve( element );
				} );
			} ),

		/**
		 * Get the video duration.
		 *
		 * @param {HTMLIFrameElement} player The player element.
		 *
		 * @return {Promise<number>} The duration of the video in seconds through a promise.
		 */
		getDuration: ( player ) => {
			const duration = player.dataset.duration;
			if ( ! duration ) {
				return Promise.reject(
					new Error( 'Video duration not found' )
				);
			}

			return Promise.resolve( parseFloat( player.dataset.duration ) );
		},

		/**
		 * Set the video to a current time.
		 *
		 * @param {HTMLIFrameElement} player  The player element.
		 * @param {number}            seconds The video time in seconds to set.
		 *
		 * @return {Promise} A promise that resolves if the video was set to a current time successfully.
		 */
		setCurrentTime: ( player, seconds ) => {
			try {
				player.contentWindow.postMessage(
					{
						event: 'videopress_action_set_currenttime',
						currentTime: seconds,
					},
					'*'
				);
				return Promise.resolve();
			} catch ( e ) {
				return Promise.reject( e );
			}
		},

		/**
		 * Play the video.
		 *
		 * @param {HTMLIFrameElement} player The player element.
		 *
		 * @return {Promise} A promise that resolves if the video was played successfully.
		 */
		play: ( player ) => {
			try {
				player.contentWindow.postMessage(
					{ event: 'videopress_action_play' },
					'*'
				);
				return Promise.resolve();
			} catch ( e ) {
				return Promise.reject( e );
			}
		},

		/**
		 * Pause the video.
		 *
		 * @param {HTMLIFrameElement} player The player element.
		 *
		 * @return {Promise} A promise that resolves if the video was paused successfully.
		 */
		pause: ( player ) => {
			try {
				player.contentWindow.postMessage(
					{ event: 'videopress_action_pause' },
					'*'
				);
				return Promise.resolve();
			} catch ( e ) {
				return Promise.reject( e );
			}
		},

		/**
		 * Add an event listener to the player.
		 *
		 * @param {HTMLIFrameElement} player    The player element.
		 * @param {string}            eventName Event name (currently only `timeupdate` is supported)
		 * @param {Function}          callback  Listener callback.
		 *
		 * @return {Function} The function to unsubscribe the event.
		 */
		on: ( player, eventName, callback ) => {
			const transformedCallback = ( event ) => {
				if (
					event.source !== player.contentWindow ||
					event.data.event !== `videopress_${ eventName }` ||
					! event.data.currentTimeMs
				) {
					return;
				}
				callback( event.data.currentTimeMs / 1000 );
			};

			// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
			window.addEventListener( 'message', transformedCallback );

			return () => {
				// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
				window.removeEventListener( 'message', transformedCallback );
			};
		},
	},
	[ YOUTUBE_TYPE ]: {
		/**
		 * The embed pattern to check if it's the respective type.
		 */
		embedPattern: /(youtu\.be|youtube\.com)\/.+/i,

		/**
		 * Initialize the player.
		 *
		 * @param {HTMLIFrameElement} element The player element.
		 *
		 * @return {Object} The YouTube player instance through a promise.
		 */
		initializePlayer: ( element ) =>
			new Promise( ( resolve ) => {
				window.senseiYouTubeIframeAPIReady.then( () => {
					const player =
						window.YT.get( element.id ) ||
						new window.YT.Player( element );

					const onReady = () => {
						resolve( player );
					};

					if ( player.getDuration ) {
						// Just in case it's called after the player is ready.
						onReady();
					} else {
						player.addEventListener( 'onReady', onReady );
					}
				} );
			} ),

		/**
		 * Get the video duration.
		 *
		 * @param {Object} player The YouTube player instance.
		 *
		 * @return {Promise<number>} The duration of the video in seconds through a promise.
		 */
		getDuration: ( player ) => Promise.resolve( player.getDuration() ),

		/**
		 * Set the video to a current time.
		 *
		 * @param {Object} player  The YouTube player instance.
		 * @param {number} seconds The video time in seconds to set.
		 *
		 * @return {Promise} A resolved promise.
		 */
		setCurrentTime: ( player, seconds ) => {
			player.seekTo( seconds );
			return Promise.resolve();
		},

		/**
		 * Play the video.
		 *
		 * @param {Object} player The YouTube player instance.
		 *
		 * @return {Promise} A resolved promise.
		 */
		play: ( player ) => {
			player.playVideo();
			return Promise.resolve();
		},

		/**
		 * Pause the video.
		 *
		 * @param {Object} player The YouTube player instance.
		 *
		 * @return {Promise} A resolved promise.
		 */
		pause: ( player ) => {
			player.pauseVideo();
			return Promise.resolve();
		},

		/**
		 * Add an event listener to the player.
		 *
		 * @param {Object}   player    The YouTube player instance.
		 * @param {string}   eventName Event name (currently only `timeupdate` is supported)
		 * @param {Function} callback  Listener callback.
		 *
		 * @return {Function} The function to unsubscribe the event.
		 */
		on: ( player, eventName, callback ) => {
			const timer = 250;

			const interval = setInterval( () => {
				if (
					player.getPlayerState() === window.YT.PlayerState.PLAYING
				) {
					callback( player.getCurrentTime() );
				}
			}, timer );

			return () => {
				clearInterval( interval );
			};
		},
	},
	[ VIMEO_TYPE ]: {
		/**
		 * The embed pattern to check if it's the respective type.
		 */
		embedPattern: /vimeo\.com\/.+/i,

		/**
		 * Initialize the player.
		 *
		 * @param {HTMLIFrameElement} element The player element.
		 *
		 * @return {Object} The Vimeo player instance through a promise.
		 */
		initializePlayer: ( element ) =>
			Promise.resolve( new window.Vimeo.Player( element ) ),

		/**
		 * Get the video duration.
		 *
		 * @param {Object} player The Vimeo player instance.
		 *
		 * @return {Promise<number>} The duration of the video in seconds through a promise
		 *                           (original return from Vimeo API).
		 */
		getDuration: ( player ) => player.getDuration(),

		/**
		 * Set the video to a current time.
		 *
		 * @param {Object} player  The Vimeo player instance.
		 * @param {number} seconds The video time in seconds to set.
		 *
		 * @return {Promise} A promise that resolves if the video was set to a current time successfully.
		 *                   (original return from Vimeo API).
		 */
		setCurrentTime: ( player, seconds ) => player.setCurrentTime( seconds ),

		/**
		 * Play the video.
		 *
		 * @param {Object} player The Vimeo player instance.
		 *
		 * @return {Promise} A promise that resolves if the video was played successfully.
		 *                   (original return from Vimeo API).
		 */
		play: ( player ) => player.play(),

		/**
		 * Pause the video.
		 *
		 * @param {Object} player The Vimeo player instance.
		 *
		 * @return {Promise} A promise that resolves if the video was paused successfully.
		 *                   (original return from Vimeo API).
		 */
		pause: ( player ) => player.pause(),

		/**
		 * Add an event listener to the player.
		 *
		 * @param {Object}   player    The Vimeo player instance.
		 * @param {string}   eventName Event name (currently only `timeupdate` is supported)
		 * @param {Function} callback  Listener callback.
		 *
		 * @return {Function} The function to unsubscribe the event.
		 */
		on: ( player, eventName, callback ) => {
			const transformedCallback = ( event ) => {
				callback( event.seconds );
			};

			player.on( eventName, transformedCallback );

			return () => {
				player.off( eventName, transformedCallback );
			};
		},
	},
};

/**
 * A class that abstracts the use of the player APIs: Video, VideoPress, YouTube, and Vimeo.
 */
class Player {
	/**
	 * Player constructor.
	 *
	 * @param {HTMLVideoElement|HTMLIFrameElement} element The player element.
	 */
	constructor( element ) {
		this.playerPromise = null;
		this.type = null;
		this.element = element;

		try {
			this.setType();
		} catch ( e ) {
			// eslint-disable-next-line no-console -- We want to expose the element with problem.
			console.error( e, element );
		}
	}

	/**
	 * Set the player type.
	 *
	 * @throws Will throw an error if the video type is not found.
	 */
	setType() {
		if ( this.element instanceof window.HTMLVideoElement ) {
			this.type = VIDEO_TYPE;
		} else if ( this.element instanceof window.HTMLIFrameElement ) {
			this.type = Object.entries( players ).find(
				( [ , p ] ) =>
					p.embedPattern && this.element.src?.match( p.embedPattern )
			)?.[ 0 ];
		}

		if ( ! this.type ) {
			throw new Error( 'Video type not found' );
		}
	}

	/**
	 * Get the video player.
	 *
	 * @return {Promise<Object|HTMLVideoElement|HTMLIFrameElement>} The video player through a promise.
	 */
	getPlayer() {
		if ( ! this.playerPromise ) {
			this.playerPromise =
				players[ this.type ]?.initializePlayer( this.element ) ||
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
			players[ this.type ].getDuration( player )
		);
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
			players[ this.type ].setCurrentTime( player, seconds )
		);
	}

	/**
	 * Play the video.
	 *
	 * @return {Promise} A promise that resolves if the video was played successfully.
	 */
	play() {
		return this.getPlayer().then( ( player ) =>
			players[ this.type ].play( player )
		);
	}

	/**
	 * Pause the video.
	 *
	 * @return {Promise} A promise that resolves if the video was paused successfully.
	 */
	pause() {
		return this.getPlayer().then( ( player ) =>
			players[ this.type ].pause( player )
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
		// Check supported events.
		if ( eventName !== 'timeupdate' ) {
			throw new Error( `Event ${ eventName } not supported` );
		}

		return this.getPlayer().then( ( player ) =>
			players[ this.type ].on( player, eventName, callback )
		);
	}
}

export default Player;
