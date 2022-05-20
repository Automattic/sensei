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

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
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
} ) );
jest.mock( '@wordpress/data' );
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
	beforeAll( () => {
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
} );
