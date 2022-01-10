/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import QuizSettings from './quiz-settings';

const mockSetAttributes = jest.fn();
const mockOpenGeneralSidebar = jest.fn();

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	InspectorControls: ( { children } ) => children,
	PanelColorSettings: () => null,
} ) );

jest.mock( '@wordpress/data', () => {
	const module = jest.requireActual( '@wordpress/data' );

	return {
		combineReducers: module.combineReducers,
		registerStore: module.registerStore,
		useDispatch: () => {
			return {
				openGeneralSidebar: mockOpenGeneralSidebar,
				selectBlock: jest.fn(),
			};
		},
		useSelect: () => [
			{
				attributes: {
					title: 'Question 1',
					type: 'multiple-choice',
				},
			},
			{
				attributes: {
					title: 'Question 2',
					type: 'multiple-choice',
				},
			},
			{
				attributes: {
					type: 'category-question',
					options: {
						number: 2,
					},
				},
			},
			{
				attributes: {
					type: 'category-question',
					options: {
						number: 3,
					},
				},
			},
		],
	};
} );

describe( '<QuizSettings />', () => {
	it( 'Should render the settings with the defined values', () => {
		const { queryByLabelText, queryAllByLabelText } = render(
			<QuizSettings
				attributes={ {
					options: {
						passRequired: true,
						quizPassmark: 50,
						autoGrade: false,
						allowRetakes: false,
						randomQuestionOrder: true,
						showQuestions: 5,
					},
				} }
				setAttributes={ mockSetAttributes }
			/>
		);

		expect( queryByLabelText( 'Pass Required' ).checked ).toEqual( true );
		expect( queryAllByLabelText( 'Passing Grade (%)' )[ 0 ].value ).toEqual(
			'50'
		);
		expect( queryByLabelText( 'Auto Grade' ).checked ).toEqual( false );
		expect( queryByLabelText( 'Allow Retakes' ).checked ).toEqual( false );
		expect( queryByLabelText( 'Random Question Order' ).checked ).toEqual(
			true
		);
		expect( queryByLabelText( 'Number of Questions' ).value ).toEqual(
			'5'
		);
	} );

	it( 'Should not render the Passing Grade field when Pass Required is false', () => {
		const { queryByLabelText } = render(
			<QuizSettings
				attributes={ {
					options: {
						passRequired: false,
					},
				} }
			/>
		);

		expect( queryByLabelText( 'Passing Grade (%)' ) ).toBeFalsy();
	} );

	it( 'Should have the maximum number of questions defined by the the number of questions added to the quiz', () => {
		const { queryByLabelText } = render(
			<QuizSettings
				attributes={ {
					options: {},
				} }
			/>
		);

		expect( queryByLabelText( 'Number of Questions' ).max ).toEqual( '7' );
	} );

	it( 'Should call the setAttributes correctly when changing the fields', () => {
		const defaultOptions = {
			passRequired: true,
			quizPassmark: 0,
			autoGrade: true,
			allowRetakes: true,
			randomQuestionOrder: true,
			showQuestions: 0,
		};

		const { queryByLabelText, queryAllByLabelText } = render(
			<QuizSettings
				attributes={ { options: defaultOptions } }
				setAttributes={ mockSetAttributes }
			/>
		);

		fireEvent.click( queryByLabelText( 'Pass Required' ) );
		expect( mockSetAttributes ).toBeCalledWith( {
			options: {
				...defaultOptions,
				passRequired: false,
			},
		} );

		fireEvent.change( queryAllByLabelText( 'Passing Grade (%)' )[ 0 ], {
			target: { value: '50' },
		} );
		expect( mockSetAttributes ).toBeCalledWith( {
			options: {
				...defaultOptions,
				quizPassmark: 50,
			},
		} );

		fireEvent.click( queryByLabelText( 'Auto Grade' ) );
		expect( mockSetAttributes ).toBeCalledWith( {
			options: {
				...defaultOptions,
				passRequired: false,
			},
		} );

		fireEvent.click( queryByLabelText( 'Allow Retakes' ) );
		expect( mockSetAttributes ).toBeCalledWith( {
			options: {
				...defaultOptions,
				allowRetakes: false,
			},
		} );

		fireEvent.click( queryByLabelText( 'Random Question Order' ) );
		expect( mockSetAttributes ).toBeCalledWith( {
			options: {
				...defaultOptions,
				randomQuestionOrder: false,
			},
		} );

		fireEvent.change( queryByLabelText( 'Number of Questions' ), {
			target: { value: '10' },
		} );
		expect( mockSetAttributes ).toBeCalledWith( {
			options: {
				...defaultOptions,
				showQuestions: 10,
			},
		} );
	} );

	it( 'Should open the siderbar when the quiz settings button is clicked', () => {
		const { getAllByRole } = render(
			<QuizSettings
				attributes={ {
					options: {},
				} }
			/>
		);

		fireEvent.click(
			getAllByRole( 'button', { name: 'Quiz settings' } )[ 0 ]
		);

		expect( mockOpenGeneralSidebar ).toBeCalledTimes( 1 );
	} );
} );
