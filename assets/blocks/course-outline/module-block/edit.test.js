import { render, fireEvent } from '@testing-library/react';

import EditModuleBlock from './edit';

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
} ) );
jest.mock( '../use-block-creator', () => jest.fn() );

describe( '<EditLessonBlock />', () => {
	it( 'Should set the title attribute on changing the name input value', () => {
		const setAttributesMock = jest.fn();
		const { getByPlaceholderText } = render(
			<EditModuleBlock
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
