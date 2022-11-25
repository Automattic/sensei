/**
 * WordPress dependencies
 */
import { doAction, applyFilters } from '@wordpress/hooks';

/**
 * The Course Video Progression settings.
 */
const {
	courseVideoRequired,
	courseVideoAutoComplete,
	courseVideoAutoPause,
} = window.sensei.courseVideoSettings;

/**
 * Map of videos.
 */
const videos = {};

/**
 * Registers the videos to manage.
 *
 * @param {Object}      video
 * @param {Function}    video.pauseVideo              The function that pauses the video when invoked.
 * @param {Function}    video.registerVideoEndHandler Accepts a callback that is invoked when the video ends.
 * @param {string}      video.url                     The source url of the video. Used as an id.
 * @param {HTMLElement} video.blockElement            The DOM element of the video block.
 */
export const registerVideo = ( {
	pauseVideo = () => {},
	registerVideoEndHandler = () => {},
	url = '',
	blockElement,
} ) => {
	const isBlockRequired = blockElement.hasAttribute(
		'data-sensei-is-required'
	);
	const isBlockNotRequired = blockElement.hasAttribute(
		'data-sensei-is-not-required'
	);

	// Block level setting overwrites the course level setting.
	if ( isBlockRequired || ( courseVideoRequired && ! isBlockNotRequired ) ) {
		/**
		 * Called when a required video for the current lesson is registered.
		 *
		 * @since 4.4.3
		 *
		 * @hook sensei.videoProgression.registerVideo Hook used to run an arbitrary code when new required
		 *                                             video for the current lesson is registered.
		 * @param {Object}      video
		 * @param {string}      video.url          The source url of the video.
		 * @param {HTMLElement} video.blockElement The video block DOM element.
		 */
		doAction( 'sensei.videoProgression.registerVideo', {
			url,
			blockElement,
		} );
		videos[ url ] = { pauseVideo, completed: false };
		disableCompleteLessonButton();
	}

	registerVideoEndHandler( () => {
		// Block level setting overwrites the course level setting.
		if (
			isBlockRequired ||
			( courseVideoRequired && ! isBlockNotRequired )
		) {
			/**
			 * Called when a required video for the current lesson is finished playing.
			 *
			 * @since 4.4.3
			 *
			 * @hook sensei.videoProgression.videoEnded Hook used to run an arbitrary code when a required video
			 *                                          for the current lesson is finished playing.
			 * @param {Object} video
			 * @param {string} video.url The source url of the video.
			 */
			doAction( 'sensei.videoProgression.videoEnded', { url } );
			videos[ url ].completed = true;
			if ( areAllCompleted() ) {
				enableCompleteLessonButton();
			}
		}

		if ( courseVideoAutoComplete && areAllCompleted() ) {
			submitCompleteLessonForm();
		}
	} );
};

/**
 * Tells if all the required videos in the current page are completed.
 *
 * @return {boolean} True if all the videos are completed. False otherwise.
 */
const areAllCompleted = () => {
	let allCompleted = true;
	for ( const url in videos ) {
		if ( ! videos[ url ].completed ) {
			allCompleted = false;
		}
	}

	/**
	 * Tells if all the required videos for the current lesson are finished playing or not.
	 *
	 * @since 4.4.3
	 *
	 * @hook sensei.videoProgression.allCompleted Hook used to tell if all the required videos for the current lesson have finished playing.
	 *
	 * @param {boolean} allCompleted Whether all the required videos for the current lesson are completed.
	 */
	allCompleted = applyFilters(
		'sensei.videoProgression.allCompleted',
		allCompleted
	);

	return allCompleted;
};

/**
 * Disables the Complete Lesson buttons.
 */
const disableCompleteLessonButton = () => {
	if (
		/**
		 * Whether or not the Lesson Complete button should be disabled or not.
		 *
		 * @since 4.4.3
		 *
		 * @hook sensei.videoProgression.preventLessonCompletion Hook is used to tell if the "Complete Lesson" button should be disabled or not.
		 *
		 * @param {boolean} shouldPrevent Whether to prevent users from completing the lesson.
		 */
		! applyFilters(
			'sensei.videoProgression.preventLessonCompletion',
			true
		)
	) {
		return;
	}

	document
		.querySelectorAll( '[data-id="complete-lesson-button"]' )
		.forEach( ( button ) => {
			button.disabled = true;
			button.addEventListener( 'click', preventClick );
		} );
};

/**
 * Prevents the browser event from default behavior
 * and from bubbling it up the DOM tree.
 *
 * @param {MouseEvent} event The click event.
 * @returns {false} Returns false always. This prevents event bubbling.
 */
const preventClick = ( event ) => {
	event.preventDefault();
	return false;
};

/**
 * Enables the Complete Lesson buttons.
 */
const enableCompleteLessonButton = () => {
	if (
		/**
		 * Whether or not the Lesson Complete button should be enabled or not.
		 *
		 * @since 4.4.3
		 *
		 * @hook sensei.videoProgression.allowLessonCompletion Hook is used to tell if the "Complete Lesson" button should be enabled or not.
		 *
		 * @param {boolean} shouldAllow Whether to allow users to complete the lesson.
		 */
		! applyFilters( 'sensei.videoProgression.allowLessonCompletion', true )
	) {
		return;
	}

	document
		.querySelectorAll( '[data-id="complete-lesson-button"]' )
		.forEach( ( button ) => {
			button.removeEventListener( 'click', preventClick );
			button.disabled = false;
		} );
};

/**
 * Completes the lesson.
 */
const submitCompleteLessonForm = () => {
	const completeButton = document.querySelector(
		'[data-id="complete-lesson-button"]'
	);
	if ( completeButton ) {
		setTimeout( () => {
			completeButton.click();
		}, 3000 );
	}
};

/**
 * If pause video setting is set. Then attach an event listener
 * to detect user navigating away and pause the videos.
 */
if ( courseVideoAutoPause && document.hidden !== undefined ) {
	// eslint-disable-next-line @wordpress/no-global-event-listener
	document.addEventListener(
		'visibilitychange',
		() => {
			if ( ! document.hidden ) {
				return;
			}

			for ( const url in videos ) {
				const pause = videos[ url ].pauseVideo;
				if ( 'function' === typeof pause ) {
					pause();
				}
			}
		},
		false
	);
}
