/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { check } from '@wordpress/icons';

/**
 * Toggle component using divs.
 *
 * @param {Object}  props            Component props.
 * @param {string}  props.className  Class name for the wrapper.
 * @param {boolean} props.isChecked  Whether it's checked.
 * @param {boolean} props.isCheckbox Whether it's a checkbox.
 * @param {Object}  props.children   Component children.
 */
export const OptionToggle = ( {
	className,
	isChecked,
	isCheckbox,
	children,
	...props
} ) => (
	<div
		className={ classnames(
			'sensei-lms-question-block__option-toggle',
			className
		) }
		{ ...props }
	>
		<div
			className={ classnames(
				'sensei-lms-question-block__option-toggle__control',
				{ 'is-checked': isChecked, 'is-checkbox': isCheckbox }
			) }
		>
			{ isCheckbox && check }
		</div>
		{ children }
	</div>
);

/**
 * Toggle component using input.
 *
 * @param {Object} props      Component props.
 * @param {string} props.type Input toggle type (`checkbox` or `radio`).
 */
export const InputToggle = ( props ) => (
	<>
		<input
			className="sensei-lms-question-block__option-toggle-input"
			{ ...props }
		/>
		<OptionToggle isCheckbox={ props.type === 'checkbox' } />
	</>
);
