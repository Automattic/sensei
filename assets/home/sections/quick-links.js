/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, external } from '@wordpress/icons';
import { useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../section';
import { Grid, Col } from '../grid';

const QuickLink = ( { item } ) => {
	let { url, title: originalTitle } = item;
	const [ title, setTitle ] = useState( originalTitle );
	const timer = useRef( 0 );

	let onClick = null;
	if ( 'sensei://install-demo-course' === url ) {
		url = '#';
		onClick = ( e ) => {
			e.preventDefault();
			setTitle( 'Installing' );
			const installedMessage = 'Installed!';
			const clear = () => {
				if ( timer.current ) {
					clearTimeout( timer.current );
				}
				timer.current = 0;
			};
			const run = () => {
				setTitle( ( oldTitle ) => {
					if ( installedMessage === oldTitle ) {
						clear();
						return originalTitle;
					}
					if ( ! oldTitle.includes( '...' ) ) {
						timer.current = setTimeout( run, 500 );
						return oldTitle + '.';
					}
					timer.current = setTimeout( run, 2000 );
					return installedMessage;
				} );
			};
			clear();
			timer.current = setTimeout( run, 500 );
		};
	}
	// TODO: improve check to verify if a URL is external or not
	const isExternal = url && url !== '#' && ! url.includes( '/wp-admin/' );
	return (
		<li>
			{ /* eslint-disable-next-line react/jsx-no-target-blank */ }
			<a
				href={ url }
				target={ isExternal ? '_blank' : null }
				onClick={ onClick }
			>
				{ title }
				{ isExternal ? (
					<Icon icon={ external } size={ 16 } fill="currentColor" />
				) : null }
			</a>
		</li>
	);
};

const QuickLinksColumn = ( { data } ) => (
	<ul>
		<li>
			<b> { data.title }</b>
		</li>
		{ data.items.map( ( item ) => (
			<QuickLink key={ item.url } item={ item }></QuickLink>
		) ) }
	</ul>
);

/**
 * Quick Links section component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.quickLinks The links to show on the Quick Links section.
 */
const QuickLinks = ( { quickLinks } ) => {
	const columnCount = Math.floor( 12 / quickLinks.length );
	const columns = new Array( quickLinks.length ).fill( columnCount );
	columns[ columns.length - 1 ] += 12 % quickLinks.length;
	return (
		<Section title={ __( 'Quick Links', 'sensei-lms' ) }>
			<Grid>
				{ columns.map( ( cols, index ) => (
					<Col cols={ cols } key={ quickLinks[ index ].title }>
						<QuickLinksColumn data={ quickLinks[ index ] } />
					</Col>
				) ) }
			</Grid>
		</Section>
	);
};

export default QuickLinks;
