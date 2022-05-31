/**
 * Internal dependencies
 */
import { initYouTubeExtension } from './youtube-extension';
import { initVideoExtension } from './video-extension';
import { initVimeoExtension } from './vimeo-extension';
import { initVideoPressExtension } from './videopress-extension';

// Initialize video extensions only after all the resources are loaded.
// This makes sure that Required Blocks feature can hook into the
// Course Video Progression feature before it starts firing it's hooks.
// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'load', () => {
	initVideoPressExtension();
	initVideoExtension();
	initVimeoExtension();
	initYouTubeExtension();
} );
