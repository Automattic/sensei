/**
 * WordPress dependencies
 */
import { FormTokenField } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SingleLineInput from '../../../shared/blocks/single-line-input/single-line-input';

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
	const addIncompleteToken = ( { target } ) => {
		if ( ! target?.value ) {
			return;
		}
		setAttributes( { gap: [ ...( gap ?? [] ), target.value ] } );
		setInputValue( target, '' );
	};

	return (
		<ul className="sensei-lms-question-block__answer sensei-lms-question-block__answer--gap-fill">
			<li>
				<SingleLineInput
					className="sensei-lms-question-block__answer--gap-fill__text"
					placeholder={ __( 'Text before the gap', 'sensei-lms' ) }
					value={ before }
					onChange={ ( nextValue ) =>
						setAttributes( { before: nextValue } )
					}
				/>
			</li>
			<li
				className="sensei-lms-question-block__answer--gap-fill__right-answers"
				onBlur={ addIncompleteToken }
			>
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
				<SingleLineInput
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

/**
 * Read-only answer component gap fill question block.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {string}   props.attributes.before Text before the gap.
 * @param {string}   props.attributes.after  Text after the gap.
 * @param {string[]} props.attributes.gap    Right answers.
 */
GapFillAnswer.view = ( { attributes: { before, after, gap } } ) => {
	return (
		<ul className="sensei-lms-question-block__answer sensei-lms-question-block__answer--gap-fill">
			<li>{ before }</li>
			<li className="sensei-lms-question-block__answer--gap-fill__right-answers sensei-lms-question-block__text-input-placeholder">
				{ gap.map( ( answer ) => (
					<span
						key={ answer }
						className="sensei-lms-question-block__answer--gap-fill__token"
					>
						{ answer }
					</span>
				) ) }
			</li>
			<li>{ after }</li>
		</ul>
	);
};

export default GapFillAnswer;

/**
 * Update input value, making sure React event handlers are triggered.
 *
 * @param {HTMLInputElement} node       Input element.
 * @param {string}           inputValue New input value.
 */
const setInputValue = ( node, inputValue ) => {
	delete node.value;
	node.value = inputValue;
	node.dispatchEvent( new window.Event( 'change', { bubbles: true } ) );
};
