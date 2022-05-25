/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useEditorWizardSteps from './use-editor-wizard-steps';
import CourseDetailsStep from './steps/course-details-step';
import CourseUpgradeStep from './steps/course-upgrade-step';
import CoursePatternsStep from './steps/course-patterns-step';
import LessonDetailsStep from './steps/lesson-details-step';
import LessonPatternsStep from './steps/lesson-patterns-step';

jest.mock( '@wordpress/data' );

describe( 'useEditorWizardSteps()', () => {
	it( 'Should have all the course steps when the post type is course and Sensei Pro is not installed', () => {
		useSelect.mockReturnValue( {
			postType: 'course',
			senseiProExtension: { is_installed: false },
		} );
		const steps = useEditorWizardSteps();
		expect( steps ).toEqual( [
			CourseDetailsStep,
			CourseUpgradeStep,
			CoursePatternsStep,
		] );
	} );
	it( 'Should have all the course steps when the post type is course and Sensei Pro is installed', () => {
		useSelect.mockReturnValue( {
			postType: 'course',
			senseiProExtension: { is_installed: true },
		} );
		const steps = useEditorWizardSteps();
		expect( steps ).toEqual( [ CourseDetailsStep, CoursePatternsStep ] );
	} );
	it( 'Should have all the lesson steps when the post type is lesson', () => {
		useSelect.mockReturnValue( {
			postType: 'lesson',
			senseiProExtension: { is_installed: false },
		} );
		const steps = useEditorWizardSteps();
		expect( steps ).toEqual( [ LessonDetailsStep, LessonPatternsStep ] );
	} );
} );
