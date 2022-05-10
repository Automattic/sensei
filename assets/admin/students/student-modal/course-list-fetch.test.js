/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { CourseList } from './course-list';
import httpClient from '../../lib/http-client';

jest.mock( '../../lib/http-client' );

describe( 'CourseList fetch', () => {
	beforeEach( () => {
		httpClient.mockImplementation( () => Promise.resolve( [] ) );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'Should append a search param to the URL', async () => {
		render( <CourseList searchQuery="abc123" /> );

		await waitFor(
			() =>
				expect( httpClient ).toHaveBeenCalledWith( {
					path: '/wp/v2/courses?per_page=100&search=abc123',
					method: 'GET',
					signal: new AbortController().signal,
				} ),
			{ timeout: 450 }
		);
	} );

	it( 'Should NOT append a search param to the URL', async () => {
		render( <CourseList searchQuery="" /> );

		await waitFor(
			() =>
				expect( httpClient ).toHaveBeenCalledWith( {
					path: '/wp/v2/courses?per_page=100',
					method: 'GET',
					signal: new AbortController().signal,
				} ),
			{ timeout: 450 }
		);
	} );
} );
