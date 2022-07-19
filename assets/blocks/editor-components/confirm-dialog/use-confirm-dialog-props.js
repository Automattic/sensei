/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Hook that returns the props for the component ConfirmDialog, with an additional async function that mimics the
 * synchronous native confirm() API. Loosely inspired by react-confirm HOC.
 *
 * @see https://github.com/haradakunihiko/react-confirm
 * @return {Array} The first item is the props to pass to ConfirmDialog, the second one is the async function to call to
 * 					trigger ConfirmDialog.
 */
export const useConfirmDialogProps = () => {
	const [ props, setProps ] = useState( { isOpen: false } );
	const confirm = ( text, title, newProps = {} ) => {
		return new Promise( ( resolve ) => {
			const callback = ( value ) => () => {
				resolve( value );
				setProps( { isOpen: false } );
			};
			setProps( {
				...newProps,
				isOpen: true,
				children: text,
				title,
				onConfirm: callback( true ),
				onCancel: callback( false ),
			} );
		} );
	};
	return [ props, confirm ];
};

export default useConfirmDialogProps;
