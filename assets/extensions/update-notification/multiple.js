/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExtensionActions from '../extension-actions';
import { logEvent } from '../../shared/helpers/log-event';

/**
 * Multiple update notification.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions with update.
 */
const Multiple = ( { extensions } ) => (
	<>
		<ul className="sensei-extensions__update-notification__list">
			{ extensions.map( ( extension ) => (
				<li
					key={ extension.product_slug }
					className="sensei-extensions__update-notification__list__item"
				>
					{ extension.title }{ ' ' }
					<a
						href={ extension.link }
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
				</li>
			) ) }
		</ul>

		<ExtensionActions
			actions={ [
				{
					key: 'update-button',
					children: __( 'Update all', 'sensei-lms' ),
					onClick: () =>
						extensions.forEach( ( extension ) => {
							logEvent( 'extensions_update', {
								slug: extension.product_slug,
							} );
						} ),
				},
			] }
		/>
	</>
);

export default Multiple;
