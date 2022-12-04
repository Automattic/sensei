/**
 * WordPress dependencies
 */
import { Button, createSlotFill } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternsList from '../patterns-list';
import { replacePlaceholders, useLogEvent } from '../helpers';

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
	const { resetEditorBlocks, editPost } = useDispatch( editorStore );
	const logEvent = useLogEvent();
	const editorSettings = useSelect( ( select ) =>
		select( editorStore ).getEditorSettings()
	);
	const availableTemplates = editorSettings.availableTemplates;

	const onChoose = ( blocks, name, template ) => {
		const newBlocks = replaces
			? replacePlaceholders( blocks, replaces )
			: blocks;

		resetEditorBlocks( newBlocks );
		onCompletion();

		//Auto select a template if the pattern specifies an available one.
		if (
			template &&
			Object.keys( availableTemplates ).includes( template )
		) {
			editPost( { template } );
		}

		logEvent( 'editor_wizard_choose_pattern', { name } );
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
const PatternsStepActions = ( { skipWizard } ) => {
	const logEvent = useLogEvent();
	const clickHandler = () => {
		skipWizard();
		logEvent( 'editor_wizard_start_with_default_layout' );
	};

	return (
		<Button isTertiary onClick={ clickHandler }>
			{ __( 'Start with default layout', 'sensei-lms' ) }
		</Button>
	);
};
PatternsStep.Actions = PatternsStepActions;

/**
 * Component to fill the Patterns Upsell section
 */
PatternsStep.UpsellFill = Fill;

export default PatternsStep;
