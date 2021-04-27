/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Extension actions component.
 *
 * @param {Object}   props            Component props.
 * @param {string}   props.extensions Extensions related to the component.
 * @param {Function} props.onClick    Action button callback.
 */
const MultipleExtensionsActions = ( {
	extensions = [],
	onClick = () => {},
} ) => {
	const disabledButton = false;

	return (
		<ul className="sensei-extensions__extension-actions">
			<li className="sensei-extensions__extension-actions__item">
				<button
					className="button button-primary"
					disabled={ disabledButton }
					onClick={ onClick }
				>
					{ __( 'Update all', 'sensei-lms' ) }
				</button>
			</li>
		</ul>
	);
};

export default MultipleExtensionsActions;
