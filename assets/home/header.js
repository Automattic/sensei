/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import LogoTree from '../icons/logo-tree.svg';
import SenseiLogoType from '../icons/sensei-logo-type.svg';

const Header = () => (
	<header>
		<h1 className="wp-heading-inline sensei-home-title">
			<span className="screen-reader-text">
				{ __( 'Sensei', 'sensei-lms' ) }
			</span>
			<LogoTree className="sensei-home-title__logo" />
			<SenseiLogoType className="sensei-home-title__logotype" />
		</h1>
	</header>
);

export default Header;
