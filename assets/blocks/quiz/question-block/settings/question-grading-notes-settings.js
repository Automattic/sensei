/**
 * WordPress dependencies
 */
import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Question block grading notes settings control.
 *
 * @param {Object}   props
 * @param {Object}   props.options
 * @param {string}   props.options.teacherNotes Notes for teacher when grading.
 * @param {Function} props.setOptions
 */
const QuestionGradingNotesSettings = ( {
	options: { teacherNotes },
	setOptions,
} ) => (
	<TextareaControl
		label={ __( 'Grading Notes', 'sensei-lms' ) }
		onChange={ ( value ) => setOptions( { teacherNotes: value } ) }
		value={ teacherNotes || '' }
		help={ __(
			'Displayed to the teacher when grading the question.',
			'sensei-lms'
		) }
	/>
);

export default QuestionGradingNotesSettings;
