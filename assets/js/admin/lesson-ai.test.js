import { render } from '@testing-library/react';
import { addQuestionGeneratorUpsellButtonToQuizBlock } from './lesson-ai';
import { Slot, SlotFillProvider } from '@wordpress/components';

describe( 'addQuestionGeneratorUpsellButtonToQuizBlock', () => {
	const settings = {
		attributes: {},
		edit: () => (
			<div>
				<Slot name="SenseiQuizHeader" />
				This is a quiz block
			</div>
		),
	};

	it( 'Should render the upsell button for quiz block', async () => {
		const { edit: Edit } = addQuestionGeneratorUpsellButtonToQuizBlock( {
			...settings,
			name: 'sensei-lms/quiz',
		} );

		const { getByText } = render(
			<SlotFillProvider>
				<Edit />
			</SlotFillProvider>
		);

		expect(
			getByText( 'Generate quiz questions with AI' )
		).toBeInTheDocument();
	} );
} );
