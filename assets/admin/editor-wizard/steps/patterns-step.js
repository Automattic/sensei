/**
 * WordPress dependencies
 */
import { Button, createSlotFill } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternsList from '../patterns-list';
import { replacePlaceholders } from '../helpers';

const { Fill, Slot } = createSlotFill( 'Patterns Upsell' );

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
			<h1 className="sensei-editor-wizard-step__sticky-title">
				{ title }
			</h1>
			<Slot />
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

/**
 * Component to fill the Patterns Upsell section
 */
PatternsStep.UpsellFill = Fill;

export default PatternsStep;
