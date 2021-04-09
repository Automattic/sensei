/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const Header = () => (
	<header>
		<h1 className="wp-heading-inline">
			{ __( 'Sensei LMS Extensions', 'sensei-lms' ) }
		</h1>
	</header>
);

export default Header;
