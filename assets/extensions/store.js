/**
 * External dependencies
 */
import { keyBy } from 'lodash';

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
	extensionsSlugs: [],
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
	 * Sets the extensions.
	 *
	 * @param {Object}  response                 The response returned from the extensions endpoint.
	 * @param {Array}   response.extensions      The extensions array.
	 * @param {boolean} response.wccom_connected True if the site is connected to WC.com.
	 * @param {boolean} onlyEntities             Whether should update only the extension entities.
	 */
	setExtensions(
		{ extensions, wccom_connected: wcccomConnected },
		onlyEntities
	) {
		const enrichedExtensions = extensions.map( ( extension ) => {
			let canUpdate = false;
			// If the extension is hosted in WC.com, check that the site is connected and the subscription is not expired.
			if ( extension.has_update ) {
				canUpdate =
					! extension.wccom_product_id ||
					( wcccomConnected && ! extension.wccom_expired );
			}

			return { ...extension, canUpdate };
		} );

		return {
			type: 'SET_EXTENSIONS',
			extensions: enrichedExtensions,
			onlyEntities,
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
			yield actions.setExtensions(
				{
					extensions: response.completed,
					wccom_connected: response.wccom_connected,
				},
				true
			);

			yield dispatch( 'core/notices' ).createNotice(
				'success',
				__( 'Update completed succesfully!', 'sensei-lms' ),
				{
					type: 'snackbar',
				}
			);
		} catch ( error ) {
			yield actions.setExtensionsStatus( slugs, '' );
			yield actions.setError(
				sprintf(
					// translators: Placeholder is underlying error message.
					__(
						'There was an error while updating the plugin: %1$s.',
						'sensei-lms'
					),
					error.message
				)
			);
		} finally {
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
	getExtensions: ( { extensionsSlugs, entities } ) =>
		extensionsSlugs.map( ( slug ) => entities.extensions[ slug ] ),
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

		return actions.setExtensions( response );
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
	SET_EXTENSIONS: ( { extensions, onlyEntities }, state ) => {
		let newState = { ...state };

		if ( ! onlyEntities ) {
			// Update extension array (slugs).
			newState = {
				...newState,
				extensionsSlugs: [
					...extensions.map(
						( extension ) => extension.product_slug
					),
				],
			};
		}

		// Update extension entities.
		return {
			...newState,
			entities: {
				...newState.entities,
				extensions: {
					...newState.entities.extensions,
					...keyBy( extensions, 'product_slug' ),
				},
			},
		};
	},
	SET_EXTENSIONS_STATUS: ( { slugs, status }, state ) => {
		const extensionsWithStatus = { ...state.entities.extensions };

		slugs.forEach( ( slug ) => {
			extensionsWithStatus[ slug ] = {
				...extensionsWithStatus[ slug ],
				status,
			};
		} );

		return {
			...state,
			entities: {
				...state.entities,
				extensions: extensionsWithStatus,
			},
		};
	},
	SET_LAYOUT: ( { layout }, state ) => ( {
		...state,
		layout,
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
