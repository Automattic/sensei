/**
 * WordPress dependencies
 */
import { ToolbarGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Question grade toolbar control.
 *
 * @param {Object}   props
 * @param {number}   props.value    Grade value.
 * @param {Function} props.onChange Grade setter.
 */
export const QuestionGradeToolbar = ( { value, onChange } ) => {
	return (
		<>
			<ToolbarGroup className="sensei-lms-question-block__grade-toolbar">
				<input
					type="number"
					min="0"
					step="1"
					value={ value }
					onChange={ ( e ) => onChange( +e.target.value ) }
					title={ __( 'Question grade', 'sensei-lms' ) }
				/>
			</ToolbarGroup>
		</>
	);
};
