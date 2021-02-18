/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
import { FormTokenField } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Question block gap fill answer component.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {string}   props.attributes.textBefore   Text before the gap.
 * @param {string}   props.attributes.textAfter    Text after the gap.
 * @param {string[]} props.attributes.rightAnswers Right answers.
 * @param {Function} props.setAttributes
 * @param {boolean}  props.hasSelected             Is the question block selected.
 */
const GapFillAnswer = ( {
	attributes: { textBefore, textAfter, rightAnswers },
	setAttributes,
	hasSelected,
} ) => {
	return (
		<ul className="sensei-lms-question-block__answer sensei-lms-question-block__answer--gap-fill">
			<li>
				<RichText
					placeholder={ __( 'Text before the gap', 'sensei-lms' ) }
					value={ textBefore }
					onChange={ ( nextValue ) =>
						setAttributes( { textBefore: nextValue } )
					}
				/>
			</li>
			<li className="sensei-lms-question-block__answer--gap-fill__right-answers">
				<FormTokenField
					className={
						'sensei-lms-question-block__text-input-placeholder'
					}
					value={ rightAnswers }
					label={ false }
					onChange={ ( nextValue ) =>
						setAttributes( { rightAnswers: nextValue } )
					}
				/>
				{ hasSelected && (
					<div className="sensei-lms-question-block__answer--gap-fill__hint">
						{ __(
							'Add right answers. Separate with commas or the Enter key.',
							'sensei-lms'
						) }
					</div>
				) }
			</li>
			<li>
				<RichText
					placeholder={ __( 'Text after the gap', 'sensei-lms' ) }
					value={ textAfter }
					onChange={ ( nextValue ) =>
						setAttributes( { textAfter: nextValue } )
					}
				/>
			</li>
		</ul>
	);
};

export default GapFillAnswer;
