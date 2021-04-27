/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { EXTENSIONS_STORE } from './store';
import { checked } from '../icons/wordpress-icons';
import { UpdateIcon } from '../icons';

/**
 * Extension actions component.
 *
 * @param {Object}   props            Component props.
 * @param {string}   props.extensions Extensions related to the component.
 * @param {Function} props.onClick    Callback which is called when the button is clicked.
 */
const MultipleExtensionsActions = ( { extensions, onClick = () => {} } ) => {
	const operationInProgress = useSelect(
		( select ) => select( EXTENSIONS_STORE ).getOperationInProgress(),
		[]
	);
	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	// A flag that is true if the user started an update.
	const [ hasUpdated, setHasUpdated ] = useState( false );

	let buttonLabel = __( 'Update All', 'sensei-lms' );

	if ( hasUpdated ) {
		if ( operationInProgress ) {
			buttonLabel = (
				<>
					<UpdateIcon
						width="20"
						height="20"
						className="sensei-extensions__rotating-icon sensei-extensions__extension-actions__button-icon"
					/>
					{ __( 'Updating…', 'sensei-lms' ) }
				</>
			);
		} else {
			buttonLabel = (
				<>
					<Icon
						className="sensei-extensions__extension-actions__button-icon"
						icon={ checked }
						size={ 14 }
					/>{ ' ' }
					{ __( 'Installed', 'sensei-lms' ) }
				</>
			);
		}
	}

	return (
		<ul className="sensei-extensions__extension-actions">
			<li className="sensei-extensions__extension-actions__item">
				<button
					className="button button-primary"
					disabled={ operationInProgress || hasUpdated }
					onClick={ () => {
						updateExtensions( extensions );
						onClick();
						setHasUpdated( true );
					} }
				>
					{ buttonLabel }
				</button>
			</li>
		</ul>
	);
};

export default MultipleExtensionsActions;
