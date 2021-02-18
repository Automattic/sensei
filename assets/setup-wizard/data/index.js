/**
 * WordPress dependencies
 */
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import controls from './controls';
import * as resolvers from './resolvers';

const registerSetupWizardStore = () => {
	registerStore( 'sensei/setup-wizard', {
		reducer,
		actions,
		selectors,
		controls,
		resolvers,
	} );
};

export default registerSetupWizardStore;
