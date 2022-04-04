/**
 * WordPress dependencies
 */
import { Toolbar } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ToolbarDropdown from '../../editor-components/toolbar-dropdown';

/**
 * Question type selector toolbar control.
 *
 * @param {Object}   props
 * @param {string}   props.value    Selected type.
 * @param {Function} props.onSelect Selection callback.
 * @param {Array}    props.options  Question options.
 */
export const QuestionTypeToolbar = ( { value, onSelect, options } ) => {
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
				getMenuItemProps={ ( option ) => {
					let children = (
						<div>
							<strong> { option.title }</strong>
							<div className="sensei-lms-question-block__type-selector__option__description">
								{ option.description }
							</div>
						</div>
					);
					/**
					 * Filters the children of the menu item.
					 *
					 * @since 4.1.0
					 *
					 * @param {JSX.Element} children Menu element children.
					 * @param {Object}      option   The question type option.
					 * @return {JSX.Element} Retuns the filtered children for the menu item.
					 */
					children = applyFilters(
						'senseiQuestionTypeToolbarOptionChildren',
						children,
						option
					);

					const props = {};
					props.children = children;
					if ( option.disabled ) {
						props.onClick = () => {};
					}

					return props;
				} }
			/>
		</Toolbar>
	);
};
