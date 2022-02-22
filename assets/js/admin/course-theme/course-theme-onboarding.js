/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useLayoutEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Guide from './guide';
import useCourseMeta from '../../../react-hooks/use-course-meta';
import { SENSEI_THEME } from './constants';

const imagesPath = `${ window.sensei.pluginUrl }assets/dist/images`;
const completedFeatureName = 'senseiCourseThemeOnboardingCompleted';
const courseThemePanelName = 'sensei-course-theme-plugin/sensei-course-theme';

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
 * A React Hook to control the onboarding open state.
 *
 * @return {boolean} Whether the onboarding is open.
 */
const useOnboardingOpen = () => {
	const { onboardingCompleted } = useSelect( ( select ) => ( {
		onboardingCompleted: select( 'core/edit-post' ).isFeatureActive(
			completedFeatureName
		),
	} ) );

	const hasOpenModal = useObserveOpenModal( ! onboardingCompleted );
	const [ isOnboardingOpen, setOnboardingOpen ] = useState( false );

	useLayoutEffect( () => {
		if ( onboardingCompleted ) {
			setOnboardingOpen( false );
		} else if ( false === hasOpenModal ) {
			// If no modal is open, it's time to open.
			setOnboardingOpen( true );
		}
	}, [ onboardingCompleted, hasOpenModal ] );

	return isOnboardingOpen;
};

/**
 * Course Theme Onboarding component.
 */
const CourseThemeOnboarding = () => {
	const { toggleFeature, toggleEditorPanelOpened } = useDispatch(
		'core/edit-post'
	);
	const { savePost } = useDispatch( 'core/editor' );

	const { isCourseThemePanelOpen } = useSelect( ( select ) => ( {
		isCourseThemePanelOpen: select( 'core/edit-post' ).isEditorPanelOpened(
			courseThemePanelName
		),
	} ) );

	const isOnboardingOpen = useOnboardingOpen();
	const [ , setTheme ] = useCourseMeta( '_course_theme' );

	if ( ! isOnboardingOpen ) {
		return null;
	}

	return (
		<Guide
			className="sensei-course-theme-onboarding"
			contentLabel={ __( 'New learning experience!', 'sensei-lms' ) }
			onFinish={ () => {
				toggleFeature( completedFeatureName );
			} }
			pages={ [
				{
					image: (
						<div className="sensei-course-theme-onboarding__image-container">
							<img
								src={ `${ imagesPath }/onboarding-learning-mode.jpg` }
								alt={ __(
									'Learning mode sample.',
									'sensei-lms'
								) }
							/>
						</div>
					),
					content: (
						<>
							<h1 className="sensei-course-theme-onboarding__heading">
								{ __(
									'New! Distraction-free learning experience',
									'sensei-lms'
								) }
							</h1>
							<p className="sensei-course-theme-onboarding__text">
								{ __(
									'Enable Sensei’s ‘learning mode’ to show an immersive and dedicated view for courses, lessons, and quizzes.',
									'sensei-lms'
								) }
							</p>
						</>
					),
					footer: ( { goForward } ) => (
						<>
							<a
								className="sensei-course-theme-onboarding__learn-more components-button components-guide__back-button"
								href="https://senseilms.com/wordpress-course-theme"
								rel="noreferrer external"
								target="_blank"
							>
								{ __( 'Learn more', 'sensei-lms' ) }
							</a>
							<Button
								className="components-guide__forward-button"
								onClick={ () => {
									// Open sidebar panel
									if ( ! isCourseThemePanelOpen ) {
										toggleEditorPanelOpened(
											courseThemePanelName
										);
									}

									setTheme( SENSEI_THEME );
									savePost();
									goForward();
								} }
							>
								{ __( 'Enable learning mode', 'sensei-lms' ) }
							</Button>
						</>
					),
				},
				{
					image: (
						<div className="sensei-course-theme-onboarding__image-container">
							<img
								src={ `${ imagesPath }/onboarding-learning-mode-check.jpg` }
								alt={ __(
									'Learning mode sample with check icon.',
									'sensei-lms'
								) }
							/>
						</div>
					),
					content: (
						<>
							<h1 className="sensei-course-theme-onboarding__heading">
								{ __(
									'We’ve enabled learning mode for this course!',
									'sensei-lms'
								) }
							</h1>
							<p className="sensei-course-theme-onboarding__text">
								{ __(
									'For more options you can access the ‘course styles’ setting in the course sidebar.',
									'sensei-lms'
								) }
							</p>
						</>
					),
					footer: ( { onFinish } ) => (
						<Button
							className="components-guide__forward-button"
							onClick={ onFinish }
						>
							{ __( 'Sounds good', 'sensei-lms' ) }
						</Button>
					),
				},
			] }
		/>
	);
};

export default CourseThemeOnboarding;
