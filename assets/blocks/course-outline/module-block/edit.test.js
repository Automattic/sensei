import { render, fireEvent } from '@testing-library/react';
import { EditModuleBlock } from './edit';
import { useSelect, useDispatch } from '@wordpress/data';

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
jest.mock( './use-insert-lesson-block' );
jest.mock( '../course-block/edit', () => jest.fn() );
jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	useContext: () => ( { outlineAttributes: { collapsibleModules: true } } ),
} ) );

describe( '<EditModuleBlock />', () => {
	beforeAll( () => {
		useSelect.mockReturnValue( [ 'first-lesson', 'second-lesson' ] );
		useDispatch.mockReturnValue( { setModuleStatus: jest.fn() } );
	} );

	it( 'Should set the title attribute on changing the name input value', () => {
		const setAttributesMock = jest.fn();
		const { getByPlaceholderText } = render(
			<EditModuleBlock
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
			<EditModuleBlock
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
} );
