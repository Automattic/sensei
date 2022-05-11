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
 * @param {Object}   video
 * @param {Function} video.pauseVideo              The function that pauses the video when invoked.
 * @param {Function} video.registerVideoEndHandler Accepts a callback that is invoked when the video ends.
 * @param {string}   video.url                     The source url of the video. Used as an id.
 */
export const registerVideo = ( {
	pauseVideo = () => {},
	registerVideoEndHandler = () => {},
	url = '',
} ) => {
	const blocksStore = window.sensei?.store?.blocks;
	if ( courseVideoRequired ) {
		videos[ url ] = { pauseVideo, completed: false };
		if ( blocksStore ) {
			blocksStore.setAttributes( url, {
				required: true,
			} );
		} else {
			disableCompleteLessonButton();
		}
	}

	registerVideoEndHandler( () => {
		if ( courseVideoRequired ) {
			if ( blocksStore ) {
				blocksStore.setAttributes( url, { completed: true } );
			} else {
				videos[ url ].completed = true;

				if ( areAllComplete() ) {
					enableCompleteLessonButton();
				}
			}
		}

		if ( courseVideoAutoComplete && areAllComplete() ) {
			submitCompleteLessonForm();
		}
	} );
};

/**
 * Tells if all the required blocks in the current page are completed.
 *
 * @return {boolean} True if all the videos are completed. False otherwise.
 */
const areAllComplete = () => {
	const blocksStore = window.sensei?.store?.blocks;
	if ( blocksStore ) {
		const requiredBlockIds = blocksStore.getRequiredBlockIds();
		const completedBlocksCount = blocksStore
			.areBlocksCompleted( requiredBlockIds )
			.filter( ( completed ) => completed ).length;

		return requiredBlockIds.length === completedBlocksCount;
	}

	for ( const url in videos ) {
		if ( ! videos[ url ].completed ) {
			return false;
		}
	}

	return true;
};

/**
 * Disables the Complete Lesson buttons.
 */
const disableCompleteLessonButton = () => {
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
