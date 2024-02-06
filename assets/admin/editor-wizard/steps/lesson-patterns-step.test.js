/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { when } from 'jest-when';
import { render } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { SlotFillProvider } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import LessonPatternsStep from './lesson-patterns-step';
import PatternsStep from './patterns-step';

jest.mock( '@wordpress/data' );
jest.mock( './patterns-step' );

const mockFunction = jest.fn();

describe( '<LessonPatternsStep />', () => {
	beforeAll( () => {
		jest.clearAllMocks();
		when( PatternsStep ).mockImplementation( ( props ) => {
			mockFunction( props );
			return (
				<div>
					<h1>Tailored Course Outline</h1>
				</div>
			);
		} );
	} );

	it( 'Should set pattern replacer value as expected when property is present.', () => {
		const lessonContent = 'Some lesson content.';
		useSelect.mockReturnValue( {
			senseiProExtension: jest.fn(),
		} );

		PatternsStep.UpsellFill.mockReturnValue( <div>Upsell Fill</div> );

		const expectedOutput = {
			replaces: {
				'sensei-content-description': lessonContent,
			},
			title: 'Lesson Layout',
		};

		render(
			<SlotFillProvider>
				<LessonPatternsStep
					wizardData={ { description: lessonContent } }
				/>
			</SlotFillProvider>
		);

		expect( mockFunction ).toBeCalledWith( expectedOutput );
	} );
} );
