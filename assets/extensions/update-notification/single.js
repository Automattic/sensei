/**
 * Internal dependencies
 */
import ExtensionActions, { getExtensionActions } from '../extension-actions';
/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { EXTENSIONS_STORE } from '../store';

/**
 * Single update notification.
 *
 * @param {Object} props           Component props.
 * @param {Object} props.extension Extension with update.
 */
const Single = ( { extension } ) => {
	const componentInProgress = useSelect( ( select ) =>
		select( EXTENSIONS_STORE ).getComponentInProgress()
	);

	return (
		<>
			<h3 className="sensei-extensions__update-notification__title">
				{ extension.title }
			</h3>
			<p className="sensei-extensions__update-notification__description">
				{ extension.excerpt }
			</p>
			<ExtensionActions
				actions={ getExtensionActions(
					extension,
					componentInProgress
				) }
			/>
		</>
	);
};

export default Single;
