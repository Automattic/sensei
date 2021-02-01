/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	Button,
	Dropdown,
	MenuGroup,
	MenuItem,
	NavigableMenu,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { checked } from '../../../icons/wordpress-icons';

/**
 * @typedef {Object} DropdownOption
 *
 * @property {string} label Option label.
 * @property {string} value Option value.
 */
/**
 * Dropdown for the editor toolbar.
 *
 * @param {Object}           props
 * @param {DropdownOption[]} props.options          Dropdown options.
 * @param {string}           [props.optionsLabel]   Options label.
 * @param {Object}           props.icon             Icon for the toolbar.
 * @param {string}           props.value            Current dropdown value.
 * @param {Function}         props.onChange         Dropdown change callback, which receive the new value as argument.
 * @param {Object}           props.toggleProps      Props passed to the toggle element.
 * @param {Object}           props.popoverProps     Props passed to the popover component.
 * @param {Function}         props.getMenuItemProps Render function for a menu item. Should return a props object.
 */
const ToolbarDropdown = ( {
	options,
	optionsLabel,
	icon,
	value,
	onChange,
	toggleProps,
	getMenuItemProps,
	popoverProps,
	...props
} ) => {
	const selectedOption = options.find( ( option ) => value === option.value );

	return (
		<Dropdown
			className="sensei-toolbar-dropdown"
			popoverProps={ {
				isAlternate: true,
				position: 'bottom right left',
				focusOnMount: true,
				...popoverProps,
				className: classnames(
					popoverProps?.className,
					'sensei-toolbar-dropdown__popover'
				),
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					onClick={ onToggle }
					icon={ icon }
					aria-expanded={ isOpen }
					aria-haspopup="true"
					{ ...toggleProps }
					children={
						toggleProps?.children
							? toggleProps.children( selectedOption )
							: selectedOption?.label
					}
				/>
			) }
			renderContent={ ( { onClose } ) => (
				<NavigableMenu role="menu" stopNavigationEvents>
					<MenuGroup label={ optionsLabel }>
						{ options.map( ( option ) => {
							const isSelected =
								option.value === selectedOption?.value;
							const menuItemProps = getMenuItemProps?.( option );
							return (
								<MenuItem
									key={ option.value }
									role="menuitemradio"
									isSelected={ isSelected }
									icon={ isSelected ? checked : null }
									className={ classnames(
										'sensei-toolbar-dropdown__option',
										{ 'is-selected': isSelected },
										menuItemProps?.className
									) }
									onClick={ () => {
										onChange( option.value );
										onClose();
									} }
									children={ option.label }
									{ ...menuItemProps }
								/>
							);
						} ) }
					</MenuGroup>
				</NavigableMenu>
			) }
			{ ...props }
		/>
	);
};

export default ToolbarDropdown;
