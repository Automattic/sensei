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
	 * @param {Array} extensions The extensions to update.
	 */
	*updateExtensions( extensions ) {
		const plugins = extensions.map(
			( extension ) => extension.product_slug
		);

		try {
			yield actions.setOperationInProgress( true );
			const newExtensions = yield apiFetch( {
				path: '/sensei-internal/v1/sensei-extensions/update',
				method: 'POST',
				data: { plugins },
			} );

			yield actions.setExtensions( newExtensions );
			return actions.setOperationInProgress( false );
		} catch ( error ) {
			// TODO: Handle error
		}
	},

	/**
	 * Mark that an operation is being done.
	 *
	 * @param {boolean} operationInProgress True if an operation is in progress.
	 */
	setOperationInProgress( operationInProgress ) {
		return {
			type: 'SET_OPERATION_IN_PROGRESS',
			operationInProgress,
		};
	},

	/**
	 * Mark that notification update button has been clicked.
	 *
	 * @param {boolean} notificationUpdated True if the button was clicked.
	 */
	setNotificationUpdated( notificationUpdated ) {
		return {
			type: 'SET_NOTIFICATION_UPDATED',
			notificationUpdated,
		};
	},
};

/**
 * Extension store selectors.
 */
const selectors = {
	getExtensions: ( { extensions } ) => extensions,
	getOperationInProgress: ( { operationInProgress } ) => operationInProgress,
	getNotificationUpdated: ( { notificationUpdated } ) => notificationUpdated,
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
		operationInProgress: false,
		notificationUpdated: false,
	},
	action
) => {
	switch ( action.type ) {
		case 'SET_EXTENSIONS':
			return {
				...state,
				extensions: [ ...action.extensions ],
			};
		case 'SET_OPERATION_IN_PROGRESS':
			return {
				...state,
				operationInProgress: action.operationInProgress,
			};
		case 'SET_NOTIFICATION_UPDATED':
			return {
				...state,
				notificationUpdated: action.notificationUpdated,
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
