/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import LimitedTextControl from '../../../blocks/editor-components/limited-text-control';
import detailsStepImage from '../../../images/details-step.png';

/**
 * Initial step for lesson creation wizard.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 */
const LessonDetailsStep = ( { data: wizardData, setData: setWizardData } ) => {
	const { editPost } = useDispatch( editorStore );
	const postTitle = usePostTitle();

	const updateLessonTitle = ( title ) => {
		setWizardData( { ...wizardData, lessonTitle: title } );
		editPost( {
			title,
		} );
	};

	return (
		<div className="sensei-editor-wizard-modal__columns">
			<div className="sensei-editor-wizard-modal__content">
				<h1 className="sensei-editor-wizard-modal__title">
					{ __( 'Create your lesson', 'sensei-lms' ) }
				</h1>
				<div className="sensei-editor-wizard-step__description">
					{ __(
						'It is best to keep your Lesson Title short because it will show in your course outline and navigation. You can easily change both later.',
						'sensei-lms'
					) }
				</div>
				<div className="sensei-editor-wizard-step__form">
					<LimitedTextControl
						label={ __( 'Lesson Title', 'sensei-lms' ) }
						value={ wizardData.lessonTitle ?? postTitle }
						onChange={ updateLessonTitle }
						maxLength={ 40 }
					/>
				</div>
			</div>
			<div className="sensei-editor-wizard-modal__illustration">
				<img
					src={ window.sensei.pluginUrl + detailsStepImage }
					className="sensei-editor-wizard-modal__illustration-image"
					alt={ __(
						'Illustration of lesson sample with some placeholders.',
						'sensei-lms'
					) }
				/>
			</div>
		</div>
	);
};

/**
 * Actions for the LessonDetailsStep.
 *
 * @param {Function} goToNextStep Invoke to go to the next step.
 */
LessonDetailsStep.Actions = ( { goToNextStep } ) => {
	return (
		<Button isPrimary onClick={ goToNextStep }>
			{ __( 'Continue', 'sensei-lms' ) }
		</Button>
	);
};

/**
 * Load the post title from the Editor Store.
 *
 * @return {string} The post title from the editor.
 */
const usePostTitle = () => {
	const { title } = useSelect( ( select ) => ( {
		title: select( editorStore )?.getEditedPostAttribute( 'title' ),
	} ) );
	return title;
};

export default LessonDetailsStep;
