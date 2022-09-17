/**
 * Internal dependencies
 */
import ExtensionActions, { useExtensionActions } from '../extension-actions';

/**
 * Single update notification.
 *
 * @param {Object} props           Component props.
 * @param {Object} props.extension Extension with update.
 */
const Single = ( { extension } ) => {
	const actions = useExtensionActions( extension );

	return (
		<>
			<h3 className="sensei-extensions__update-notification__title">
				{ extension.title }
			</h3>
			<p className="sensei-extensions__update-notification__description">
				{ extension.excerpt }
			</p>
			<ExtensionActions actions={ actions } />
		</>
	);
};

export default Single;
