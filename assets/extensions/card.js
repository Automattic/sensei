/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExtensionActions from './extension-actions';

// prettier-ignore
// const extensionMock = {"hash":"d1a69640d53a32a9fb13e93d1c8f3104","title":"WooCommerce Paid Courses","image":null,"excerpt":"Sell your courses using the most popular eCommerce platform on the web \u2013 WooCommerce.","link":"https:\/\/senseilms.com\/product\/woocommerce-paid-courses\/","price":"$129.00","is_featured":false,"product_slug":"sensei-wc-paid-courses","hosted_location":"external","type":"plugin","plugin_file":"woothemes-sensei\/woothemes-sensei.php","wccom_product_id":"152116"};
// prettier-ignore
// const extensionMock = {"version":"5.0.0.0.0.0","hash":"d1a69640d53a32a9fb13e93d1c8f3104","title":"WooCommerce Paid Courses","image":null,"excerpt":"Sell your courses using the most popular eCommerce platform on the web \u2013 WooCommerce.","link":"https:\/\/senseilms.com\/product\/woocommerce-paid-courses\/","price":"$129.00","is_featured":false,"product_slug":"sensei-wc-paid-courses","hosted_location":"external","type":"plugin","plugin_file":"woothemes-sensei\/woothemes-sensei.php","wccom_product_id":"152116"};
// prettier-ignore
const extensionMock = {"version":"5.0.0.0.0.0","has_update":true,"hash":"d1a69640d53a32a9fb13e93d1c8f3104","title":"WooCommerce Paid Courses","image":null,"excerpt":"Sell your courses using the most popular eCommerce platform on the web \u2013 WooCommerce.","link":"https:\/\/senseilms.com\/product\/woocommerce-paid-courses\/","price":"$129.00","is_featured":false,"product_slug":"sensei-wc-paid-courses","hosted_location":"external","type":"plugin","plugin_file":"woothemes-sensei\/woothemes-sensei.php","wccom_product_id":"152116"};

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
			<p className="sensei-extensions__card__description">
				Lorem ipsum dolor sit amet, consectertur adipiscing elit. Enin
				cras odio netus mi. Maecenas
			</p>
		</div>
		<ExtensionActions detailsLink="#" />
	</article>
);

export default Card;
