import { select } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import registerExportStore, { EXPORT_STORE } from './index';
import * as actions from './actions';

window.sensei_log_event = jest.fn();
jest.mock( '@wordpress/data-controls', () => {
	const originalModule = jest.requireActual( '@wordpress/data-controls' );

	return {
		__esModule: true,
		...originalModule,
		apiFetch: jest.fn(),
	};
} );

function mockActiveJob( job ) {
	apiFetch.mockImplementation( () => {
		if ( ! job )
			throw {
				code: 'sensei_data_port_job_not_found',
				message: 'Not found',
			};
		else return job;
	} );
	select( EXPORT_STORE ).getJob();
	apiFetch.mockClear();
}

describe( 'Export store', () => {
	let store;
	beforeEach( () => {
		store = registerExportStore();
		apiFetch.mockClear();
	} );

	it( 'resolves active job', () => {
		apiFetch.mockReturnValue( {
			id: 5,
			status: { status: 'completed' },
		} );

		const job = select( EXPORT_STORE ).getJob();

		expect( job ).toEqual( {
			id: 5,
			status: 'completed',
		} );
	} );

	it( 'handles request error', () => {
		apiFetch.mockImplementation( () => {
			throw {
				code: 'sensei_data_port_job_error',
				message: 'Request error',
			};
		} );

		store.dispatch( actions.checkForActiveJob() );

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/sensei-internal/v1/export/active',
		} );

		expect( store.getState() ).toEqual( {
			error: 'Request error',
		} );
	} );

	it( 'handles no active job', () => {
		apiFetch.mockImplementation( () => {
			throw {
				code: 'sensei_data_port_job_not_found',
				message: 'Not found',
			};
		} );

		store.dispatch( actions.checkForActiveJob() );

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/sensei-internal/v1/export/active',
		} );

		expect( store.getState() ).toEqual( {} );
	} );

	it( 'cancels active job in setup phase', () => {
		apiFetch
			.mockReturnValueOnce( {
				id: 5,
				status: { status: 'setup' },
			} )
			.mockReturnValueOnce( {
				id: 5,
				deleted: true,
			} );

		store.dispatch( actions.checkForActiveJob() );

		expect( apiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/sensei-internal/v1/export/5',
			method: 'DELETE',
		} );

		expect( store.getState() ).toEqual( {} );
	} );

	it( 'creates and starts a job', () => {
		mockActiveJob( null );
		apiFetch
			.mockReturnValueOnce( { id: 5, status: { status: 'setup' } } )
			.mockReturnValueOnce( { id: 5, status: { status: 'complete' } } );

		store.dispatch( actions.start( [ 'lesson', 'course' ] ) );

		expect( apiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/sensei-internal/v1/export',
			method: 'POST',
		} );

		expect( apiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/sensei-internal/v1/export/5/start',
			method: 'POST',
			data: { content_types: [ 'lesson', 'course' ] },
		} );

		expect( select( EXPORT_STORE ).getJob() ).toEqual( {
			id: 5,
			status: 'complete',
		} );
	} );

	it( 'deletes job', async () => {
		mockActiveJob( { id: 4, status: { status: 'complete' } } );
		apiFetch.mockReturnValue( { id: 4, deleted: true } );
		store.dispatch( actions.cancel() );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/sensei-internal/v1/export/4',
			method: 'DELETE',
		} );
		expect( store.getState() ).toEqual( {} );
	} );

	it( 'polls for updates if job is pending', async () => {
		jest.useFakeTimers();
		const mockStatus = ( status, percentage ) => ( {
			id: 3,
			status: { status, percentage },
		} );

		mockActiveJob( {
			id: 3,
			status: { status: 'pending', percentage: 0 },
		} );

		apiFetch
			.mockReturnValueOnce( mockStatus( 'pending', 20 ) )
			.mockReturnValueOnce( mockStatus( 'pending', 50 ) )
			.mockReturnValueOnce( mockStatus( 'complete', 100 ) );

		await jest.runOnlyPendingTimers();

		const pollRequest = {
			path: '/sensei-internal/v1/export/3/process',
			method: 'POST',
		};

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenLastCalledWith( pollRequest );
		expect( select( EXPORT_STORE ).getJob().percentage ).toEqual( 20 );

		await jest.runOnlyPendingTimers();

		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		expect( apiFetch ).toHaveBeenLastCalledWith( pollRequest );
		expect( select( EXPORT_STORE ).getJob().percentage ).toEqual( 50 );

		await jest.runOnlyPendingTimers();

		expect( apiFetch ).toHaveBeenCalledTimes( 3 );
		expect( apiFetch ).toHaveBeenLastCalledWith( pollRequest );
		expect( select( EXPORT_STORE ).getJob() ).toEqual( {
			id: 3,
			status: 'complete',
			percentage: 100,
		} );

		await jest.runOnlyPendingTimers();

		expect( apiFetch ).toHaveBeenCalledTimes( 3 );
		expect( select( EXPORT_STORE ).getJob() ).toEqual( {
			id: 3,
			status: 'complete',
			percentage: 100,
		} );
	} );
} );
