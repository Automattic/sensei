/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExtensionActions from '../extension-actions';

/**
 * Multiple update notification.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions with update.
 * @param {Array}  props.actions    Actions that can be taken.
 */
const Multiple = ( { extensions, actions } ) => (
	<>
		<ul className="sensei-extensions__update-notification__list">
			{ extensions.map( ( extension ) => (
				<li
					key={ extension.product_slug }
					className="sensei-extensions__update-notification__list__item"
				>
					{ extension.title }{ ' ' }
					{ extension.changelog_url && (
						<a
							href={ extension.changelog_url }
							className="sensei-extensions__update-notification__version-link"
							target="_blank"
							rel="noreferrer external"
						>
							{ sprintf(
								// translators: placeholder is the version number.
								__( 'version %s', 'sensei-lms' ),
								extension.version
							) }
						</a>
					) }
				</li>
			) ) }
		</ul>

		<ExtensionActions actions={ actions } />
	</>
);

export default Multiple;
