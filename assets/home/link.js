/**
 * WordPress dependencies
 */
import { external, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { addUtms } from './utils';

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
 * @param {Object}   props         Component props.
 * @param {string}   props.label   The label for the link.
 * @param {string}   props.url     The target URL.
 * @param {Function} props.onClick The event listener for the click event.
 */
const Link = ( { label, url, onClick } ) => {
	const isExternal = getHostname( window.location ) !== getHostname( url );

	return (
		<div className="sensei-home__link">
			<a
				href={ addUtms( url ) }
				target={ onClick || ! isExternal ? undefined : '_blank' }
				rel="noreferrer"
				onClick={ onClick }
			>
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
