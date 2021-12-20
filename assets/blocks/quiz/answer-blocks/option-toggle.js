/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { check } from '@wordpress/icons';

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
			{ isChecked && isCheckbox && check }
		</div>
		{ children }
	</div>
);
