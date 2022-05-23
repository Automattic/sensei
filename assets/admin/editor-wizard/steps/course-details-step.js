/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

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
	// TODO Replace this sample implementation.
	const updateNewCourseTitle = ( value ) => {
		setWizardData( { ...wizardData, newCourseTitle: value } );
	};
	const updateNewCourseDescription = ( value ) => {
		setWizardData( {
			...wizardData,
			newCourseDescription: value,
		} );
	};
	return (
		<div className="sensei-editor-wizard-modal__columns">
			<div className="sensei-editor-wizard-modal__content">
				<h1>Course Details Step</h1>
				<div>
					<LimitedTextControl
						label={ __( 'Course Title', 'sensei-lms' ) }
						value={ wizardData.newCourseTitle ?? '' }
						onChange={ updateNewCourseTitle }
						maxLength={ 40 }
					/>
					<LimitedTextControl
						label={ __( 'Course Description', 'sensei-lms' ) }
						value={ wizardData.newCourseDescription ?? '' }
						onChange={ updateNewCourseDescription }
						maxLength={ 350 }
						multiline={ true }
					/>
				</div>
				<div>PENDING TO IMPLEMENT</div>
			</div>
			<div className="sensei-editor-wizard-modal__illustration">
				<img
					src={
						window.sensei.pluginUrl +
						'/assets/images/sensei-pro-upsell.png'
					}
					alt="PENDING TO IMPLEMENT, BUT HERE TO SHOW IT WORKING"
					height="75%"
				/>
			</div>
		</div>
	);
};

CourseDetailsStep.Actions = ( { data, goToNextStep } ) => {
	// Actions have access to the whole wizard data.
	const secondaryAction = () => {
		// TODO Remove this.
		// eslint-disable-next-line no-alert
		window.alert( `Data ${ JSON.stringify( data ) }` );
	};
	return (
		<div>
			<button onClick={ secondaryAction }>
				<span
					role="img"
					aria-label="Funny eyes that will be removed later."
				>
					👀
				</span>
			</button>
			<button onClick={ goToNextStep }>Next</button>
		</div>
	);
};

export default CourseDetailsStep;
