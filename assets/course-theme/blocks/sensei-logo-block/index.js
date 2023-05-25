/**
 * Internal dependencies
 */
import meta from './block.json';
import SenseiLogoTree from '../../../icons/sensei-logo-tree.svg';
import LogoTreeIcon from '../../../icons/logo-tree.svg';

/**
 * WordPress dependencies
 */
const Edit = () => {
	return (
		<a href="#pseudo-link-to-sensei-site">
			<SenseiLogoTree />
		</a>
	);
};

/**
 * Course Navigation block.
 */
export default {
	...meta,
	icon: {
		src: <LogoTreeIcon width="20" height="20" />,
		foreground: '#43AF99',
	},
	edit: Edit,
};
