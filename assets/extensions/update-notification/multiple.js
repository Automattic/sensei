/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MultipleExtensionsActions from '../multiple-extensions-actions';

/**
 * Multiple update notification.
 *
 * @param {Object}   props            Component props.
 * @param {Array}    props.extensions Extensions with update.
 * @param {Function} props.onClick    Callback which is called when the button is clicked.
 */
const Multiple = ( { extensions, onClick } ) => (
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
		<MultipleExtensionsActions
			extensions={ extensions }
			onClick={ onClick }
		/>
	</>
);

export default Multiple;
