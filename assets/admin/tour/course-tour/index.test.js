/**
 * Internal dependencies
 */
import { getFirstBlockByName } from '../../../blocks/course-outline/data';
import CourseTour from './index';
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

jest.mock( '../../../blocks/course-outline/data', () => ( {
	getFirstBlockByName: jest.fn(),
} ) );
jest.mock( '../components/sensei-tour-kit', () =>
	jest.fn().mockImplementation( ( props ) => {
		mockFunction( props );
		return <>Tour Kit Output</>;
	} )
);
jest.mock( '@wordpress/data' );

const mockFunction = jest.fn();

describe( 'CourseTour', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		select.mockReturnValue( {
			getBlocks: () => null,
		} );
	} );

	test( 'renders null when no outline block', () => {
		// Mocking getOutlineBlock to return null
		getFirstBlockByName.mockReturnValueOnce( null );

		const { queryByText } = render( <CourseTour /> );
		expect( queryByText( 'Tour Kit Output' ) ).toBeFalsy();
	} );

	test( 'renders SenseiTourKit when outline block exists', () => {
		// Mocking getOutlineBlock to return a block
		getFirstBlockByName.mockReturnValueOnce( true );

		const { getAllByText } = render( <CourseTour /> );
		expect( getAllByText( 'Tour Kit Output' ) ).toBeTruthy();
	} );
} );
