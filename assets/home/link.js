/**
 * WordPress dependencies
 */
import { external, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { addUtms, isUrlExternal } from './utils';

/**
 * Link component. Will add an external link icon if the url is for an external domain.
 *
 * @param {Object}   props         Component props.
 * @param {string}   props.label   The label for the link.
 * @param {string}   props.url     The target URL.
 * @param {Function} props.onClick The event listener for the click event.
 * @param {Object}   props.dataSet Data attributes to add to the link.
 */
const Link = ( { label, url, onClick, dataSet } ) => {
	const isExternal = isUrlExternal( url );
	const linkProps = {
		href: addUtms( url ),
		target: ! isExternal ? undefined : '_blank',
		rel: isExternal ? 'noreferrer' : undefined,
		onClick,
	};
	if ( !! dataSet ) {
		for ( const [ key, value ] of Object.entries( dataSet ) ) {
			linkProps[ 'data-' + key ] = value;
		}
	}

	return (
		<div className="sensei-home__link">
			<a { ...linkProps }>
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
