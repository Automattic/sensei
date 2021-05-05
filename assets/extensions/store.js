/**
 * External dependencies
 */
import { keyBy, merge } from 'lodash';

/**
 * WordPress dependencies
 */
import { registerStore, select, dispatch } from '@wordpress/data';
import { controls, apiFetch } from '@wordpress/data-controls';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { createReducerFromActionMap } from '../shared/data/store-helpers';

/**
 * Extension statuses.
 */
const STATUS = {
	IN_PROGRESS: 'in-progress',
	IN_QUEUE: 'in-queue',
};

/**
 * Store name.
 */
export const EXTENSIONS_STORE = 'sensei/extensions';

/**
 * Default store state.
 */
const DEFAULT_STATE = {
	// Extensions list to be mapped using the entities.
	extensionSlugs: [],
	entities: { extensions: {} },
	layout: [],
	error: null,
};

/**
 * Checks whether status is a loading status.
 *
 * @param {string} status Status to check.
 *
 * @return {string} Whether is a loading status.
 */
export const isLoadingStatus = ( status ) =>
	Object.values( STATUS ).includes( status );

/**
 * Extension store actions.
 */
const actions = {
	/**
	 * Sets the extensions list.
	 *
	 * @param {Array} extensionSlugs The extensions slugs array.
	 */
	setExtensions( extensionSlugs ) {
		return {
			type: 'SET_EXTENSIONS',
			extensionSlugs,
		};
	},

	/**
	 * Sets entities.
	 *
	 * @param {Object} entities Entities to set.
	 */
	setEntities( entities ) {
		return {
			type: 'SET_ENTITIES',
			entities,
		};
	},

	/**
	 * Updates the provided extensions.
	 *
	 * @param {Array} extensions The extensions to update.
	 */
	*updateExtensions( extensions ) {
		const slugs = extensions.map( ( extension ) => extension.product_slug );

		const inProgressExtensions = yield select(
			EXTENSIONS_STORE
		).getExtensionsByStatus( STATUS.IN_PROGRESS );

		// Add extensions to queue and skip if extensions are already updating.
		if ( inProgressExtensions.length > 0 ) {
			yield actions.setExtensionsStatus( slugs, STATUS.IN_QUEUE );
			return;
		}

		yield actions.setExtensionsStatus( slugs, STATUS.IN_PROGRESS );

		try {
			const response = yield apiFetch( {
				path: '/sensei-internal/v1/sensei-extensions/update',
				method: 'POST',
				data: { plugins: slugs },
			} );

			yield actions.setError( null );
			yield actions.setEntities( {
				extensions: keyBy( response.completed, 'product_slug' ),
			} );

			yield dispatch( 'core/notices' ).createNotice(
				'success',
				__( 'Update completed succesfully!', 'sensei-lms' ),
				{
					type: 'snackbar',
				}
			);
		} catch ( error ) {
			const errorMessage = Object.keys( error.errors )
				.map( ( key ) => error.errors[ key ].join( ' ' ) )
				.join( ' ' );

			yield actions.setError(
				sprintf(
					// translators: Placeholder is underlying error message.
					__(
						'There was an error while updating the plugin: %1$s',
						'sensei-lms'
					),
					errorMessage
				)
			);
		} finally {
			yield actions.setExtensionsStatus( slugs, '' );

			// Update queue extensions, if exists.
			const queuedExtensions = yield select(
				EXTENSIONS_STORE
			).getExtensionsByStatus( STATUS.IN_QUEUE );

			if ( 0 === queuedExtensions.length ) {
				return;
			}

			yield actions.updateExtensions( queuedExtensions );
		}
	},

	/**
	 * Set extensions in progress.
	 *
	 * @param {string} slugs  Extensions in progress.
	 * @param {string} status Status.
	 */
	setExtensionsStatus( slugs, status ) {
		return {
			type: 'SET_EXTENSIONS_STATUS',
			slugs,
			status,
		};
	},

	/**
	 * Set the extensions layout.
	 *
	 * @param {Object} obj        Layout object.
	 * @param {Array}  obj.layout Extensions layout.
	 */
	setLayout( { layout = [] } ) {
		return {
			type: 'SET_LAYOUT',
			layout,
		};
	},

	/**
	 * Set the error message.
	 *
	 * @param {string} error The error.
	 */
	setError( error ) {
		return {
			type: 'SET_ERROR',
			error,
		};
	},
};

/**
 * Extension store selectors.
 */
const selectors = {
	getExtensions: ( { extensionSlugs, entities } ) =>
		extensionSlugs.map( ( slug ) => entities.extensions[ slug ] ),
	getExtensionsByStatus: ( args, status ) =>
		selectors
			.getExtensions( args )
			.filter( ( extension ) => status === extension.status ),
	getEntities: ( { entities }, entity ) => entities[ entity ],
	getLayout: ( { layout } ) => layout,
	getError: ( { error } ) => error,
};

/**
 * Extension store resolvers.
 */
const resolvers = {
	/**
	 * Loads the extensions during initialization.
	 */
	*getExtensions() {
		const response = yield apiFetch( {
			path: '/sensei-internal/v1/sensei-extensions?type=plugin',
		} );

		yield actions.setEntities( {
			extensions: keyBy( response.extensions, 'product_slug' ),
		} );
		yield actions.setExtensions(
			response.extensions.map( ( extension ) => extension.product_slug )
		);
	},

	/**
	 * Loads the extensions layout.
	 */
	*getLayout() {
		const response = yield apiFetch( {
			path: '/sensei-internal/v1/sensei-extensions/layout',
		} );

		return actions.setLayout( response );
	},
};

/**
 * Store reducer.
 */
const reducer = {
	SET_EXTENSIONS: ( { extensionSlugs }, state ) => ( {
		...state,
		extensionSlugs,
	} ),
	SET_EXTENSIONS_STATUS: ( { slugs, status }, state ) => ( {
		...state,
		entities: {
			...state.entities,
			extensions: Object.keys( state.entities.extensions ).reduce(
				( acc, slug ) => ( {
					...acc,
					[ slug ]: {
						...state.entities.extensions[ slug ],
						status: slugs.includes( slug )
							? status
							: state.entities.extensions[ slug ].status,
					},
				} ),
				{}
			),
		},
	} ),
	SET_LAYOUT: ( { layout }, state ) => ( {
		...state,
		layout,
	} ),
	SET_ENTITIES: ( { entities }, state ) => ( {
		...state,
		entities: merge( {}, state.entities, entities ),
	} ),
	SET_ERROR: ( { error }, state ) => ( {
		...state,
		error,
	} ),
	DEFAULT: ( action, state ) => state,
};

registerStore( EXTENSIONS_STORE, {
	reducer: createReducerFromActionMap( reducer, DEFAULT_STATE ),
	actions,
	selectors,
	resolvers,
	controls,
} );
