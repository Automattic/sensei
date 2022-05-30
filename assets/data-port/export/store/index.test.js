/**
 * WordPress dependencies
 */
import { resolveSelect, select } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import registerExportStore, { EXPORT_STORE } from './index';
import * as actions from './actions';
/**
 * External dependencies
 */
import { waitFor } from '@testing-library/dom';

window.sensei_log_event = jest.fn();
jest.mock( '@wordpress/data-controls', () => {
	const originalModule = jest.requireActual( '@wordpress/data-controls' );

	return {
		__esModule: true,
		...originalModule,
		apiFetch: jest.fn(),
	};
} );

async function mockActiveJob( job ) {
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

	it( 'resolves active job', async () => {
		apiFetch.mockReturnValue( {
			id: 5,
			status: { status: 'completed' },
		} );

		const job = await resolveSelect( EXPORT_STORE ).getJob();

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
		jest.useFakeTimers();

		mockActiveJob( { id: 4, status: { status: 'complete' } } );
		apiFetch.mockReturnValue( {
			id: 4,
			deleted: true,
			status: { status: 'setup' },
		} );

		store.dispatch( actions.cancel() );
		await jest.runOnlyPendingTimers();

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/sensei-internal/v1/export/4',
			method: 'DELETE',
		} );

		expect( store.getState() ).toEqual( {} );
	} );

	it( 'polls for updates if job is pending', async () => {
		jest.useFakeTimers();
		const mockStatus = ( status, percentage ) => ( {
			id: 7,
			status: { status, percentage },
		} );

		mockActiveJob( {
			id: 7,
			status: { status: 'pending', percentage: 0 },
		} );

		apiFetch
			.mockReturnValueOnce( mockStatus( 'pending', 20 ) )
			.mockReturnValueOnce( mockStatus( 'pending', 50 ) )
			.mockReturnValueOnce( mockStatus( 'complete', 100 ) );

		await jest.runOnlyPendingTimers();
		await jest.runOnlyPendingTimers();
		await jest.runOnlyPendingTimers();

		const pollRequest = {
			path: '/sensei-internal/v1/export/7/process',
			method: 'POST',
		};

		await waitFor( async () => {
			expect( apiFetch ).toHaveBeenCalledWith( pollRequest );
			expect( select( EXPORT_STORE ).getJob() ).toEqual( {
				id: 7,
				status: 'complete',
				percentage: 100,
			} );
		} );
	} );

	it( 'gets logs after job is completed', async () => {
		jest.useFakeTimers();

		// store = registerExportStore();

		mockActiveJob( {
			id: 5,
			status: { status: 'pending', percentage: 0 },
		} );

		await jest.runOnlyPendingTimers();

		apiFetch
			.mockReturnValueOnce( {
				id: 5,
				status: { status: 'completed', percentage: 100 },
			} )
			.mockReturnValueOnce( {
				items: [
					{
						message: 'Error 1.',
					},
					{
						message: 'Error 2.',
					},
				],
			} );

		await jest.runOnlyPendingTimers();

		expect( select( EXPORT_STORE ).getError() ).toEqual(
			'Error 1. Error 2.'
		);
	} );
} );
