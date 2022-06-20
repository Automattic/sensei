/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import CourseDetailsStep from './steps/course-details-step';
import CourseUpgradeStep from './steps/course-upgrade-step';
import CoursePatternsStep from './steps/course-patterns-step';
import LessonDetailsStep from './steps/lesson-details-step';
import LessonPatternsStep from './steps/lesson-patterns-step';
import { useHideEditorWizardUpsell } from './helpers';

/**
 * Returns the list of components (representing steps) for the Editor Wizard according to the post type and if
 * Sensei Pro is activated or not.
 *
 * @return {Array} The list of components to show to the user.
 */
const useEditorWizardSteps = () => {
	const stepsByPostType = {
		course: [ CourseDetailsStep, CourseUpgradeStep, CoursePatternsStep ],
		lesson: [ LessonDetailsStep, LessonPatternsStep ],
	};
	const { postType } = useSelect(
		( select ) => ( {
			postType: select( editorStore )?.getCurrentPostType(),
		} ),
		[]
	);

	const shouldHideEditorWizardUpsell = useHideEditorWizardUpsell();

	if ( shouldHideEditorWizardUpsell ) {
		stepsByPostType.course = stepsByPostType.course.filter(
			( step ) => step !== CourseUpgradeStep
		);
	}

	return stepsByPostType[ postType ];
};

export default useEditorWizardSteps;
