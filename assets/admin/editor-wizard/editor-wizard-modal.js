/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import Wizard from './wizard';
import useEditorWizardSteps from './use-editor-wizard-steps';
import { useWizardOpenState, useSetDefaultPattern } from './helpers';
import '../../shared/data/api-fetch-preloaded-once';

/**
 * Editor wizard modal component.
 */
const EditorWizardModal = () => {
	const wizardDataState = useState( {} );
	const wizardData = wizardDataState[ 0 ];
	const { editPost, savePost } = useDispatch( editorStore );
	const { postType } = useSelect(
		( select ) => ( {
			postType: select( editorStore ).getCurrentPostType(),
		} ),
		[]
	);

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
					window.sensei_log_event( 'editor_wizard_close_modal', {
						post_type: postType,
					} );
				} }
			>
				<Wizard
					steps={ steps }
					wizardDataState={ wizardDataState }
					onCompletion={ onWizardCompletion }
					skipWizard={ () => {
						skipWizard();
						window.sensei_log_event(
							'editor_wizard_start_with_default_layout',
							{
								post_type: postType,
							}
						);
					} }
				/>
			</Modal>
		)
	);
};

export default EditorWizardModal;
