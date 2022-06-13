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
import { useWizardOpenState, useSetDefaultPattern } from './helpers';
import '../../shared/data/api-fetch-preloaded-once';

/**
 * Editor wizard modal component.
 */
const EditorWizardModal = () => {
	const dataState = useState( {} );
	const { editPost, savePost } = useDispatch( editorStore );

	const [ open, setDone ] = useWizardOpenState();
	const steps = useEditorWizardSteps();
	const setDefaultPattern = useSetDefaultPattern( {
		'sensei-content-description': dataState[ 0 ].description,
	} );

	const onWizardCompletion = () => {
		setDone( true );
		const newPostData = {
			meta: { _new_post: false },
			title: dataState[ 0 ].title,
		};
		if ( dataState[ 0 ].description ) {
			newPostData.excerpt = dataState[ 0 ].description;
		}
		editPost( newPostData );
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
				onRequestClose={ skipWizard }
			>
				<Wizard
					steps={ steps }
					dataState={ dataState }
					onCompletion={ onWizardCompletion }
					skipWizard={ skipWizard }
				/>
			</Modal>
		)
	);
};

export default EditorWizardModal;
