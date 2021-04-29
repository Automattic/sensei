/**
 * WordPress dependencies
 */
import { registerStore } from '@wordpress/data';
import { controls, apiFetch } from '@wordpress/data-controls';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Extension store actions.
 */
const actions = {
	/**
	 * Sets the extensions.
	 *
	 * @param {Array} response                 The response returned from the extensions endpoint.
	 * @param {Array} response.extensions      The extensions array.
	 * @param {Array} response.wccom_connected True if the site is connected to WC.com.
	 */
	setExtensions( { extensions, wccom_connected: wcccomConnected } ) {
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
		};
	},

	/**
	 * Updates the provided extensions.
	 *
	 * @param {Array}  extensions The extensions to update.
	 * @param {string} component  The component which started the update.
	 */
	*updateExtensions( extensions, component ) {
		const plugins = extensions.map(
			( extension ) => extension.product_slug
		);

		try {
			yield actions.setComponentInProgress( component );
			const response = yield apiFetch( {
				path: '/sensei-internal/v1/sensei-extensions/update',
				method: 'POST',
				data: { plugins },
			} );

			yield actions.setError( null );
			yield actions.setExtensions( response );
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
		} finally {
			yield actions.setComponentInProgress( '' );
		}
	},

	/**
	 * Update the component that caused an update.
	 *
	 * @param {string} componentInProgress The component.
	 */
	setComponentInProgress( componentInProgress ) {
		return {
			type: 'SET_COMPONENT_IN_PROGRESS',
			componentInProgress,
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
	getExtensions: ( { extensions } ) => extensions,
	getComponentInProgress: ( { componentInProgress } ) => componentInProgress,
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
 * Product store reducer.
 *
 * @param {Object} state  The store state.
 * @param {Object} action The action to handle.
 */
const reducer = (
	state = {
		extensions: [],
		componentInProgress: '',
		error: null,
	},
	action
) => {
	switch ( action.type ) {
		case 'SET_EXTENSIONS':
			return {
				...state,
				extensions: [ ...action.extensions ],
			};
		case 'SET_COMPONENT_IN_PROGRESS':
			return {
				...state,
				componentInProgress: action.componentInProgress,
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
