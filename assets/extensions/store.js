/**
 * WordPress dependencies
 */
import { registerStore } from '@wordpress/data';
import { controls, apiFetch } from '@wordpress/data-controls';

/**
 * Extension store actions.
 */
const actions = {
	/**
	 * Sets the extensions.
	 *
	 * @param {Array} extensions The new extensions.
	 */
	setExtensions( extensions ) {
		const enrichedExtensions = extensions.map( ( extension ) => {
			let canUpdate = false;
			// If the extension is hosted in WC.com, check that the site is connected and the subscription is not expired.
			if ( extension.has_update ) {
				canUpdate =
					! extension.wccom_product_id ||
					( extension.wccom_connected && ! extension.wccom_expired );
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
			const newExtensions = yield apiFetch( {
				path: '/sensei-internal/v1/sensei-extensions/update',
				method: 'POST',
				data: { plugins },
			} );

			yield actions.setExtensions( newExtensions );
			return actions.setComponentInProgress( '' );
		} catch ( error ) {
			// TODO: Handle error
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
};

/**
 * Extension store selectors.
 */
const selectors = {
	getExtensions: ( { extensions } ) => extensions,
	getComponentInProgress: ( { componentInProgress } ) => componentInProgress,
};

/**
 * Extension store resolvers.
 */
const resolvers = {
	/**
	 * Loads the extensions during initialization.
	 */
	*getExtensions() {
		let extensions = [];

		try {
			extensions = yield apiFetch( {
				path: '/sensei-internal/v1/sensei-extensions?type=plugin',
			} );
		} catch ( error ) {
			//TODO: Handle error
		}

		return actions.setExtensions( extensions );
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
