/**
 * External dependencies
 */
import { render, fireEvent, screen } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ModuleEdit } from './module-edit';
jest.mock( '@wordpress/data' );

jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: () => null,
	InnerBlocks: () => null,
	RichText: ( { placeholder, onChange } ) => (
		<input
			placeholder={ placeholder }
			onChange={ ( { target: { value } } ) => {
				onChange( value );
			} }
		/>
	),
	withColors: () => ( Component ) => Component,
} ) );

jest.mock( '../../../shared/blocks/single-line-input', () => ( props ) => (
	<input
		{ ...props }
		onChange={ ( event ) => props.onChange( event.currentTarget.value ) }
	/>
) );

jest.mock( '../use-block-creator', () => jest.fn() );
jest.mock( '../../../shared/blocks/use-auto-inserter' );
jest.mock( '../outline-block/outline-edit', () => jest.fn() );
jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	useContext: () => ( {
		outlineAttributes: { collapsibleModules: true },
		outlineClassName: '',
	} ),
} ) );

describe( '<ModuleEdit />', () => {
	beforeEach( () => {
		useSelect.mockReturnValue( [ 'first-lesson', 'second-lesson' ] );
		useDispatch.mockReturnValue( { setModuleStatus: jest.fn() } );
	} );

	it( 'Should set the title attribute on changing the name input value', () => {
		const setAttributesMock = jest.fn();
		const { getByPlaceholderText } = render(
			<ModuleEdit
				className={ '' }
				attributes={ { title: '', description: '', lessons: [] } }
				setAttributes={ setAttributesMock }
			/>
		);

		fireEvent.change( getByPlaceholderText( 'Module name' ), {
			target: { value: 'Test' },
		} );

		expect( setAttributesMock ).toBeCalledWith( { title: 'Test' } );
	} );

	it( 'Should set the description attribute on changing the description input value', () => {
		const setAttributesMock = jest.fn();
		const { getByPlaceholderText } = render(
			<ModuleEdit
				className={ '' }
				attributes={ { title: '', description: '', lessons: [] } }
				setAttributes={ setAttributesMock }
			/>
		);

		fireEvent.change( getByPlaceholderText( 'Module description' ), {
			target: { value: 'Test' },
		} );

		expect( setAttributesMock ).toBeCalledWith( { description: 'Test' } );
	} );

	it( 'Should not display the teacher name section if no or empty name is provided', () => {
		render(
			<ModuleEdit
				className={ '' }
				attributes={ { title: '', description: '', lessons: [] } }
			/>
		);

		expect( screen.queryByText( '(', { exact: false } ) ).toBeFalsy();
	} );

	it( 'Should display the teacher name section in parentheses if name is provided', () => {
		render(
			<ModuleEdit
				className={ '' }
				attributes={ {
					title: '',
					description: '',
					lessons: [],
					teacher: 'teacher1',
				} }
			/>
		);

		expect( screen.getByText( '(', { exact: false } ).textContent ).toEqual(
			'(teacher1)'
		);
	} );
	it( 'Should show custom slug in header', () => {
		render(
			<ModuleEdit
				className={ '' }
				attributes={ {
					title: '',
					description: '',
					lessons: [],
					slug: 'custom-slug',
				} }
			/>
		);

		expect( screen.getByText( '(', { exact: false } ).textContent ).toEqual(
			'(custom-slug)'
		);
	} );

	it( 'Should have font size set for lesson sub header', () => {
		const { getByRole } = render(
			<ModuleEdit
				className={ '' }
				attributes={ {
					title: '',
					description: '',
					lessons: [],
					slug: 'custom-slug',
					lessonSubheaderFontSize: '5rem',
				} }
			/>
		);

		expect( getByRole( 'heading', { level: 3 } ) ).toHaveStyle(
			'font-size:5rem'
		);
	} );

	it( 'Should have default font size set for lesson sub header when custom value not set', () => {
		const { getByRole } = render(
			<ModuleEdit
				className={ '' }
				attributes={ {
					title: '',
					description: '',
					lessons: [],
					slug: 'custom-slug',
				} }
			/>
		);

		expect( getByRole( 'heading', { level: 3 } ) ).toHaveStyle(
			'font-size:1.17em'
		);
	} );
} );
