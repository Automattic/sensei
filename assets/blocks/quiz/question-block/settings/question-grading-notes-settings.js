import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Question block grading notes settings control.
 *
 * @param {Object}   props
 * @param {Object}   props.options
 * @param {string}   props.options.gradingNotes Notes for teacher when grading.
 * @param {Function} props.setOptions
 */
const QuestionGradingNotesSettings = ( {
	options: { gradingNotes },
	setOptions,
} ) => (
	<TextareaControl
		label={ __( 'Grading Notes', 'sensei-lms' ) }
		onChange={ ( value ) => setOptions( { gradingNotes: value } ) }
		value={ gradingNotes }
		help={ __(
			'Displayed to the teacher when grading the question.',
			'sensei-lms'
		) }
	/>
);

export default QuestionGradingNotesSettings;
