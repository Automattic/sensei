/**
 * WordPress dependencies
 */
import { Toolbar } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ToolbarDropdown from '../../editor-components/toolbar-dropdown';
import types from '../answer-blocks';

const options = Object.entries( types ).map( ( [ value, settings ] ) => ( {
	...settings,
	label: settings.title,
	value,
} ) );

/**
 * Question type selector toolbar control.
 *
 * @param {Object}   props
 * @param {string}   props.value    Selected type.
 * @param {Function} props.onSelect Selection callback.
 */
export const QuestionTypeToolbar = ( { value, onSelect } ) => {
	return (
		<Toolbar className="sensei-lms-question-block__type-selector__toolbar">
			<ToolbarDropdown
				className="sensei-lms-question-block__type-selector"
				label={ __( 'Question Type', 'sensei-lms' ) }
				options={ options }
				value={ value }
				onChange={ ( nextValue ) => onSelect( nextValue ) }
				optionsLabel={ __( 'Question Type', 'sensei-lms' ) }
				popoverProps={ {
					className:
						'sensei-lms-question-block__type-selector__popover',
				} }
				toggleProps={ {
					children: ( selectedOption ) => (
						<b>{ selectedOption?.title }</b>
					),
				} }
				getMenuItemProps={ ( option ) => ( {
					children: (
						<div>
							<strong> { option.title }</strong>
							<div className="sensei-lms-question-block__type-selector__option__description">
								{ option.description }
							</div>
						</div>
					),
				} ) }
			/>
		</Toolbar>
	);
};
