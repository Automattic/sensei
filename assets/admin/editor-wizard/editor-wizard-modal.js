/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
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
import LessonDetailsStep from './steps/lesson-details-step';
import LessonPatternsStep from './steps/lesson-patterns-step';

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
	const [ modalTitle, setModalTitle ] = useState( '' );

	const closeModal = () => {
		setDone( true );
		synchronizeTemplate();
		editPost( {
			meta: { _new_post: false },
		} );
	};

	// Choose steps by post type.
	const stepsByPostType = {
		course: [ CourseDetailsStep, UpgradeStep, CoursePatternsStep ],
		lesson: [ LessonDetailsStep, LessonPatternsStep ],
	};
	const { postType } = useSelect( ( select ) => ( {
		postType: select( editorStore )?.getCurrentPostType(),
	} ) );
	const steps = stepsByPostType[ postType ];

	// eslint-disable-next-line no-unused-vars
	const onWizardCompletion = ( wizardData ) => {
		// TODO Implement actions when wizard is completed
		closeModal();
	};

	const updateModalTitle = ( step ) => {
		if ( step.Title !== undefined ) {
			setModalTitle( step.Title );
		}
	};

	return (
		( open && steps && (
			<Modal
				className="sensei-editor-wizard-modal"
				onRequestClose={ closeModal }
				title={ modalTitle }
			>
				<Wizard
					steps={ steps }
					onStepChange={ updateModalTitle }
					onCompletion={ onWizardCompletion }
				/>
			</Modal>
		) ) ||
		null
	);
};

export default EditorWizardModal;
