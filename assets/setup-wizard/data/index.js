/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import controls from './controls';
import * as resolvers from './resolvers';

const registerSetupWizardStore = () => {
	const store = createReduxStore( 'sensei/setup-wizard', {
		reducer,
		actions,
		selectors,
		controls,
		resolvers,
	} );

	register( store );
};

export default registerSetupWizardStore;
