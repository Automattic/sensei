/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExtensionActions from './extension-actions';

/**
 * Extensions card component.
 *
 * @param {Object}  props           Component props.
 * @param {boolean} props.extension Extension object.
 */
const Card = ( { extension } ) => (
	<article className="sensei-extensions__card">
		<header className="sensei-extensions__card__header">
			<h3 className="sensei-extensions__card__title">
				{ extension.title }
			</h3>
			{ extension.has_update && (
				<small className="sensei-extensions__card__new-badge">
					{ __( 'New version', 'sensei-lms' ) }
				</small>
			) }
		</header>
		<div className="sensei-extensions__card__content">
			<p className="sensei-extensions__card__description">
				{ extension.excerpt }
			</p>
			<ExtensionActions extension={ [ extension ] } />
		</div>
	</article>
);

export default Card;
