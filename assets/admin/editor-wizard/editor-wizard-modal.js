/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
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
import { SENSEI_TOUR_STORE } from '../tour/data/store';

/**
 * Editor wizard modal component.
 */
const EditorWizardModal = () => {
	const wizardDataState = useState( {} );
	const wizardData = wizardDataState[ 0 ];
	const { editPost, savePost } = useDispatch( editorStore );
	const { setTourShowStatus } = useDispatch( SENSEI_TOUR_STORE ) ?? {};
	const logEvent = useLogEvent();

	const [ open, setDone ] = useWizardOpenState();
	const steps = useEditorWizardSteps();

	const setDefaultPattern = useSetDefaultPattern( {
		'sensei-content-description': wizardData.description,
	} );

	const showSenseiTour = ( show ) => {
		if ( setTourShowStatus ) {
			setTourShowStatus( show );
		}
	};

	const onWizardCompletion = () => {
		setDone( true );
		editPost( {
			meta: { _new_post: false },
		} );
		savePost();
		showSenseiTour( true );
	};

	const skipWizard = () => {
		setDefaultPattern();
		onWizardCompletion();
	};

	const { setShowWelcomeGuide } =
		useDispatch( 'automattic/wpcom-welcome-guide' ) ?? {};

	const { isShowWelcomeGuide } = useSelect( ( select ) => {
		const { isWelcomeGuideShown } =
			select( 'automattic/wpcom-welcome-guide' ) ?? {};
		return {
			isShowWelcomeGuide: isWelcomeGuideShown
				? isWelcomeGuideShown()
				: false,
		};
	}, [] );

	useEffect( () => {
		showSenseiTour( false );
		if ( setShowWelcomeGuide && isShowWelcomeGuide ) {
			setShowWelcomeGuide( undefined, {
				onlyLocal: true,
			} );
		}
	}, [ setShowWelcomeGuide, isShowWelcomeGuide ] );

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
