/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { BaseControl, Icon } from '@wordpress/components';

/**
 * Input control component.
 *
 * It can use or be replaced by the
 * WordPress [InputControl]{@link https://github.com/WordPress/gutenberg/tree/master/packages/components/src/input-control} when it's stable.
 *
 * @param {Object}   props             Component props.
 * @param {string}   [props.className] Additional classnames for the input.
 * @param {string}   [props.id]        Component id used to connect label and input - required if label is set.
 * @param {string}   [props.label]     Input label.
 * @param {number}   [props.value]     Input value.
 * @param {string}   [props.help]      Help text.
 * @param {string}   [props.iconRight] Icon right.
 * @param {Function} [props.onChange]  Change function.
 */
const InputControl = ( {
	className,
	id,
	label,
	value,
	help,
	iconRight,
	onChange,
	...props
} ) => (
	<BaseControl id={ id } label={ label } help={ help }>
		<div className="sensei-input-control">
			<input
				className={ classnames(
					'sensei-input-control__input',
					{
						'sensei-input-control__input--with-icon-right': iconRight,
					},
					className
				) }
				type="text"
				id={ id }
				value={ null === value ? '' : value }
				onChange={ ( e ) => onChange( e.target.value ) }
				{ ...props }
			/>
			{ iconRight && (
				<span className="sensei-input-control__icon">
					<Icon icon={ iconRight } />
				</span>
			) }
		</div>
	</BaseControl>
);

export default InputControl;
