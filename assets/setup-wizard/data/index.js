/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';

export const store = createReduxStore( 'sensei/setup-wizard', {
	reducer,
	actions,
	selectors,
	controls,
} );

const registerSetupWizardStore = () => {
	register( store );
};

export default registerSetupWizardStore;
