/**
 * WordPress dependencies
 */
import { ToolbarGroup } from '@wordpress/components';
/**
 * Internal dependencies
 */
import { QuestionGradeControl } from './question-grade-control';

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
				<QuestionGradeControl value={ value } onChange={ onChange } />
			</ToolbarGroup>
		</>
	);
};
