/**
 * WordPress dependencies
 */
import { BaseControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Number control component.
 *
 * It can use or be replaced by the
 * WordPress [NumberControl]{@link https://github.com/WordPress/gutenberg/tree/master/packages/components/src/number-control} when it's stable.
 *
 * @param {Object}   props                    Component props.
 * @param {string}   [props.id]               Component id used to connect label and input - required if label is set.
 * @param {string}   [props.label]            Input label.
 * @param {number}   [props.value]            Input value.
 * @param {string}   [props.help]             Help text.
 * @param {boolean}  [props.allowReset=false] Whether reset is allowed.
 * @param {string}   [props.resetLabel]       Reset button custom label.
 * @param {Function} props.onChange           Change function, which receives number as argument.
 */
const NumberControl = ( {
	id,
	label,
	value,
	help,
	allowReset = false,
	resetLabel,
	onChange,
	...props
} ) => (
	<BaseControl id={ id } label={ label } help={ help }>
		<input
			type="number"
			id={ id }
			onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) ) }
			value={ null === value ? '' : value }
			{ ...props }
		/>
		{ allowReset && (
			<Button isSmall isSecondary onClick={ () => onChange( null ) }>
				{ resetLabel || __( 'Reset', 'sensei-lms' ) }
			</Button>
		) }
	</BaseControl>
);

export default NumberControl;
