import { registerStore } from '@wordpress/data';
import controls, { schedule } from './schedule-controls';

const delayedAction = () => ( { type: 'TEST_SCHEDULED_ACTION' } );

describe( 'schedule-controls', () => {
	let reducer, store;

	beforeEach( () => {
		reducer = jest.fn();
		store = registerStore( 'schedule-test', {
			reducer,
			controls,
		} );
		reducer.mockClear();
	} );

	it( 'runs action after timeout', async () => {
		jest.useFakeTimers();

		store.dispatch( schedule( delayedAction, 1000 ) );

		expect( reducer ).not.toHaveBeenCalled();

		await jest.runAllTimers();

		expect( reducer ).toHaveBeenCalledWith( undefined, {
			type: 'TEST_SCHEDULED_ACTION',
		} );
	} );
} );
