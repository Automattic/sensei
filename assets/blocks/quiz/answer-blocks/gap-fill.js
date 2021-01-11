import { RichText } from '@wordpress/block-editor';
import { FormTokenField } from '@wordpress/components';

export const GapFillAnswer = ( {
	attributes: { before, after, rightAnswers },
	setAttributes,
	hasSelected,
} ) => {
	return (
		<div className="sensei-lms-question-block__answer sensei-lms-question-block__gapfill">
			<RichText
				placeholder={ 'Text before the gap' }
				value={ before }
				onChange={ ( nextValue ) =>
					setAttributes( { before: nextValue } )
				}
			/>
			<FormTokenField
				className={
					'sensei-lms-question-block__text-input-placeholder'
				}
				value={ rightAnswers }
				label={ false }
				onChange={ ( nextValue ) =>
					setAttributes( { rightAnswers: nextValue } )
				}
				placeholder={ 'Add correct answers' }
			/>
			<RichText
				placeholder={ 'Text after the gap' }
				value={ after }
				onChange={ ( nextValue ) =>
					setAttributes( { after: nextValue } )
				}
			/>
		</div>
	);
};
