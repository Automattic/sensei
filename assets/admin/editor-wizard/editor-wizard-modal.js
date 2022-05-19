/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import { useEffect, useLayoutEffect, useState } from '@wordpress/element';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
/**
 * Internal dependencies
 */
import Wizard from './wizard';
import CourseDetailsStep from './steps/course-details-step';
import UpgradeStep from './steps/upgrade-step';
import CoursePatternsStep from './steps/course-patterns-step';

/**
 * A React Hook to observe if a modal is open based on the body class.
 *
 * @param {boolean} shouldObserve If it should observe the changes.
 *
 * @return {boolean|undefined} Whether a modal is open, or `undefined` if it's not initialized yet.
 */
const useObserveOpenModal = ( shouldObserve ) => {
	const [ hasOpenModal, setHasOpenModal ] = useState();

	useEffect( () => {
		if ( ! shouldObserve ) {
			return;
		}

		// Initialize state after modals are open or not.
		setTimeout( () => {
			setHasOpenModal( document.body.classList.contains( 'modal-open' ) );
		}, 1 );

		const observer = new window.MutationObserver( () => {
			setHasOpenModal( document.body.classList.contains( 'modal-open' ) );
		} );
		observer.observe( document.body, {
			attributes: true,
			attributeFilter: [ 'class' ],
		} );

		return () => {
			observer.disconnect();
		};
	}, [ shouldObserve ] );

	return hasOpenModal;
};

/**
 * A React Hook to control the wizard open state.
 *
 * @return {boolean} Whether the modal should be open.
 */
const useWizardOpenState = () => {
	const [ open, setOpen ] = useState( false );
	const [ done, setDone ] = useState( false );
	const hasOpenModal = useObserveOpenModal( ! done );

	useLayoutEffect( () => {
		if ( done ) {
			setOpen( false );
		} else if ( false === hasOpenModal ) {
			// If no modal is open, it's time to open.
			setOpen( true );
		}
	}, [ done, hasOpenModal ] );

	return [ open, setDone ];
};

/**
 * Editor wizard modal component.
 */
const EditorWizardModal = () => {
	const [ open, setDone ] = useWizardOpenState();
	const { synchronizeTemplate } = useDispatch( blockEditorStore );
	const { editPost } = useDispatch( editorStore );

	const closeModal = () => {
		setDone( true );
		synchronizeTemplate();
		editPost( {
			meta: { _new_post: false },
		} );
	};

	// TODO Implement different flows depending on post type 👇.
	const steps = [ CourseDetailsStep, UpgradeStep, CoursePatternsStep ];

	const onWizardCompletion = ( data ) => {
		// TODO Implement actions when wizard is completed.

		// eslint-disable-next-line no-console
		console.log(
			`Wizard completed with data: ${ JSON.stringify( data ) }`
		);

		closeModal();
	};

	return (
		open && (
			<Modal onRequestClose={ closeModal } title="I'm a modal!">
				<Wizard steps={ steps } onCompletion={ onWizardCompletion } />
			</Modal>
		)
	);
};

export default EditorWizardModal;
