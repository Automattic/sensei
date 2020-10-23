import { select as selectData, registerStore } from '@wordpress/data';
import { createReducerFromActionMap } from '../../shared/data/store-helpers';
import { Status } from './status-control';
import { select, controls } from '@wordpress/data-controls';

const DEFAULT_STATE = {
	completedLessons: [],
	trackedLessons: [],
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
	 * Creates the action to update state after a an update of the outline's structure.
	 *
	 * @param {string}   outlineId      The outline block id.
	 * @param {string[]} trackedLessons The ids of all tracked lessons.
	 *
	 * @return {Object} The action.
	 */
	refreshStructure( outlineId, trackedLessons ) {
		return {
			type: 'REFRESH_STRUCTURE',
			trackedLessons,
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
	 * Calculates and gets the module status.
	 *
	 * @param {Object} state                  The state.
	 * @param {Array}  state.completedLessons The ids of the completed lessons.
	 * @param {Array}  state.trackedLessons   The ids of  all the lessons.
	 * @param {string} moduleId               The module id.
	 *
	 * @return {string} The lesson status.
	 */
	getModuleStatus( { completedLessons, trackedLessons }, moduleId ) {
		const moduleLessons = selectData( 'core/block-editor' )
			.getClientIdsOfDescendants( [ moduleId ] )
			.filter( ( descendantId ) =>
				trackedLessons.includes( descendantId )
			);

		const completedLessonsCount = moduleLessons.filter( ( lessonId ) =>
			completedLessons.includes( lessonId )
		).length;

		if ( 0 === completedLessonsCount ) {
			return Status.NOT_STARTED;
		} else if (
			moduleLessons.length === completedLessonsCount &&
			moduleLessons.length > 0
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
	 * Updates internal state according to a new array of lessons.
	 *
	 * @param {Object} action                The action.
	 * @param {Array}  action.trackedLessons The ids of all lessons of the outline block.
	 * @param {Object} state                 The state.
	 *
	 * @return {Object} The new state.
	 */
	REFRESH_STRUCTURE: ( { trackedLessons }, state ) => {
		let completedLessons = [ ...state.completedLessons ];

		completedLessons = completedLessons.filter( ( completedLessonId ) =>
			trackedLessons.includes( completedLessonId )
		);

		return {
			...state,
			completedLessons,
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
