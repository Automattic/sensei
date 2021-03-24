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
		const attributes = {
			courseDescriptionEnabled: true,
			featuredImageEnabled: false,
			courseCategoryEnabled: true,
			progressBarEnabled: false,
			layoutView: 'grid',
		};
		const { queryByLabelText, container } = render(
			<LearnerCoursesSettings
				attributes={ attributes }
				setAttributes={ () => {} }
			/>
		);

		expect( queryByLabelText( 'Course description' ).checked ).toEqual(
			attributes.courseDescriptionEnabled
		);

		expect( queryByLabelText( 'Featured image' ).checked ).toEqual(
			attributes.featuredImageEnabled
		);

		expect( queryByLabelText( 'Course category' ).checked ).toEqual(
			attributes.courseCategoryEnabled
		);

		expect( queryByLabelText( 'Progress bar' ).checked ).toEqual(
			attributes.progressBarEnabled
		);

		const [ , listViewButton, gridViewButton ] = container.querySelectorAll(
			'button'
		);

		expect( listViewButton ).not.toHaveClass( 'is-pressed' );
		expect( gridViewButton ).toHaveClass( 'is-pressed' );
	} );

	it( 'Should call the setAttributes correctly when changing the fields', () => {
		const setAttributesMock = jest.fn();

		const attributes = {
			courseDescriptionEnabled: false,
			featuredImageEnabled: false,
			courseCategoryEnabled: true,
			progressBarEnabled: true,
			layoutView: 'grid',
		};

		const { queryByLabelText, container } = render(
			<LearnerCoursesSettings
				attributes={ attributes }
				setAttributes={ setAttributesMock }
			/>
		);

		fireEvent.click( queryByLabelText( 'Course description' ) );
		expect( setAttributesMock ).toBeCalledWith( {
			courseDescriptionEnabled: true,
		} );

		fireEvent.click( queryByLabelText( 'Featured image' ) );
		expect( setAttributesMock ).toBeCalledWith( {
			featuredImageEnabled: true,
		} );

		fireEvent.click( queryByLabelText( 'Course category' ) );
		expect( setAttributesMock ).toBeCalledWith( {
			courseCategoryEnabled: false,
		} );

		fireEvent.click( queryByLabelText( 'Progress bar' ) );
		expect( setAttributesMock ).toBeCalledWith( {
			progressBarEnabled: false,
		} );

		const [ , listViewButton, gridViewButton ] = container.querySelectorAll(
			'button'
		);

		fireEvent.click( listViewButton );
		expect( setAttributesMock ).toBeCalledWith( {
			layoutView: 'list',
		} );

		fireEvent.click( gridViewButton );
		expect( setAttributesMock ).toBeCalledWith( {
			layoutView: 'grid',
		} );
	} );
} );
