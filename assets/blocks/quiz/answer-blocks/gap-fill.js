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
 * @param {string}   props.attributes.before Text before the gap.
 * @param {string}   props.attributes.after  Text after the gap.
 * @param {string[]} props.attributes.gap    Right answers.
 * @param {Function} props.setAttributes
 * @param {boolean}  props.hasSelected       Is the question block selected.
 */
const GapFillAnswer = ( {
	attributes: { before, after, gap },
	setAttributes,
	hasSelected,
} ) => {
	return (
		<ul className="sensei-lms-question-block__answer sensei-lms-question-block__answer--gap-fill">
			<li>
				<RichText
					className="sensei-lms-question-block__answer--gap-fill__text"
					placeholder={ __( 'Text before the gap', 'sensei-lms' ) }
					value={ before }
					onChange={ ( nextValue ) =>
						setAttributes( { before: nextValue } )
					}
				/>
			</li>
			<li className="sensei-lms-question-block__answer--gap-fill__right-answers">
				<FormTokenField
					className={
						'sensei-lms-question-block__text-input-placeholder'
					}
					value={ gap || [] }
					label={ false }
					onChange={ ( nextValue ) =>
						setAttributes( { gap: nextValue } )
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
					className="sensei-lms-question-block__answer--gap-fill__text"
					placeholder={ __( 'Text after the gap', 'sensei-lms' ) }
					value={ after }
					onChange={ ( nextValue ) =>
						setAttributes( { after: nextValue } )
					}
				/>
			</li>
		</ul>
	);
};

export default GapFillAnswer;
