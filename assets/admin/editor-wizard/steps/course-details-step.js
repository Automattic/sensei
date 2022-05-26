/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import LimitedTextControl from '../../../blocks/editor-components/limited-text-control';

/**
 * Initial step for course creation wizard.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 */
const CourseDetailsStep = ( { data: wizardData, setData: setWizardData } ) => {
	usePostTitle( wizardData.courseTitle );

	const updateCourseTitle = ( value ) => {
		setWizardData( { ...wizardData, courseTitle: value } );
	};

	const updateCourseDescription = ( value ) => {
		setWizardData( {
			...wizardData,
			courseDescription: value,
		} );
	};

	return (
		<div className="sensei-editor-wizard-modal__columns">
			<div className="sensei-editor-wizard-modal__content">
				<h1>Course Details Step</h1>
				<div className="sensei-editor-wizard-step__description">
					{ __(
						'Keep your Course Title short as it will get displayed in different places around your website. You can easily change both later.',
						'sensei-lms'
					) }
				</div>
				<div className="sensei-editor-wizard-step__form">
					<LimitedTextControl
						label={ __( 'Course Title', 'sensei-lms' ) }
						value={ wizardData.courseTitle ?? '' }
						onChange={ updateCourseTitle }
						maxLength={ 40 }
					/>
					<LimitedTextControl
						label={ __( 'Course Description', 'sensei-lms' ) }
						value={ wizardData.courseDescription ?? '' }
						onChange={ updateCourseDescription }
						maxLength={ 350 }
						multiline={ true }
					/>
				</div>
			</div>
			<div className="sensei-editor-wizard-modal__illustration">
				<img
					src={
						window.sensei.pluginUrl +
						'assets/dist/images/course-details-step.png'
					}
					alt={ __(
						'Illustration of course sample with some placeholders.',
						'sensei-lms'
					) }
					className="sensei-editor-wizard-modal__illustration-image"
				/>
			</div>
		</div>
	);
};

CourseDetailsStep.Actions = ( { goToNextStep } ) => {
	return (
		<div>
			<Button isPrimary onClick={ goToNextStep }>
				{ __( 'Continue', 'sensei-lms' ) }
			</Button>
		</div>
	);
};

function usePostTitle( title ) {
	const { editPost } = useDispatch( editorStore );
	useEffect( () => {
		editPost( {
			title,
		} );
	}, [ title, editPost ] );
}

export default CourseDetailsStep;
