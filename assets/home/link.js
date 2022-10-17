/**
 * WordPress dependencies
 */
import { external, Icon } from '@wordpress/icons';

/**
 * Return hostname for a given URL.
 *
 * @param {string} url URL to parse.
 * @return {string} The hostname for the given URL.
 */
const getHostname = ( url ) => {
	const element = document.createElement( 'a' );
	element.href = url;
	return element.hostname;
};

/**
 * Link component. Will add an external link icon if the url is for an external domain.
 *
 * @param {Object} props       Component props.
 * @param {Array}  props.label The label for the link.
 * @param {Array}  props.url   The target URL.
 */
const Link = ( { label, url } ) => {
	const isExternal = getHostname( window.location ) !== getHostname( url );

	return (
		<div className="sensei-home__link">
			<a href={ url } target="_blank" rel="noreferrer">
				{ label }
				{ isExternal && (
					<Icon
						icon={ external }
						className="sensei-home__link__external-icon"
					/>
				) }
			</a>
		</div>
	);
};

export default Link;
