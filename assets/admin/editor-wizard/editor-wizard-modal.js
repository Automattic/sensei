/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import Wizard from './wizard';
import useEditorWizardSteps from './use-editor-wizard-steps';
import {
	useWizardOpenState,
	useSetDefaultPattern,
	useLogEvent,
} from './helpers';
import '../../shared/data/api-fetch-preloaded-once';

/**
 * Editor wizard modal component.
 */
const EditorWizardModal = () => {
	const wizardDataState = useState( {} );
	const wizardData = wizardDataState[ 0 ];
	const { editPost, savePost } = useDispatch( editorStore );
	const logEvent = useLogEvent();

	const [ open, setDone ] = useWizardOpenState();
	const steps = useEditorWizardSteps();

	const setDefaultPattern = useSetDefaultPattern( {
		'sensei-content-description': wizardData.description,
	} );

	const onWizardCompletion = () => {
		setDone( true );
		editPost( {
			meta: { _new_post: false },
		} );
		savePost();
	};

	const skipWizard = () => {
		setDefaultPattern();
		onWizardCompletion();
	};

	return (
		open && (
			<Modal
				className="sensei-editor-wizard-modal"
				onRequestClose={ () => {
					skipWizard();
					logEvent( 'editor_wizard_close_modal' );
				} }
			>
				<Wizard
					steps={ steps }
					wizardDataState={ wizardDataState }
					onCompletion={ onWizardCompletion }
					skipWizard={ skipWizard }
				/>
			</Modal>
		)
	);
};

export default EditorWizardModal;
