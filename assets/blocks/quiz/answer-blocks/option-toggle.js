/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { checked } from '../../../icons/wordpress-icons';

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
			{ isChecked && isCheckbox && checked }
		</div>
		{ children }
	</div>
);
