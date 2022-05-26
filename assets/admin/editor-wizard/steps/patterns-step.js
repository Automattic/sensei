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
 * Update blocks content, filling the placeholders.
 *
 * @param {Object[]} blocks      Blocks to fill with the new content.
 * @param {string}   title       Title to fill the placeholders
 * @param {string}   description Description to fill the placeholders.
 * @return {Object[]} Blocks with the placeholders filled.
 */
const fillPlaceholders = ( blocks, title, description ) => {
	if ( ! title && ! description ) {
		return blocks;
	}

	return blocks.map( ( block ) => {
		const { className = '' } = block.attributes;

		if ( title && className.includes( 'sensei-pattern-description' ) ) {
			block.attributes.content = description;
		}

		if ( title && className.includes( 'sensei-pattern-title' ) ) {
			block.attributes.content = title;
		}

		if ( block.innerBlocks ) {
			block.innerBlocks = fillPlaceholders( block.innerBlocks );
		}

		return block;
	} );
};

/**
 * Choose patterns step.
 *
 * @param {Object}   props              Component props.
 * @param {string}   props.title        Step title.
 * @param {Object}   props.data         Wizard data.
 * @param {Function} props.onCompletion On completion callback.
 */
const PatternsStep = ( { title, data, onCompletion } ) => {
	const { resetEditorBlocks } = useDispatch( editorStore );

	const onChoose = ( blocks ) => {
		const blocksWithFilledContent = fillPlaceholders(
			blocks,
			data.title,
			data.description
		);
		resetEditorBlocks( blocksWithFilledContent );
		onCompletion();
	};

	return (
		<div className="sensei-editor-wizard-modal__content">
			<h1 className="sensei-editor-wizard-modal__sticky-title">
				{ title }
			</h1>
			<PatternsList
				title={ data.newCourseTitle }
				description={ data.newCourseDescription }
				onChoose={ onChoose }
			/>
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
	<div>
		<Button isTertiary onClick={ skipWizard }>
			{ __( 'Start with default layout', 'sensei-lms' ) }
		</Button>
	</div>
);

export default PatternsStep;
