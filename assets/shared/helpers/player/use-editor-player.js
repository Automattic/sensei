/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import Player from './player';

/**
 * Add a script to a body.
 *
 * @param {HTMLBodyElement} body   The body where the script will be appended.
 * @param {string}          src    Script src.
 * @param {string}          id     Script ID.
 * @param {Function}        onLoad Script load callback.
 */
const addScript = ( body, src, id, onLoad ) => {
	const script = document.createElement( 'script' );

	script.src = src;
	script.id = id;
	script.onload = onLoad;

	body.append( script );
};

/**
 * Hook to get the editor player related to a block.
 *
 * @param {Object} videoBlock Video block object.
 *
 * @return {Object|undefined} The player instance or undefined if it's not ready yet.
 */
const useEditorPlayer = ( videoBlock ) => {
	const [ player, setPlayer ] = useState( undefined );

	// Check embed fetching.
	const { fetching } = useSelect(
		( select ) => ( {
			fetching: select( coreStore ).isRequestingEmbedPreview(
				videoBlock?.attributes?.url
			),
		} ),
		[ videoBlock?.attributes?.url ]
	);

	// Check if block is selected. We need to get the player reference again when it's video block
	// because it re-creates the video element when it's (un)selected.
	const { isBlockSelected } = useSelect(
		( select ) => ( {
			isBlockSelected: select( blockEditorStore ).isBlockSelected(
				videoBlock.clientId
			),
		} ),
		[ videoBlock.clientId ]
	);

	useEffect( () => {
		// This timeout is to make sure it will run after the effects of the other blocks, which
		// creates the iframe and video tags.
		setTimeout( () => {
			if ( 'core/video' === videoBlock.name ) {
				const video = document.querySelector(
					`#block-${ videoBlock.clientId } video`
				);

				setPlayer( new Player( video ) );
			} else {
				const scriptId = 'player-script';

				const sandboxIframe = document.querySelector(
					`#block-${ videoBlock.clientId } iframe`
				);
				const w = sandboxIframe?.contentWindow;
				const doc = sandboxIframe?.contentDocument;
				const playerIframe = doc?.querySelector( 'iframe' );
				const playerScript = doc?.getElementById( scriptId );

				if ( ! playerIframe || playerScript ) {
					return;
				}

				switch ( videoBlock.attributes.providerNameSlug ) {
					case 'vimeo': {
						addScript(
							doc.body,
							'https://player.vimeo.com/api/player.js',
							scriptId,
							() => {
								setPlayer(
									new Player(
										doc.querySelector( 'iframe' ),
										w
									)
								);
							}
						);

						break;
					}
					case 'youtube': {
						// Update the current embed to enable JS API.
						if (
							playerIframe &&
							! playerIframe.src.includes( 'enablejsapi=1' )
						) {
							playerIframe.src =
								playerIframe.src + '&enablejsapi=1';
						}

						w.senseiYouTubeIframeAPIReady = new Promise(
							( resolve ) => {
								w.onYouTubeIframeAPIReady = () => {
									resolve();
								};
							}
						);

						addScript(
							doc.body,
							'https://www.youtube.com/iframe_api',
							scriptId,
							() => {
								setPlayer(
									new Player(
										doc.querySelector( 'iframe' ),
										w
									)
								);
							}
						);

						break;
					}
					case 'videopress': {
						setPlayer(
							new Player( doc.querySelector( 'iframe' ), w )
						);
						break;
					}
				}
			}
		}, 1 );
	}, [ videoBlock, isBlockSelected, fetching ] );

	return player;
};

export default useEditorPlayer;
