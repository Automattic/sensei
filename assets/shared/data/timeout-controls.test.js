import { registerStore } from '@wordpress/data';
import controls, { timeout } from './timeout-controls';

const delayedAction = () => ( { type: 'TEST_TIMEOUT_ACTION' } );

describe( 'timeout-controls', () => {
	let reducer, store;

	beforeEach( () => {
		reducer = jest.fn();
		store = registerStore( 'timeout-test', {
			reducer,
			controls,
		} );
		reducer.mockClear();
	} );

	it( 'runs action after timeout', async () => {
		jest.useFakeTimers();

		store.dispatch( timeout( delayedAction, 1000 ) );

		expect( reducer ).not.toHaveBeenCalled();

		await jest.runAllTimers();

		expect( reducer ).toHaveBeenCalledWith( undefined, {
			type: 'TEST_TIMEOUT_ACTION',
		} );
	} );
} );
