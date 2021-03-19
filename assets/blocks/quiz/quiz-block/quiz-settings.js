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
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../../editor-components/number-control';
import { isQuestionEmpty } from '../data';

/**
 * Quiz settings.
 *
 * @param {Object}   props                    Block props.
 * @param {Object}   props.attributes         Block attributes
 * @param {Object}   props.attributes.options Current setting options.
 * @param {Function} props.setAttributes      Set attributes function.
 * @param {string}   props.clientId           Block ID.
 */
const QuizSettings = ( {
	attributes: { options = {} },
	setAttributes,
	clientId,
} ) => {
	const {
		passRequired = false,
		quizPassmark = 100,
		autoGrade = true,
		allowRetakes = true,
		randomQuestionOrder = false,
		showQuestions = null,
	} = options;

	const createChangeHandler = ( optionKey ) => ( value ) =>
		setAttributes( { options: { ...options, [ optionKey ]: value } } );

	const questions = useSelect(
		( select ) =>
			select( 'core/block-editor' )
				.getBlock( clientId )
				.innerBlocks.filter(
					( questionBlock ) =>
						! isQuestionEmpty( questionBlock.attributes )
				),
		[ clientId ]
	);

	const questionCount = questions.reduce(
		( count, question ) =>
			count +
			( question.attributes.type === 'category-question'
				? question.attributes.options.number
				: 1 ),
		0
	);

	useEffect( () => {
		if ( showQuestions > questionCount ) {
			setAttributes( {
				options: { ...options, showQuestions: questionCount },
			} );
		}
	}, [ options, questionCount, setAttributes, showQuestions ] );

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
						label={ __( 'Pass Required', 'sensei-lms' ) }
					/>
				</PanelRow>
				{ passRequired && (
					<PanelRow>
						<RangeControl
							label={ 'Passing Grade (%)' }
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
							'Automatically grade Multiple Choice, True/False and Gap Fill questions that have a non-zero point value.',
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
						max={ questionCount }
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
