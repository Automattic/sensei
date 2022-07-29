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
import Player from './index';

const API_SCRIPT_ID = 'player-script';
const YOUTUBE_API_SRC = 'https://www.youtube.com/iframe_api';
const VIMEO_API_SRC = 'https://player.vimeo.com/api/player.js';

/**
 * Hook to get the dependencies used as trigger to (re)set the player instance.
 *
 * @param {Object} videoBlock Video block object.
 *
 * @return {Object} Object containing embed fetching and block selected state.
 */
const useTriggerDependencies = ( videoBlock ) => {
	// Check embed fetching.
	const { fetching } = useSelect(
		( select ) => ( {
			fetching: select( coreStore ).isRequestingEmbedPreview(
				videoBlock?.attributes?.url
			),
		} ),
		[ videoBlock?.attributes?.url ]
	);

	const { isBlockSelected, lastBlockAttributeChange } = useSelect(
		( select ) => ( {
			// Check if block is selected. We need to get the player reference again when it's video
			// block because it re-creates the video element when it's (un)selected.
			isBlockSelected: select( blockEditorStore ).isBlockSelected(
				videoBlock.clientId
			),
			// This prop is used to for the case when the user edit the embed URL, don't change the
			// value and click on "Embed" again.
			lastBlockAttributeChange: select(
				blockEditorStore
			).__experimentalGetLastBlockAttributeChanges()?.[
				videoBlock.clientId
			],
		} ),
		[ videoBlock.clientId ]
	);

	return { fetching, isBlockSelected, lastBlockAttributeChange };
};

/**
 * A wrapper to useEffect with a setTimeout, in order to delay to the next event cycle.
 *
 * @param {Function} effect Effect callback.
 * @param {Array}    deps   Effect dependencies.
 */
const useDelayedEffect = ( effect, deps ) => {
	useEffect( () => {
		setTimeout( () => {
			effect();
		} );
	}, deps ); // eslint-disable-line react-hooks/exhaustive-deps -- Wrapper to useEffect.
};

/**
 * Add a script to a body.
 *
 * @param {HTMLBodyElement} body   The body where the script will be appended.
 * @param {string}          src    Script src.
 * @param {Function}        onLoad Script load callback.
 */
const addScript = ( body, src, onLoad ) => {
	const script = document.createElement( 'script' );

	script.src = src;
	script.id = API_SCRIPT_ID;
	script.onload = onLoad;

	body.append( script );
};

/**
 * It prepares the YouTube iframe, enabling JS API, and adding the promise for the API Ready event.
 *
 * @param {HTMLIFrameElement} playerIframe YouTube player iframe.
 * @param {Window}            w            Window object inside the sandbox (the parent of the
 *                                         player iframe).
 */
const prepareYouTubeIframe = ( playerIframe, w ) => {
	// Update the current embed to enable JS API.
	if ( playerIframe && ! playerIframe.src.includes( 'enablejsapi=1' ) ) {
		playerIframe.src = playerIframe.src + '&enablejsapi=1';
	}

	w.senseiYouTubeIframeAPIReady = new Promise( ( resolve ) => {
		w.onYouTubeIframeAPIReady = () => {
			resolve();
		};
	} );
};

/**
 * Hook to get the editor player related to a block.
 *
 * @param {Object} videoBlock Video block object.
 *
 * @return {Object|undefined} The player instance or undefined if it's not ready yet.
 */
const useEditorPlayer = ( videoBlock ) => {
	const [ player, setPlayer ] = useState();

	const {
		fetching,
		isBlockSelected,
		lastBlockAttributeChange,
	} = useTriggerDependencies( videoBlock );

	// This is delayed to make sure it will run after the effects of the other blocks, which
	// creates the iframe and video tags.
	useDelayedEffect( () => {
		// Video block.
		if ( 'core/video' === videoBlock.name ) {
			const video = document.querySelector(
				`#block-${ videoBlock.clientId } video`
			);

			setPlayer( new Player( video ) );

			return;
		}

		// Embed block.
		const sandboxIframe = document.querySelector(
			`#block-${ videoBlock.clientId } iframe`
		);
		const w = sandboxIframe?.contentWindow;
		const doc = sandboxIframe?.contentDocument;
		const playerIframe = doc?.querySelector( 'iframe' );
		const playerScript = doc?.getElementById( API_SCRIPT_ID );

		// Skip if iframe is not found or player was already added.
		if ( ! playerIframe || playerScript ) {
			return;
		}

		switch ( videoBlock.attributes.providerNameSlug ) {
			case 'youtube': {
				prepareYouTubeIframe( playerIframe, w );

				addScript( doc.body, YOUTUBE_API_SRC, () => {
					setPlayer( new Player( doc.querySelector( 'iframe' ), w ) );
				} );

				break;
			}
			case 'vimeo': {
				addScript( doc.body, VIMEO_API_SRC, () => {
					setPlayer( new Player( doc.querySelector( 'iframe' ), w ) );
				} );

				break;
			}
			case 'videopress': {
				setPlayer( new Player( doc.querySelector( 'iframe' ), w ) );
				break;
			}
		}
	}, [ fetching, isBlockSelected, lastBlockAttributeChange ] );

	return player;
};

export default useEditorPlayer;
