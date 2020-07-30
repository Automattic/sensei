import { registerStore } from '@wordpress/data';
import controls, { schedule, clearSchedule } from './schedule';

const delayedAction = () => ( { type: 'TEST_SCHEDULED_ACTION' } );

describe( 'schedule', () => {
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

	it( 'cancels schedules action', async () => {
		jest.useFakeTimers();
		store.dispatch( schedule( delayedAction, 1000 ) );

		store.dispatch( clearSchedule() );

		await jest.advanceTimersByTime( 500 );
		await jest.runAllTimers();

		expect( reducer ).not.toHaveBeenCalled();
	} );
} );
