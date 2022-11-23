/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import QuizIcon from '../../../icons/quiz.svg';
import questionBlock from '../question-block';
import categoryQuestionBlock from '../category-question-block';
import { useNextQuestionIndex } from './next-question-index';
import TextAppender from '../../../shared/components/text-appender';
import { applyFilters } from '@wordpress/hooks';

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

	/**
	 * Filter the controls for the quiz question appender.
	 *
	 * @param {Object[]} controls
	 * @param {string}   controls.id      Control ID.
	 * @param {string}   controls.label   Control label.
	 * @param {Function} controls.onClick Control click handler.
	 *
	 * @return {Object[]} Filtered controls.
	 */
	const controls = applyFilters( 'sensei-lms.Quiz.appender-controls', [
		{
			id: 'new-question',
			title: __( 'New Question', 'sensei-lms' ),
			icon: questionBlock.icon,
			onClick: () => addNewQuestionBlock( questionBlock ),
		},
		{
			id: 'category-question',
			title: __( 'Category Question(s)', 'sensei-lms' ),
			icon: QuizIcon,
			onClick: () => addNewQuestionBlock( categoryQuestionBlock ),
		},
		{
			id: 'existing-question',
			title: __( 'Existing Question(s)', 'sensei-lms' ),
			icon: QuizIcon,
			onClick: openModal,
		},
	] );

	const text = __( 'Add new or existing question(s)', 'sensei-lms' );

	return <TextAppender controls={ controls } text={ text } label={ text } />;
};

export default QuizAppender;
