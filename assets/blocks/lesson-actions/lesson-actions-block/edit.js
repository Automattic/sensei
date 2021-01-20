import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

import usePreviewState from './use-preview-state';
import useToggleBlocks from './use-toggle-blocks';
import useHasQuiz from './use-has-quiz';

import { LessonActionsBlockSettings } from './settings';
import { ACTION_BLOCKS, INNER_BLOCKS_TEMPLATE } from './constants';

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
		],
	} );

	const hasQuiz = useHasQuiz();
	const quizStateClass = hasQuiz ? 'has-quiz' : 'no-quiz';

	// Filter inner blocks based on the settings.
	const filteredInnerBlocksTemplate = INNER_BLOCKS_TEMPLATE.filter(
		( i ) => false !== toggledBlocks[ i[ 0 ] ]
	);

	return (
		<>
			<LessonActionsBlockSettings
				previewState={ previewState }
				onPreviewChange={ onPreviewChange }
				toggleBlocks={ toggleBlocks }
			/>
			<div
				className={ classnames(
					className,
					`wp-block-sensei-lms-lesson-actions__preview-${ previewState }`,
					`wp-block-sensei-lms-lesson-actions__${ quizStateClass }`
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
