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
 * @typedef {Object} DropdownOption
 *
 * @property {string} label Option label.
 * @property {string} value Option value.
 */
/**
 * Dropdown for the editor toolbar.
 *
 * @param {Object}           props
 * @param {DropdownOption[]} props.options        Dropdown options.
 * @param {string}           [props.optionsLabel] Options label.
 * @param {Object}           props.icon           Icon for the toolbar.
 * @param {string}           props.value          Current dropdown value.
 * @param {Function}         props.onChange       Dropdown change callback, which receive
 *                                                the new value as argument.
 */
const ToolbarDropdown = ( {
	options,
	optionsLabel,
	icon,
	value,
	onChange,
} ) => {
	const selectedOption = options.find( ( option ) => value === option.value );

	return (
		<Dropdown
			popoverProps={ { isAlternate: true } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					onClick={ onToggle }
					icon={ icon }
					aria-expanded={ isOpen }
					aria-haspopup="true"
				>
					{ selectedOption.label }
				</Button>
			) }
			renderContent={ ( { onClose } ) => (
				<NavigableMenu role="menu" stopNavigationEvents>
					<MenuGroup label={ optionsLabel }>
						{ options.map( ( option ) => {
							const isSelected =
								option.value === selectedOption.value;

							return (
								<MenuItem
									key={ option.value }
									role="menuitemradio"
									isSelected={ isSelected }
									className={ classnames( {
										'sensei-toolbar-dropdown-item-checked': isSelected,
									} ) }
									onClick={ () => {
										onChange( option.value );
										onClose();
									} }
								>
									{ option.label }
								</MenuItem>
							);
						} ) }
					</MenuGroup>
				</NavigableMenu>
			) }
		/>
	);
};

export default ToolbarDropdown;
