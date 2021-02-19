/**
 * WordPress dependencies
 */
import { Button, ToolbarGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';

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
				<div className="sensei-lms-question-block__grade-toolbar__wrapper">
					<div>
						<input
							type="number"
							min="0"
							step="1"
							value={ value }
							onChange={ ( e ) => onChange( +e.target.value ) }
							title={ __( 'Question grade', 'sensei-lms' ) }
						/>
					</div>

					<div className="sensei-lms-question-block__grade-toolbar__steppers">
						<Button
							onClick={ () => onChange( value + 1 ) }
							className="sensei-lms-question-block__grade-toolbar__stepper is-up-button"
							icon={ chevronUp }
							label={ __(
								'Increase question grade',
								'sensei-lms'
							) }
						/>
						<Button
							onClick={ () =>
								onChange( Math.max( 0, value - 1 ) )
							}
							className="sensei-lms-question-block__grade-toolbar__stepper is-down-button"
							icon={ chevronDown }
							label={ __(
								'Decrease question grade',
								'sensei-lms'
							) }
						/>
					</div>
				</div>
			</ToolbarGroup>
		</>
	);
};
