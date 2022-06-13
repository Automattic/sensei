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
	it( 'Should not have the course upgrade step when the post type is course and the Sensei Pro extension is not found', () => {
		useSelect.mockReturnValue( {
			postType: 'course',
			senseiProExtension: undefined,
		} );
		const steps = useEditorWizardSteps();
		expect( steps ).toEqual( [ CourseDetailsStep, CoursePatternsStep ] );
	} );
	it( 'Should not have the course upgrade step when the post type is course and the Sensei Pro is activated', () => {
		useSelect.mockReturnValue( {
			postType: 'course',
			senseiProExtension: { is_activated: true },
		} );
		const steps = useEditorWizardSteps();
		expect( steps ).toEqual( [ CourseDetailsStep, CoursePatternsStep ] );
	} );
	it( 'Should have all the lesson steps when the post type is lesson', () => {
		useSelect.mockReturnValue( {
			postType: 'lesson',
			senseiProExtension: { is_activated: false },
		} );
		const steps = useEditorWizardSteps();
		expect( steps ).toEqual( [ LessonDetailsStep, LessonPatternsStep ] );
	} );
} );
