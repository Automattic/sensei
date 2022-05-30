/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternsList from '../patterns-list';

/**
 * Update blocks content, replacing the placeholders with a content.
 *
 * @param {Object[]} blocks   Blocks to replace with the new content.
 * @param {Object}   replaces Object containing content to be replaced. The keys are the block
 *                            classNames to find. The values are the content to be replaced.
 *
 * @return {Object[]} Blocks with the placeholders filled.
 */
export const replacePlaceholders = ( blocks, replaces ) =>
	blocks.map( ( block ) => {
		const { className = '' } = block.attributes;
		const replacesArray = Object.entries( replaces );

		replacesArray.forEach( ( [ placeholder, content ] ) => {
			if ( className.includes( placeholder ) ) {
				block.attributes.content = content;
			}
		} );

		if ( block.innerBlocks ) {
			block.innerBlocks = replacePlaceholders(
				block.innerBlocks,
				replaces
			);
		}

		return block;
	} );

/**
 * Choose patterns step.
 *
 * @param {Object}   props              Component props.
 * @param {string}   props.title        Step title.
 * @param {Object}   props.replaces     Object containing content to be replaced. The keys are the
 *                                      block classNames to find. The values are the content to be
 *                                      replaced.
 * @param {Function} props.onCompletion On completion callback.
 */
const PatternsStep = ( { title, replaces, onCompletion } ) => {
	const { resetEditorBlocks } = useDispatch( editorStore );

	const onChoose = ( blocks ) => {
		const newBlocks = replaces
			? replacePlaceholders( blocks, replaces )
			: blocks;

		resetEditorBlocks( newBlocks );
		onCompletion();
	};

	return (
		<div className="sensei-editor-wizard-modal__content">
			<h1 className="sensei-editor-wizard-modal__sticky-title">
				{ title }
			</h1>
			<PatternsList onChoose={ onChoose } />
		</div>
	);
};

/**
 * Choose patterns step.
 *
 * @param {Object}   props            Compoent props.
 * @param {Function} props.skipWizard Skip wizard function.
 */
PatternsStep.Actions = ( { skipWizard } ) => (
	<Button isTertiary onClick={ skipWizard }>
		{ __( 'Start with default layout', 'sensei-lms' ) }
	</Button>
);

export default PatternsStep;
