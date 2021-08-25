/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import usePreviewState from './use-preview-state';
import useToggleBlocks from './use-toggle-blocks';
import useHasQuiz from './use-has-quiz';
import useCompleteLessonAllowed from './use-complete-lesson-allowed';
import LessonActionsSettings from './lesson-actions-settings';
import {
	ACTION_BLOCKS,
	INNER_BLOCKS_TEMPLATE,
	IN_PROGRESS_PREVIEW,
} from './constants';

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
const LessonActionsEdit = ( props ) => {
	const {
		className,
		clientId,
		setAttributes,
		attributes: { toggledBlocks },
	} = props;
	const [ previewState, onPreviewChange ] = usePreviewState(
		IN_PROGRESS_PREVIEW
	);

	const toggleBlocks = useToggleBlocks( {
		parentClientId: clientId,
		setAttributes,
		toggledBlocks,
		blocks: [
			{
				blockName: 'sensei-lms/button-reset-lesson',
				label: __( 'Reset Lesson', 'sensei-lms' ),
			},
		],
	} );

	const hasQuiz = useHasQuiz();
	const quizStateClass = hasQuiz ? 'has-quiz' : 'no-quiz';

	const completeLessonAllowed = useCompleteLessonAllowed( hasQuiz );
	const completeLessonAllowedClass = completeLessonAllowed
		? 'allowed'
		: 'not-allowed';

	// Filter inner blocks based on the settings.
	const filteredInnerBlocksTemplate = INNER_BLOCKS_TEMPLATE.filter(
		( i ) => false !== toggledBlocks[ i[ 0 ] ]
	);

	return (
		<>
			<LessonActionsSettings
				previewState={ previewState }
				onPreviewChange={ onPreviewChange }
				toggleBlocks={ toggleBlocks }
			/>
			<div
				className={ classnames(
					className,
					`wp-block-sensei-lms-lesson-actions__preview-${ previewState }`,
					`wp-block-sensei-lms-lesson-actions__${ quizStateClass }`,
					`wp-block-sensei-lms-lesson-actions__complete_lessons-${ completeLessonAllowedClass }`
				) }
			>
				<div className="sensei-buttons-container">
					<InnerBlocks
						allowedBlocks={ ACTION_BLOCKS }
						template={ filteredInnerBlocksTemplate }
						templateLock="all"
						templateInsertUpdatesSelection={ false }
					/>
				</div>
			</div>
		</>
	);
};

export default LessonActionsEdit;
