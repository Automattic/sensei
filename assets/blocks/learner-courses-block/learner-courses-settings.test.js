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
			progressBarEnabled: false,
			layoutView: 'grid',
		};
		const { queryByLabelText, container } = render(
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

		const [ , listViewButton, gridViewButton ] = container.querySelectorAll(
			'button'
		);

		expect( listViewButton ).not.toHaveClass( 'is-pressed' );
		expect( gridViewButton ).toHaveClass( 'is-pressed' );
	} );

	it( 'Should call the setOptions correctly when changing the fields', () => {
		const setOptionsMock = jest.fn();

		const options = {
			courseDescriptionEnabled: false,
			featuredImageEnabled: false,
			courseCategoryEnabled: true,
			progressBarEnabled: true,
			layoutView: 'grid',
		};

		const { queryByLabelText, container } = render(
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

		const [ , listViewButton, gridViewButton ] = container.querySelectorAll(
			'button'
		);

		fireEvent.click( listViewButton );
		expect( setOptionsMock ).toBeCalledWith( {
			layoutView: 'list',
		} );

		fireEvent.click( gridViewButton );
		expect( setOptionsMock ).toBeCalledWith( {
			layoutView: 'grid',
		} );
	} );
} );
