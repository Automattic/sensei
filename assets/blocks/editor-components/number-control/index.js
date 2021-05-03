/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { BaseControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Number control component.
 *
 * @param {Object}   props                    Component props.
 * @param {string}   [props.className]        Additional classnames for the input.
 * @param {string}   [props.id]               Component id used to connect label and input - required if label is set.
 * @param {string}   [props.label]            Input label.
 * @param {number}   [props.value]            Input value.
 * @param {string}   [props.help]             Help text.
 * @param {boolean}  [props.allowReset=false] Whether reset is allowed.
 * @param {string}   [props.resetLabel]       Reset button custom label.
 * @param {Function} props.onChange           Change function, which receives number as argument.
 * @param {string}   props.suffix             Input suffix.
 */
const NumberControl = ( {
	className,
	id,
	label,
	value,
	help,
	allowReset = false,
	resetLabel,
	onChange,
	suffix,
	...props
} ) => {
	const setValue = ( e ) => {
		onChange( parseInt( e.target.value, 10 ) || props.min || 0 );
	};

	return (
		<BaseControl id={ id } label={ label } help={ help }>
			<div className="sensei-number-control">
				<div className="sensei-number-control__input-container">
					<input
						className={ classnames(
							'sensei-number-control__input components-text-control__input',
							className
						) }
						type="number"
						id={ id }
						onChange={ setValue }
						value={ null === value ? '' : value }
						{ ...props }
					/>
					{ suffix && (
						<span className="sensei-number-control__input-suffix">
							{ suffix }
						</span>
					) }
				</div>
				{ allowReset && (
					<Button
						className="sensei-number-control__button"
						isSmall
						isSecondary
						onClick={ () => onChange( null ) }
					>
						{ resetLabel || __( 'Reset', 'sensei-lms' ) }
					</Button>
				) }
			</div>
		</BaseControl>
	);
};

export default NumberControl;
