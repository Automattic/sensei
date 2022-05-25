/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor/build/store';

/**
 * Internal dependencies
 */
import CourseDetailsStep from './steps/course-details-step';
import CourseUpgradeStep from './steps/course-upgrade-step';
import CoursePatternsStep from './steps/course-patterns-step';
import LessonDetailsStep from './steps/lesson-details-step';
import LessonPatternsStep from './steps/lesson-patterns-step';
import { EXTENSIONS_STORE } from '../../extensions/store';

const useEditorWizardSteps = () => {
	const stepsByPostType = {
		course: [ CourseDetailsStep, CourseUpgradeStep, CoursePatternsStep ],
		lesson: [ LessonDetailsStep, LessonPatternsStep ],
	};
	const { postType, senseiProExtension } = useSelect(
		( select ) => ( {
			postType: select( editorStore )?.getCurrentPostType(),
			senseiProExtension: select(
				EXTENSIONS_STORE
			).getSenseiProExtension(),
		} ),
		[]
	);

	if ( ! senseiProExtension || senseiProExtension.is_installed === true ) {
		stepsByPostType.course = stepsByPostType.course.filter(
			( step ) => step !== CourseUpgradeStep
		);
	}

	return stepsByPostType[ postType ];
};

export default useEditorWizardSteps;
