/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { CheckboxControl } from '@wordpress/components';

/**
 * Purpose Item component.
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.label    Item label.
 * @param {boolean}  props.checked  Whether it's checked.
 * @param {Function} props.onToggle Toggle callback.
 * @param {Object}   props.children Component children, which is displayed when it's checked.
 */
const PurposeItem = ( { label, checked, onToggle, children } ) => (
	<li
		className={ classnames( 'sensei-setup-wizard__purpose-item', {
			'sensei-setup-wizard__purpose-item--checked': checked,
		} ) }
	>
		<CheckboxControl
			className="sensei-setup-wizard__checkbox"
			label={ label }
			checked={ checked }
			onChange={ onToggle }
		/>

		{ checked && children && (
			<small className="sensei-setup-wizard__purpose-children">
				{ children }
			</small>
		) }
	</li>
);

export default PurposeItem;
