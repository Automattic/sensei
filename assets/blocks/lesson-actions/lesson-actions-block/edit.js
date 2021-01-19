import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

import usePreviewState from './use-preview-state';
import useToggleBlocks from './use-toggle-blocks';
import useHasQuiz from './use-has-quiz';

import { LessonActionsBlockSettings } from './settings';
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
const EditLessonActionsBlock = ( {
	className,
	clientId,
	setAttributes,
	attributes: { toggledBlocks },
} ) => {
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
