/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { useContext, createContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import icon from '../../../icons/question-icon';
import { QuestionContext } from '../question-block/question-context';

/**
 * Internal dependencies
 */
import { OptionToggle } from './option-toggle';
import { RigthWrongToggle } from './rigth-wrong-toggle';

/**
 * Answer option in a multiple choice type question block.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes         Answer attributes.
 * @param {boolean}  props.attributes.correct Is this a right answer.
 * @param {Function} props.setAttributes      Update answer attributes.
 */
export const MultipleChoiceAnswerOption = ( props ) => {
	const {
		attributes: { correct },
		setAttributes,
	} = props;

	const { hasSelected } = useContext( QuestionContext );
	const { hasMultipleRight } = useContext(
		MultipleChoiceAnswerOption.Context
	);

	const toggleCorrect = () => setAttributes( { correct: ! correct } );

	return (
		<div className="sensei-lms-question-block__multiple-choice-answer-option">
			<OptionToggle
				isChecked={ correct }
				isCheckbox={ hasMultipleRight }
			/>
			<div className="sensei-lms-question-block__multiple-choice-answer-option__input">
				<InnerBlocks
					template={ MultipleChoiceAnswerOption.template }
					templateLock={ false }
				/>
			</div>
			{ hasSelected && (
				<RigthWrongToggle
					value={ correct }
					onChange={ toggleCorrect }
				/>
			) }
		</div>
	);
};

MultipleChoiceAnswerOption.Context = createContext( {} );

MultipleChoiceAnswerOption.template = [
	[
		'core/paragraph',
		{
			placeholder: __( 'Add Answer', 'sensei-lms' ),
		},
	],
];

export default {
	name: 'sensei-lms/quiz-question-answer-choice',
	//parent: [ 'sensei-lms/quiz-question-answers' ],
	category: 'sensei-lms',
	supports: {
		html: false,
	},
	attributes: {
		correct: {
			type: 'boolean',
			default: false,
		},
	},
	title: __( 'Answer', 'sensei-lms' ),
	icon,
	description: __(
		'A possible answer to a multiple choice question',
		'sensei-lms'
	),
	edit: MultipleChoiceAnswerOption,
	save: () => <InnerBlocks.Content />,
};
