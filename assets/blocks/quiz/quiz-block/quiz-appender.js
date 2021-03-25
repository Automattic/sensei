/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { DropdownMenu } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import quizIcon from '../../../icons/quiz-icon';
import questionBlock from '../question-block';
import categoryQuestionBlock from '../category-question-block';
import { useNextQuestionIndex } from './next-question-index';

/**
 * Quiz block inserter for adding new or existing questions.
 *
 * @param {Object}   props
 * @param {string}   props.clientId  Quiz block ID.
 * @param {Function} props.openModal Open modal callback.
 */
const QuizAppender = ( { clientId, openModal } ) => {
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const nextInsertIndex = useNextQuestionIndex( clientId );

	const addNewQuestionBlock = ( block ) => {
		insertBlock(
			createBlock( block.name ),
			nextInsertIndex,
			clientId,
			true
		);
	};

	const controls = [
		{
			title: __( 'New Question', 'sensei-lms' ),
			icon: questionBlock.icon,
			onClick: () => addNewQuestionBlock( questionBlock ),
		},
		{
			title: __( 'Category Question(s)', 'sensei-lms' ),
			icon: quizIcon,
			onClick: () => addNewQuestionBlock( categoryQuestionBlock ),
		},
		{
			title: __( 'Existing Question(s)', 'sensei-lms' ),
			icon: quizIcon,
			onClick: openModal,
		},
	];

	return (
		<div className="sensei-lms-quiz-block__appender block-editor-default-block-appender">
			<DropdownMenu
				icon={ plus }
				toggleProps={ {
					className: 'block-editor-inserter__toggle',
					onMouseDown: ( event ) => event.preventDefault(),
				} }
				label={ __( 'Add Block', 'sensei-lms' ) }
				controls={ controls }
			/>
			<p
				className="sensei-lms-quiz-block__appender__placeholder"
				data-placeholder={ __(
					'Add new or existing question(s)',
					'sensei-lms'
				) }
			/>
		</div>
	);
};

export default QuizAppender;
