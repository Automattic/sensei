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

/**
 * Quiz block inserter for adding new or existing questions.
 *
 * @param {Object}   props
 * @param {string}   props.clientId  Quiz block ID.
 * @param {Function} props.openModal Open modal callback.
 */
const QuizAppender = ( { clientId, openModal } ) => {
	const { insertBlock } = useDispatch( 'core/block-editor' );

	const addNewQuestionBlock = () =>
		insertBlock(
			createBlock( questionBlock.name ),
			undefined,
			clientId,
			true
		);

	return (
		<div className="sensei-lms-quiz-block__appender block-editor-default-block-appender">
			<DropdownMenu
				icon={ plus }
				toggleProps={ {
					className: 'block-editor-inserter__toggle',
				} }
				label={ __( 'Add Block', 'sensei-lms' ) }
				controls={ [
					{
						title: __( 'New Question', 'sensei-lms' ),
						icon: questionBlock.icon,
						onClick: addNewQuestionBlock,
					},
					{
						title: __( 'Existing Question(s)', 'sensei-lms' ),
						icon: quizIcon,
						onClick: openModal,
					},
				] }
			/>
			<p
				className="sensei-lms-quiz-block__appender__placeholder"
				data-placeholder={ __(
					'Add new or existing question',
					'sensei-lms'
				) }
			/>
		</div>
	);
};

export default QuizAppender;
