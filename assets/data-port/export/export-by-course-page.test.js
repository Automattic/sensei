/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { ExportByCoursePage } from './export-by-course-page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( '<ExportByCoursePage />', () => {
	beforeEach( () => {
		apiFetch.mockReset();
		apiFetch.mockResolvedValue( [] );
	} );

	it( 'submits with mode by_course and empty selection by default', async () => {
		const onSubmit = jest.fn();
		const { getByRole } = render(
			<ExportByCoursePage onSubmit={ onSubmit } onBack={ () => {} } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		fireEvent.click( getByRole( 'button', { name: 'Start Export' } ) );

		expect( onSubmit ).toHaveBeenCalledWith( {
			types: [ 'course', 'lesson', 'question' ],
			selections: { course: [] },
			mode: 'by_course',
		} );
	} );

	it( 'fetches courses from /wp/v2/courses', async () => {
		render(
			<ExportByCoursePage onSubmit={ () => {} } onBack={ () => {} } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		const path = apiFetch.mock.calls[ 0 ][ 0 ].path;
		expect( path ).toContain( '/wp/v2/courses' );
	} );

	it( 'invokes onBack when Back is clicked', async () => {
		const onBack = jest.fn();
		const { getByRole } = render(
			<ExportByCoursePage onSubmit={ () => {} } onBack={ onBack } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		fireEvent.click( getByRole( 'button', { name: 'Back' } ) );

		expect( onBack ).toHaveBeenCalled();
	} );
} );
