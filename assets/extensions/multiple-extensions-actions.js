/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EXTENSIONS_STORE } from './store';

/**
 * Extension actions component.
 *
 * @param {Object} props            Component props.
 * @param {string} props.extensions Extensions related to the component.
 */
const MultipleExtensionsActions = ( { extensions } ) => {
	const disabledButton = useSelect(
		( select ) => select( EXTENSIONS_STORE ).getButtonsDisabled(),
		[]
	);
	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	return (
		<ul className="sensei-extensions__extension-actions">
			<li className="sensei-extensions__extension-actions__item">
				<button
					className="button button-primary"
					disabled={ disabledButton }
					onClick={ () => updateExtensions( extensions ) }
				>
					{ __( 'Update all', 'sensei-lms' ) }
				</button>
			</li>
		</ul>
	);
};

export default MultipleExtensionsActions;
