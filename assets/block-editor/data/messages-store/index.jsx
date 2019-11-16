/**
 * WordPress dependencies.
 */
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { MESSAGES_STORE } from './name';
import * as actions from './actions';
import reducer from './reducer';
import * as selectors from './selectors';
import * as resolvers from './resolvers';

// Register the Sensei Messages store.
registerStore( MESSAGES_STORE, {
	actions,
	reducer,
	selectors,
	resolvers,
} );

// Provide the name of the store.
export default MESSAGES_STORE;
