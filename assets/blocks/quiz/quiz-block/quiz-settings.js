/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../../editor-components/number-control';

/**
 * Quiz settings.
 *
 * @param {Object}   props                    Block props.
 * @param {Object}   props.attributes         Block attributes
 * @param {Object}   props.attributes.options Current setting options.
 * @param {Function} props.setAttributes      Set attributes function.
 */
const QuizSettings = ( { attributes: { options = {} }, setAttributes } ) => {
	const {
		passRequired,
		quizPassmark,
		autoGrade,
		allowRetakes,
		randomQuestionOrder,
		showQuestions,
	} = options;

	const createChangeHandler = ( optionKey ) => ( value ) =>
		setAttributes( { options: { ...options, [ optionKey ]: value } } );

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Quiz Settings', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<PanelRow>
					<ToggleControl
						checked={ passRequired }
						onChange={ createChangeHandler( 'passRequired' ) }
						label={ __( 'Pass required', 'sensei-lms' ) }
					/>
				</PanelRow>
				{ passRequired && (
					<PanelRow>
						<RangeControl
							label={ 'Passing Grade' }
							value={ quizPassmark }
							onChange={ createChangeHandler( 'quizPassmark' ) }
							min={ 0 }
							max={ 100 }
							initialPosition={ 100 }
						/>
					</PanelRow>
				) }
				<PanelRow>
					<ToggleControl
						checked={ autoGrade }
						onChange={ createChangeHandler( 'autoGrade' ) }
						label={ __( 'Auto Grade', 'sensei-lms' ) }
						help={ __(
							'Only applicable for multiple choice, true/false abd gap fill questions.',
							'sensei-lms'
						) }
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						checked={ allowRetakes }
						onChange={ createChangeHandler( 'allowRetakes' ) }
						label={ __( 'Allow Retakes', 'sensei-lms' ) }
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						checked={ randomQuestionOrder }
						onChange={ createChangeHandler(
							'randomQuestionOrder'
						) }
						label={ __( 'Random Question Order', 'sensei-lms' ) }
					/>
				</PanelRow>
				<PanelRow>
					<NumberControl
						id="sensei-quiz-settings-show-questions"
						label={ __( 'Number of Questions', 'sensei-lms' ) }
						help={ __(
							'Display a random selection of questions.',
							'sensei-lms'
						) }
						allowReset
						resetLabel={ __( 'All', 'sensei-lms' ) }
						min={ 0 }
						step={ 1 }
						value={ showQuestions }
						placeholder={ __( 'All', 'sensei-lms' ) }
						onChange={ createChangeHandler( 'showQuestions' ) }
					/>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	);
};

export default QuizSettings;
