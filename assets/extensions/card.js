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
 * @param {boolean} props.hasUpdate Whether extensions has update.
 */
const Card = ( { hasUpdate } ) => (
	<article className="sensei-extensions__card">
		<div>
			<header className="sensei-extensions__card__header">
				<h3 className="sensei-extensions__card__title">
					Advanced quizzes
				</h3>
				{ hasUpdate && (
					<small className="sensei-extensions__card__new-badge">
						{ __( 'New version', 'sensei-lms' ) }
					</small>
				) }
			</header>
			<strong className="sensei-extensions__card__price">$ 29</strong>
			<p className="sensei-extensions__card__description">
				Lorem ipsum dolor sit amet, consectertur adipiscing elit. Enin
				cras odio netus mi. Maecenas
			</p>
		</div>
		<ExtensionActions detailsLink="#" />
	</article>
);

export default Card;
