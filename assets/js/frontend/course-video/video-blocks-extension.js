/**
 * Internal dependencies
 */
import { initYouTubeExtension } from './youtube-extension';
import { initVideoExtension } from './video-extension';
import { initVimeoExtension } from './vimeo-extension';
import { initVideoPressExtension } from './videopress-extension';

// Initialize video extensions only after all the resources are loaded.
// This makes sure that we do not miss the sensei blocks store that comes
// from the Sensei Pro.
// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'load', () => {
	initVideoExtension();
	initVideoPressExtension();
	initVimeoExtension();
	initYouTubeExtension();
} );
