/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import LimitedTextControl from '../../../blocks/editor-components/limited-text-control';
import detailsStepImage from '../../../images/details-step.png';

/**
 * Initial step for course creation wizard.
 *
 * @param {Object}   props
 * @param {Object}   props.wizardData    Wizard data.
 * @param {Function} props.setWizardData Wizard data setter.
 */
const CourseDetailsStep = ( { wizardData, setWizardData } ) => {
	const { editPost } = useDispatch( editorStore );

	const updateCourseTitle = ( title ) => {
		setWizardData( { ...wizardData, title } );
		editPost( { title } );
	};

	const updateCourseDescription = ( description ) => {
		setWizardData( {
			...wizardData,
			description,
		} );
		editPost( { excerpt: description } );
	};

	return (
		<div className="sensei-editor-wizard-modal__columns">
			<div className="sensei-editor-wizard-modal__content">
				<h1 className="sensei-editor-wizard-step__title">
					{ __( 'Create your course', 'sensei-lms' ) }
				</h1>
				<p className="sensei-editor-wizard-step__description">
					{ __(
						'Keep your Course Title short as it will get displayed in different places around your website. You can easily change both later.',
						'sensei-lms'
					) }
				</p>
				<div className="sensei-editor-wizard-step__form">
					<LimitedTextControl
						className="sensei-editor-wizard-step__form-control"
						label={ __( 'Course Title', 'sensei-lms' ) }
						value={ wizardData.title ?? '' }
						onChange={ updateCourseTitle }
						maxLength={ 40 }
					/>
					<LimitedTextControl
						className="sensei-editor-wizard-step__form-control"
						label={ __( 'Course Description', 'sensei-lms' ) }
						value={ wizardData.description ?? '' }
						onChange={ updateCourseDescription }
						maxLength={ 350 }
						multiline={ true }
					/>
				</div>
			</div>
			<div className="sensei-editor-wizard-modal__illustration">
				<img
					src={ window.sensei.pluginUrl + detailsStepImage }
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
		<Button isPrimary onClick={ goToNextStep }>
			{ __( 'Continue', 'sensei-lms' ) }
		</Button>
	);
};

export default CourseDetailsStep;
