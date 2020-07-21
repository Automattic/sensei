import { ExportJobState } from './export-job-state';
import apiFetch from '@wordpress/api-fetch';

function flushPromises() {
	return new Promise( ( resolve ) => setImmediate( resolve ) );
}

function nextResponse( value ) {
	apiFetch.mockReturnValueOnce( Promise.resolve( value ) );
}

jest.mock( '@wordpress/api-fetch' );

describe( 'ExportJobState', () => {
	let updateState;
	beforeEach( () => ( updateState = jest.fn() ) );

	describe( 'creation', () => {
		it( 'checks for active job', () => {
			nextResponse( { id: null } );
			ExportJobState( updateState );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: '/sensei-internal/v1/export/active',
			} );
			expect( updateState ).toHaveBeenLastCalledWith( null );
		} );

		it( 'sets state from active job', async () => {
			nextResponse( { id: 5, status: { status: 'completed' } } );

			ExportJobState( updateState );

			await flushPromises();

			expect( updateState ).toHaveBeenLastCalledWith( {
				id: 5,
				status: 'completed',
			} );
		} );
	} );

	describe( 'instance', () => {
		let exportJobState;

		const startJob = () => {
			nextResponse( { id: 1, status: { status: 'created' } } );
			nextResponse( {
				id: 1,
				status: { status: 'completed' },
			} );

			return exportJobState.start( [ 'lesson', 'course' ] );
		};

		beforeEach( async () => {
			nextResponse( { id: null } );
			exportJobState = ExportJobState( updateState );
			await flushPromises();
			apiFetch.mockClear();
		} );

		it( 'creates and starts a job', async () => {
			await startJob();

			expect( apiFetch ).toHaveBeenNthCalledWith( 1, {
				path: '/sensei-internal/v1/export',
				method: 'POST',
			} );

			expect( apiFetch ).toHaveBeenNthCalledWith( 2, {
				path: '/sensei-internal/v1/export/1/start',
				method: 'POST',
				data: { content_types: [ 'lesson', 'course' ] },
			} );
		} );

		it( 'deletes job', async () => {
			await startJob();
			apiFetch.mockClear();

			await exportJobState.cancel();

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: '/sensei-internal/v1/export/1',
				method: 'DELETE',
			} );
		} );

		it( 'polls for updates', async () => {
			jest.useFakeTimers();

			nextResponse( {
				id: 1,
				status: { status: 'setup', percentage: 0 },
			} );
			nextResponse( {
				id: 1,
				status: { status: 'pending', percentage: 0 },
			} );

			await exportJobState.start( [ 'lesson' ] );

			apiFetch.mockClear();

			nextResponse( {
				id: 1,
				status: { status: 'pending', percentage: 50 },
			} );
			nextResponse( {
				id: 1,
				status: { status: 'completed', percentage: 100 },
			} );

			jest.runOnlyPendingTimers();
			await flushPromises();

			expect( apiFetch ).toHaveBeenNthCalledWith( 1, {
				path: '/sensei-internal/v1/export/1',
			} );

			expect( updateState ).toHaveBeenLastCalledWith( {
				status: 'pending',
				percentage: 50,
				id: 1,
			} );

			jest.runOnlyPendingTimers();
			await flushPromises();

			expect( updateState ).toHaveBeenLastCalledWith( {
				status: 'completed',
				percentage: 100,
				id: 1,
			} );

			jest.runOnlyPendingTimers();
			await flushPromises();

			expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		} );
	} );
} );
