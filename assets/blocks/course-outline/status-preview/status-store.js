/**
 * WordPress dependencies
 */
import { select as selectData, registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Status } from './index';
import { select, controls } from '@wordpress/data-controls';
import { createReducerFromActionMap } from '../../../shared/data/store-helpers';

const DEFAULT_STATE = {
	completedLessons: [],
	trackedLessons: [],
};

/**
 * Status store actions.
 */
const actions = {
	/**
	 * Sets thecreateReducerFromActionMap status of a lesson.
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
		const trackedLessonIds = yield select(
			COURSE_STATUS_STORE,
			'getTrackedLessons'
		);

		const moduleDescendants = yield select(
			'core/block-editor',
			'getClientIdsOfDescendants',
			[ moduleId ]
		);

		const moduleLessons = moduleDescendants.filter( ( descendantId ) =>
			trackedLessonIds.includes( descendantId )
		);

		if ( 0 === moduleLessons.length ) {
			return;
		}

		if ( Status.COMPLETED === status || Status.NOT_STARTED === status ) {
			yield* moduleLessons.map( ( lessonId ) =>
				actions.setLessonStatus( lessonId, status )
			);
		} else {
			yield* moduleLessons
				.slice( 1 )
				.map( ( lessonId ) =>
					actions.setLessonStatus( lessonId, Status.NOT_STARTED )
				);

			return actions.setLessonStatus(
				moduleLessons[ 0 ],
				Status.COMPLETED
			);
		}
	},

	/**
	 * Creates the action to update state after a possible removal of a lesson.
	 *
	 * @param {string[]} descendantIds The ids of all outline block descendants.
	 *
	 * @return {Object} The action.
	 */
	stopTrackingRemovedLessons( descendantIds ) {
		return {
			type: 'REMOVE_LESSONS',
			descendantIds,
		};
	},

	/**
	 * Creates the action which marks a lesson as tracked by the store.
	 *
	 * @param {string} lessonId The lesson id.
	 *
	 * @return {Object} The action.
	 */
	trackLesson( lessonId ) {
		return {
			type: 'TRACK_LESSON',
			lessonId,
		};
	},

	/**
	 * Creates the action which marks a lesson as not tracked by the store.
	 *
	 * @param {string} lessonId The lesson id.
	 *
	 * @return {Object} The action.
	 */
	ignoreLesson( lessonId ) {
		return {
			type: 'IGNORE_LESSON',
			lessonId,
		};
	},
};

/**
 * Status store selectors.
 */
const selectors = {
	/**
	 * Get all the lessons that are tracked by the store.
	 *
	 * @param {Object} state                The state.
	 * @param {Array}  state.trackedLessons The tracked lessons.
	 *
	 * @return {Object} An object with the total and completed lesson counts.
	 */
	getTrackedLessons: ( { trackedLessons } ) => trackedLessons,

	/**
	 * Get the lesson counts.
	 *
	 * @param {Object} state                  The state.
	 * @param {Array}  state.trackedLessons   The ids of all the lessons.
	 * @param {Array}  state.completedLessons The ids of the completed lessons.
	 *
	 * @return {Object} An object with the total and completed lesson counts.
	 */
	getLessonCounts: ( { trackedLessons, completedLessons } ) => ( {
		totalLessonsCount: trackedLessons.length,
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
		completedLessons.includes( lessonId )
			? Status.COMPLETED
			: Status.NOT_STARTED,

	/**
	 * Returns the number of total and completed lessons of a module.
	 *
	 * @param {Object} state                  The state.
	 * @param {Array}  state.completedLessons The ids of the completed lessons.
	 * @param {Array}  state.trackedLessons   The ids of  all the lessons.
	 * @param {string} moduleId               The module id.
	 *
	 * @return {Object} The module lesson counts.
	 */
	getModuleLessonCounts( { completedLessons, trackedLessons }, moduleId ) {
		const moduleLessons = selectData( 'core/block-editor' )
			.getClientIdsOfDescendants( [ moduleId ] )
			.filter( ( descendantId ) =>
				trackedLessons.includes( descendantId )
			);

		const completedModuleLessons = moduleLessons.filter( ( lessonId ) =>
			completedLessons.includes( lessonId )
		);

		return {
			completedLessonsCount: completedModuleLessons.length,
			totalLessonsCount: moduleLessons.length,
		};
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
	 * Removes any lessons that don't exist in list of descendantIds.
	 *
	 * @param {Object} action               The action.
	 * @param {Array}  action.descendantIds The ids of all descendants of the outline block.
	 * @param {Object} state                The state.
	 *
	 * @return {Object} The new state.
	 */
	REMOVE_LESSONS: ( { descendantIds }, state ) => {
		const completedLessons = state.completedLessons.filter(
			( completedLesson ) => descendantIds.includes( completedLesson )
		);

		const trackedLessons = state.trackedLessons.filter( ( trackedLesson ) =>
			descendantIds.includes( trackedLesson )
		);

		// Do not update the state if no lessons were removed.
		if (
			trackedLessons.length === state.trackedLessons.length &&
			completedLessons.length === state.completedLessons.length
		) {
			return state;
		}

		return {
			...state,
			completedLessons,
			trackedLessons,
		};
	},

	/**
	 * Removes a lesson from the arrays of tracked lessons.
	 *
	 * @param {Object} action          The action.
	 * @param {Array}  action.lessonId The ids of the lesson to ignore.
	 * @param {Object} state           The state.
	 *
	 * @return {Object} The new state.
	 */
	IGNORE_LESSON: ( { lessonId }, state ) => {
		const completedLessons = state.completedLessons.filter(
			( completedLesson ) => completedLesson !== lessonId
		);

		const trackedLessons = state.trackedLessons.filter(
			( trackedLesson ) => trackedLesson !== lessonId
		);

		return {
			...state,
			completedLessons,
			trackedLessons,
		};
	},

	/**
	 * Adds a lesson from the arrays of tracked lessons.
	 *
	 * @param {Object} action          The action.
	 * @param {Array}  action.lessonId The ids of the lesson to track.
	 * @param {Object} state           The state.
	 *
	 * @return {Object} The new state.
	 */
	TRACK_LESSON: ( { lessonId }, state ) => {
		const trackedLessons = [ ...state.trackedLessons ];

		if ( trackedLessons.includes( lessonId ) ) {
			return state;
		}

		trackedLessons.push( lessonId );

		return {
			...state,
			trackedLessons,
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
