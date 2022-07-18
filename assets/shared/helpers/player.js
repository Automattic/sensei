const VIDEO_TYPE = 'video';
const VIDEOPRESS_TYPE = 'videopress';
const YOUTUBE_TYPE = 'youtube';
const VIMEO_TYPE = 'vimeo';

const players = {
	[ VIDEO_TYPE ]: {
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
		getDuration: ( player ) => Promise.resolve( player.duration ),
		setCurrentTime: ( player, seconds ) => {
			player.currentTime = seconds;
		},
		play: ( player ) => {
			player.play();
		},
		pause: ( player ) => {
			player.pause();
		},
	},
	[ VIDEOPRESS_TYPE ]: {
		embedPattern: /(videopress|video\.wordpress)\.com\/.+/i,
		initializePlayer: ( element ) => Promise.resolve( element ),
		getDuration: ( player ) =>
			new Promise( ( resolve ) =>
				// eslint-disable-next-line @wordpress/no-global-event-listener -- Not in a React context.
				window.addEventListener( 'message', ( event ) => {
					if (
						event.source !== player.contentWindow ||
						event.data.event !== 'videopress_durationchange' ||
						! event.data.durationMs
					) {
						return;
					}

					resolve( event.data.durationMs / 1000 );
				} )
			),
		setCurrentTime: ( player, seconds ) => {
			player.contentWindow.postMessage(
				{
					event: 'videopress_action_set_currenttime',
					currentTime: seconds,
				},
				'*'
			);
		},
		play: ( player ) => {
			player.contentWindow.postMessage(
				{ event: 'videopress_action_play' },
				'*'
			);
		},
		pause: ( player ) => {
			player.contentWindow.postMessage(
				{ event: 'videopress_action_pause' },
				'*'
			);
		},
	},
	[ YOUTUBE_TYPE ]: {
		embedPattern: /(youtu\.be|youtube\.com)\/.+/i,
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
		getDuration: ( player ) => player.getDuration(),
		setCurrentTime: ( player, seconds ) => {
			player.seekTo( seconds );
		},
		play: ( player ) => {
			player.playVideo();
		},
		pause: ( player ) => {
			player.pauseVideo();
		},
	},
	[ VIMEO_TYPE ]: {
		embedPattern: /vimeo\.com\/.+/i,
		initializePlayer: ( element ) =>
			Promise.resolve( new window.Vimeo.Player( element ) ),
		getDuration: ( player ) => player.getDuration(),
		setCurrentTime: ( player, seconds ) => {
			player.setCurrentTime( seconds );
		},
		play: ( player ) => {
			player.play();
		},
		pause: ( player ) => {
			player.pause();
		},
	},
};

class Player {
	constructor( element ) {
		this.playerPromise = null;
		this.type = null;
		this.element = element;

		try {
			this.setType( element );
		} catch ( e ) {
			// eslint-disable-next-line no-console -- We want to expose the element with problem.
			console.error( e, element );
		}
	}

	setType() {
		if ( this.element instanceof window.HTMLVideoElement ) {
			this.type = VIDEO_TYPE;
		} else if ( this.element instanceof window.HTMLIFrameElement ) {
			this.type = Object.entries( players ).find(
				( [ , p ] ) =>
					p.embedPattern && this.element.src.match( p.embedPattern )
			)?.[ 0 ];
		}

		if ( ! this.type ) {
			throw new Error( 'Video type not found' );
		}
	}

	getPlayer() {
		if ( ! this.playerPromise ) {
			this.playerPromise =
				players[ this.type ]?.initializePlayer( this.element ) ||
				// A promise that never resolves if it doesn't exist.
				new Promise( () => {} );
		}

		return this.playerPromise;
	}

	getDuration() {
		return this.getPlayer().then( ( player ) =>
			players[ this.type ].getDuration( player )
		);
	}

	setCurrentTime( seconds ) {
		return this.getPlayer().then( ( player ) =>
			players[ this.type ].setCurrentTime( player, seconds )
		);
	}

	play() {
		return this.getPlayer().then( ( player ) =>
			players[ this.type ].play( player )
		);
	}

	pause() {
		return this.getPlayer().then( ( player ) =>
			players[ this.type ].pause( player )
		);
	}
}

export default Player;
