/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LearnerCoursesSettings from './learner-courses-settings';

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	InspectorControls: ( { children } ) => children,
	BlockControls: ( { children } ) => children,
} ) );

describe( '<LearnerCoursesSettings />', () => {
	it( 'Should render the settings with the defined values', () => {
		const options = {
			courseDescriptionEnabled: true,
			featuredImageEnabled: false,
			courseCategoryEnabled: true,
			progressBarEnabled: true,
			layoutView: 'grid',
			columns: 2,
			progressBarHeight: 20,
			progressBarBorderRadius: 10,
		};
		const {
			queryByLabelText,
			queryAllByLabelText,
			queryByText,
			queryByTestId,
		} = render(
			<LearnerCoursesSettings
				options={ options }
				setOptions={ () => {} }
			/>
		);

		expect( queryByLabelText( 'Course description' ).checked ).toEqual(
			options.courseDescriptionEnabled
		);

		expect( queryByLabelText( 'Featured image' ).checked ).toEqual(
			options.featuredImageEnabled
		);

		expect( queryByLabelText( 'Course category' ).checked ).toEqual(
			options.courseCategoryEnabled
		);

		expect( queryByLabelText( 'Progress bar' ).checked ).toEqual(
			options.progressBarEnabled
		);

		expect( queryByTestId( 'list' ) ).not.toHaveClass( 'is-pressed' );
		expect( queryByTestId( 'grid' ) ).toHaveClass( 'is-pressed' );

		expect( queryByLabelText( 'Layout' ).value ).toEqual( '2' );

		// Open progress bar settings.
		fireEvent.click( queryByText( 'Progress bar settings' ) );

		expect( queryAllByLabelText( 'Height' )[ 0 ].value ).toEqual( '20' );

		expect( queryAllByLabelText( 'Border radius' )[ 0 ].value ).toEqual(
			'10'
		);
	} );

	it( 'Should call the setOptions correctly when changing the fields', () => {
		const setOptionsMock = jest.fn();

		const options = {
			courseDescriptionEnabled: false,
			featuredImageEnabled: false,
			courseCategoryEnabled: true,
			progressBarEnabled: true,
			layoutView: 'grid',
			columns: 2,
			progressBarHeight: 20,
			progressBarBorderRadius: 10,
		};

		const {
			queryByLabelText,
			queryAllByLabelText,
			queryByText,
			queryByTestId,
		} = render(
			<LearnerCoursesSettings
				options={ options }
				setOptions={ setOptionsMock }
			/>
		);

		fireEvent.click( queryByLabelText( 'Course description' ) );
		expect( setOptionsMock ).toBeCalledWith( {
			courseDescriptionEnabled: true,
		} );

		fireEvent.click( queryByLabelText( 'Featured image' ) );
		expect( setOptionsMock ).toBeCalledWith( {
			featuredImageEnabled: true,
		} );

		fireEvent.click( queryByLabelText( 'Course category' ) );
		expect( setOptionsMock ).toBeCalledWith( {
			courseCategoryEnabled: false,
		} );

		fireEvent.click( queryByLabelText( 'Progress bar' ) );
		expect( setOptionsMock ).toBeCalledWith( {
			progressBarEnabled: false,
		} );

		fireEvent.click( queryByTestId( 'list' ) );
		expect( setOptionsMock ).toBeCalledWith( {
			layoutView: 'list',
		} );

		fireEvent.click( queryByTestId( 'grid' ) );
		expect( setOptionsMock ).toBeCalledWith( {
			layoutView: 'grid',
		} );

		fireEvent.change( queryByLabelText( 'Layout' ), {
			target: { value: '3' },
		} );
		expect( setOptionsMock ).toBeCalledWith( {
			columns: '3',
		} );

		// Open progress bar settings.
		fireEvent.click( queryByText( 'Progress bar settings' ) );

		fireEvent.change( queryAllByLabelText( 'Height' )[ 0 ], {
			target: { value: '10', checkValidity: false },
		} );
		expect( setOptionsMock ).toBeCalledWith( {
			progressBarHeight: 10,
		} );

		fireEvent.change( queryAllByLabelText( 'Border radius' )[ 0 ], {
			target: { value: '5', checkValidity: false },
		} );
		expect( setOptionsMock ).toBeCalledWith( {
			progressBarBorderRadius: 5,
		} );
	} );

	it( 'Should not show layout setting when layout view is "list"', () => {
		const options = {
			layoutView: 'list',
		};

		const { queryByLabelText } = render(
			<LearnerCoursesSettings
				options={ options }
				setOptions={ () => {} }
			/>
		);

		expect( queryByLabelText( 'Layout' ) ).toBeFalsy();
	} );

	it( 'Should not show progress bar settings when progress bar is disabled', () => {
		const options = {
			progressBarEnabled: false,
		};

		const { queryByText } = render(
			<LearnerCoursesSettings
				options={ options }
				setOptions={ () => {} }
			/>
		);

		expect( queryByText( 'Progress bar settings' ) ).toBeFalsy();
	} );
} );
