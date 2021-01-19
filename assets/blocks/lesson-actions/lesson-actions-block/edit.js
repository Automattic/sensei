import { useState, useEffect } from '@wordpress/element';
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

import usePreviewState from './use-preview-state';
import useToggleBlocks from './use-toggle-blocks';
import { LessonActionsBlockSettings } from './settings';
import { ACTION_BLOCKS, INNER_BLOCKS_TEMPLATE } from './constants';

/**
 * Has quiz hook.
 *
 * @param {Object}   options            Hook options.
 * @param {Function} options.quizToggle Toggle the quiz block.
 *
 * @return {boolean} If a quiz exists with questions.
 */
const useHasQuiz = ( { quizToggle } ) => {
	const [ quizEventListener ] = useState( null );
	const [ hasQuiz, setHasQuiz ] = useState( () => {
		const questionCount = document.getElementById( 'question_counter' );

		return questionCount && parseInt( questionCount.value, 10 ) > 0;
	} );

	useEffect( () => {
		quizToggle( hasQuiz );
	}, [ hasQuiz, quizToggle ] );

	useEffect( () => {
		const quizToggleEventHandler = ( event ) => {
			setHasQuiz( event.detail.questions > 0 );
		};

		window.addEventListener(
			'sensei-quiz-editor-question-count-updated',
			quizToggleEventHandler
		);

		return () => {
			window.removeEventListener(
				'sensei-quiz-editor-question-count-updated',
				quizToggleEventHandler
			);
		};
	}, [ quizEventListener ] );

	return hasQuiz;
};

/**
 * Edit lesson actions block component.
 *
 * @param {Object}   props
 * @param {string}   props.className                Custom class name.
 * @param {string}   props.clientId                 Block ID.
 * @param {Function} props.setAttributes            Block set attributes function.
 * @param {Object}   props.attributes               Block attributes.
 * @param {Object}   props.attributes.toggledBlocks Toggled blocks, where the key is the block name.
 */
const EditLessonActionsBlock = ( {
	className,
	clientId,
	setAttributes,
	attributes: { toggledBlocks },
} ) => {
	const [ previewState, onPreviewChange ] = usePreviewState( 'in-progress' );

	const toggleBlocks = useToggleBlocks( {
		parentClientId: clientId,
		setAttributes,
		toggledBlocks,
		blocks: [
			{
				blockName: 'sensei-lms/button-reset-lesson',
				label: __( 'Reset lesson', 'sensei-lms' ),
			},
			{
				blockName: 'sensei-lms/button-view-quiz',
			},
		],
	} );

	useHasQuiz( {
		quizToggle: toggleBlocks.find(
			( i ) => i.blockName === 'sensei-lms/button-view-quiz'
		).onToggle,
	} );

	// Filter inner blocks based on the settings.
	const filteredInnerBlocksTemplate = INNER_BLOCKS_TEMPLATE.filter(
		( i ) => false !== toggledBlocks[ i[ 0 ] ]
	);

	const userToggleBlocks = toggleBlocks.filter( ( i ) => false !== i.label );

	return (
		<>
			<LessonActionsBlockSettings
				previewState={ previewState }
				onPreviewChange={ onPreviewChange }
				toggleBlocks={ userToggleBlocks }
			/>
			<div
				className={ classnames(
					className,
					`wp-block-sensei-lms-lesson-actions__preview-${ previewState }`
				) }
			>
				<div className="sensei-buttons-container">
					<InnerBlocks
						allowedBlocks={ ACTION_BLOCKS }
						template={ filteredInnerBlocksTemplate }
						templateLock="all"
					/>
				</div>
			</div>
		</>
	);
};

export default EditLessonActionsBlock;
