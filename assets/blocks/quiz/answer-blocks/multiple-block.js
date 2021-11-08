/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	getSaveContent,
	synchronizeBlocksWithTemplate,
} from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import {
	MultipleChoiceAnswerOption,
	default as answerBlock,
} from './multiple-block-option';

/**
 * Internal dependencies
 */
import { OptionToggle } from './option-toggle';
import { useAutoInserter } from '../../../shared/blocks/use-auto-inserter';

/**
 * Check if there are more than one right answers.
 *
 * @param {Array} answers
 */
const hasMultipleRightAnswers = ( answers ) =>
	answers.filter( ( a ) => a.attributes.correct ).length > 1;

const TEMPLATE = [
	[ answerBlock.name, { correct: true } ],
	// Wrong answer option will be added by the auto-inserter.
];
const ALLOWED_BLOCKS = [ answerBlock.name ];

/**
 * Answer component for question blocks with multiple choice type.
 *
 * @param {Object} props
 * @param {Object} props.blockProps Props of the containing "Answers" block.
 */
const MultipleChoiceAnswer = ( { blockProps } ) => {
	const answers = useSelect( ( select ) =>
		select( 'core/block-editor' ).getBlocks( blockProps.clientId )
	);
	const hasMultipleRight = hasMultipleRightAnswers( answers );

	const context = useMemo(
		() => ( {
			hasMultipleRight,
		} ),
		[ hasMultipleRight ]
	);

	//const hasDraft = ! answers[ answers.length - 1 ]?.label;

	const emptyContent = useMemo(
		() =>
			getSaveContent(
				answerBlock.name,
				{},
				synchronizeBlocksWithTemplate(
					[],
					MultipleChoiceAnswerOption.template
				)
			),
		[]
	);

	useAutoInserter(
		{
			name: answerBlock.name,
			isEmptyBlock: ( block ) => {
				return (
					emptyContent ===
					getSaveContent(
						block.name,
						block.attributes,
						block.innerBlocks
					)
				);
			},
		},
		blockProps
	);

	return (
		<MultipleChoiceAnswerOption.Context.Provider value={ context }>
			<InnerBlocks
				allowedBlocks={ ALLOWED_BLOCKS }
				template={ TEMPLATE }
				templateLock={ false }
				renderAppender={ false }
			/>
		</MultipleChoiceAnswerOption.Context.Provider>
	);
};

/**
 * Read-only multiple choice answer component.
 *
 * @param {Object} props
 * @param {Object} props.attributes
 * @param {Array}  props.attributes.answers Answers.
 */
MultipleChoiceAnswer.view = ( { attributes: { answers = [] } } ) => {
	const hasMultipleRight = hasMultipleRightAnswers( answers );

	return (
		<MultipleChoiceAnswer.Options answers={ answers }>
			{ ( answer ) => (
				<>
					<OptionToggle
						isChecked={ answer.correct }
						isCheckbox={ hasMultipleRight }
					/>
					{ answer.label }
				</>
			) }
		</MultipleChoiceAnswer.Options>
	);
};

export default MultipleChoiceAnswer;
