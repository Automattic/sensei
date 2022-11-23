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
	/**
	 * Shows the ConfirmDialog component and returns a boolean with the result asynchronously.
	 *
	 * @param {string} text                      Text of the Confirm Dialog.
	 * @param {Object} newProps                  Additional properties to use on the ConfirmDialog.
	 * @param {string} newProps.title            Title of the Confirm Dialog.
	 * @param {string} newProps.cancelButtonText Text of the Cancel button on the Confirm Dialog.
	 * @param {string} newProps.okButtonText     Text of the Ok button on the Confirm Dialog.
	 * @return {Promise<boolean>} true if the user clicked the OK button or pressed Enter. false if the user clicked the
	 * 								Cancel button or pressed ESC.
	 */
	const confirm = ( text, newProps = {} ) => {
		return new Promise( ( resolve ) => {
			const callback = ( value ) => () => {
				resolve( value );
				setProps( { isOpen: false } );
			};
			setProps( {
				...newProps,
				isOpen: true,
				children: text,
				onConfirm: callback( true ),
				onCancel: callback( false ),
			} );
		} );
	};
	return [ props, confirm ];
};

export default useConfirmDialogProps;
