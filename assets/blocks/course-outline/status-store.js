import { select as selectData, registerStore } from '@wordpress/data';
import { createReducerFromActionMap } from '../../shared/data/store-helpers';
import { Status } from './status-control';
import { select, controls } from '@wordpress/data-controls';

const DEFAULT_STATE = {
	completedLessons: [],
	totalLessonsCount: 0,
};

/**
 * Status store actions.
 */
const actions = {
	/**
	 * Sets the status of a lesson.
	 *
	 * @param {string} lessonId The lesson id.
	 * @param {string} status   The lesson status.
	 *
	 * @return {Object} The setLessonStatus action.
	 */
	setLessonStatus( lessonId, status ) {
		return {
			type: 'SET_LESSON_STATUS',
			lessonId,
			status,
		};
	},

	/**
	 * Sets the status of a module by updating the status of its lessons.
	 *
	 * @param {string} moduleId The module block id.
	 * @param {string} status   The module status.
	 *
	 * @return {Object} Yields the lesson update actions.
	 */
	*setModuleStatus( moduleId, status ) {
		const lessonIds = yield select(
			'core/block-editor',
			'getClientIdsOfDescendants',
			[ moduleId ]
		);

		if ( 0 === lessonIds.length ) {
			return;
		}

		if ( Status.COMPLETED === status || Status.NOT_STARTED === status ) {
			yield* lessonIds.map( ( lessonId ) =>
				actions.setLessonStatus( lessonId, status )
			);
		} else {
			yield* lessonIds
				.slice( 1 )
				.map( ( lessonId ) =>
					actions.setLessonStatus( lessonId, Status.NOT_STARTED )
				);

			return actions.setLessonStatus( lessonIds[ 0 ], Status.COMPLETED );
		}
	},

	/**
	 * Creates the action to update state after a an update of the outline's structure.
	 *
	 * @param {string}   outlineId          The outline block id.
	 * @param {number}   totalLessonsCount  The count of the lessons.
	 * @param {string[]} outlineDescendants The descendants of the outline block.
	 *
	 * @return {Object} The action.
	 */
	refreshStructure( outlineId, totalLessonsCount, outlineDescendants ) {
		return {
			type: 'REFRESH_BLOCK_IDS',
			newDescendantIds: outlineDescendants,
			totalLessonsCount,
		};
	},
};

/**
 * Status store selectors.
 */
const selectors = {
	/**
	 * Get the lesson counts.
	 *
	 * @param {Object} state                   The state.
	 * @param {number} state.totalLessonsCount The number of lessons.
	 * @param {Array}  state.completedLessons  The ids of the completed lessons.
	 *
	 * @return {Object} An object with the total and completed lesson counts.
	 */
	getLessonCounts: ( { totalLessonsCount, completedLessons } ) => ( {
		totalLessonsCount,
		completedLessonsCount: completedLessons.length,
	} ),

	/**
	 * Gets the lesson status.
	 *
	 * @param {Object} state                  The state.
	 * @param {Array}  state.completedLessons The ids of the completed lessons.
	 * @param {string} lessonId               The lesson id.
	 *
	 * @return {string} The lesson status.
	 */
	getLessonStatus: ( { completedLessons }, lessonId ) =>
		completedLessons.some(
			( completedLessonId ) => lessonId === completedLessonId
		)
			? Status.COMPLETED
			: Status.NOT_STARTED,

	/**
	 * Calculates and gets the module status.
	 *
	 * @param {Object} state                  The state.
	 * @param {Array}  state.completedLessons The ids of the completed lessons.
	 * @param {string} moduleId               The module id.
	 *
	 * @return {string} The lesson status.
	 */
	getModuleStatus( { completedLessons }, moduleId ) {
		const lessonIds = selectData(
			'core/block-editor'
		).getClientIdsOfDescendants( [ moduleId ] );

		const completedLessonsCount = lessonIds.filter( ( lessonId ) =>
			completedLessons.some(
				( completedLessonId ) => lessonId === completedLessonId
			)
		).length;

		if ( 0 === completedLessonsCount ) {
			return Status.NOT_STARTED;
		} else if (
			lessonIds.length === completedLessonsCount &&
			lessonIds.length > 0
		) {
			return Status.COMPLETED;
		}

		return Status.IN_PROGRESS;
	},
};

/**
 * Status store reducer.
 */
const reducers = {
	/**
	 * Updates the lesson status.
	 *
	 * @param {Object} action          The action.
	 * @param {string} action.status   The lesson status.
	 * @param {string} action.lessonId The lesson id.
	 * @param {Object} state           The state.
	 *
	 * @return {Object} The new state.
	 */
	SET_LESSON_STATUS: ( { lessonId, status }, state ) => {
		let completedLessons = [ ...state.completedLessons ];

		if ( Status.COMPLETED === status ) {
			if ( ! completedLessons.includes( lessonId ) ) {
				completedLessons.push( lessonId );
			}
		} else {
			completedLessons = completedLessons.filter(
				( completedLessonId ) => completedLessonId !== lessonId
			);
		}

		return {
			...state,
			completedLessons,
		};
	},

	/**
	 * Checks if a lesson has been removed and updates the lessons.
	 *
	 * @param {Object} action                   The action.
	 * @param {Array}  action.newDescendantIds  The ids of all descendants of the outline block.
	 * @param {number} action.totalLessonsCount The number of total lessons.
	 * @param {Object} state                    The state.
	 *
	 * @return {Object} The new state.
	 */
	REFRESH_BLOCK_IDS: ( { newDescendantIds, totalLessonsCount }, state ) => {
		let completedLessons = [ ...state.completedLessons ];

		completedLessons.forEach( ( lesson ) => {
			if ( ! newDescendantIds.includes( lesson ) ) {
				completedLessons = completedLessons.filter(
					( completedLessonId ) => completedLessonId !== lesson
				);
			}
		} );

		return {
			...state,
			totalLessonsCount,
			completedLessons,
		};
	},
	DEFAULT: ( action, state ) => state,
};

export const COURSE_STATUS_STORE = 'sensei/course-status';

registerStore( COURSE_STATUS_STORE, {
	reducer: createReducerFromActionMap( reducers, DEFAULT_STATE ),
	actions,
	selectors,
	controls,
} );
