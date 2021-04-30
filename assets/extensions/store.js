/**
 * External dependencies
 */
import { keyBy } from 'lodash';

/**
 * WordPress dependencies
 */
import { registerStore, dispatch } from '@wordpress/data';
import { controls, apiFetch } from '@wordpress/data-controls';
import { __, sprintf } from '@wordpress/i18n';

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

		try {
			yield actions.setExtensionsStatus( slugs, 'in-progress' );
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

			dispatch( 'core/notices' ).createNotice(
				'success',
				__( 'Update completed succesfully!', 'sensei-lms' ),
				{
					type: 'snackbar',
				}
			);
		} catch ( error ) {
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
	getExtensions: ( { extensions, entities } ) =>
		extensions.map( ( slug ) => entities.extensions[ slug ] ),
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
};

/**
 * Extensions store reducer.
 *
 * @param {Object} state  The store state.
 * @param {Object} action The action to handle.
 */
const reducer = (
	state = {
		extensions: [],
		entities: { extensions: {} },
		error: null,
	},
	action
) => {
	switch ( action.type ) {
		case 'SET_EXTENSIONS':
			let newState = { ...state };

			if ( ! action.onlyEntities ) {
				// Update extension array (slugs).
				newState = {
					...newState,
					extensions: [
						...action.extensions.map(
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
						...keyBy( action.extensions, 'product_slug' ),
					},
				},
			};
		case 'SET_EXTENSIONS_STATUS':
			const extensionsWithStatus = { ...state.entities.extensions };

			action.slugs.forEach( ( slug ) => {
				extensionsWithStatus[ slug ].status = action.status;
			} );

			return {
				...state,
				entities: {
					...state.entities,
					extensions: extensionsWithStatus,
				},
			};
		case 'SET_ERROR':
			return {
				...state,
				error: action.error,
			};
		default:
			return state;
	}
};

export const EXTENSIONS_STORE = 'sensei/extensions';

registerStore( EXTENSIONS_STORE, {
	reducer,
	actions,
	selectors,
	resolvers,
	controls,
} );
