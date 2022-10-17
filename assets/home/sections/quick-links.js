/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../section';
import { Grid, Col } from '../grid';
import Link from '../link';

const quickLinksSpecialMapping = {
	'sensei://install-demo-course': ( { setTitle, originalTitle, timer } ) => {
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
	},
};

/**
 * Component that shows a Quick Link.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.item The item to show.
 */
const QuickLink = ( { item } ) => {
	let { url, title: originalTitle } = item;
	const [ title, setTitle ] = useState( originalTitle );
	const timer = useRef( 0 );

	let onClick = null;

	const callback = quickLinksSpecialMapping[ url ];
	if ( !! callback ) {
		url = '#';
		onClick = ( e ) => {
			e.preventDefault();
			callback( { setTitle, originalTitle, timer } );
		};
	}
	return (
		<li>
			<Link url={ url } onClick={ onClick } label={ title } />
		</li>
	);
};

/**
 * A column on the Quick Links section.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.data The data related to the column of quick links.
 */
const QuickLinksColumn = ( { data } ) => (
	<>
		<h3 className="sensei-home__quick-links__title">{ data.title }</h3>
		<ul>
			{ data.items.map( ( item ) => (
				<QuickLink key={ item.url } item={ item }></QuickLink>
			) ) }
		</ul>
	</>
);

/**
 * Returns an array with the column size of a given set of columns, distributed over a given total.
 *
 * @param {number} columnCount The number of columns to return the size for.
 * @param {number} columnTotal The total size of the columns. By default, it's 12.
 * @return {number[]} An array containing the sizes of each column.
 */
const getColumnsSize = ( columnCount, columnTotal = 12 ) => {
	const columnCountRounded = Math.floor(
		columnCount ? columnTotal / columnCount : 0
	);
	const columns = new Array( columnCount ).fill( columnCountRounded );
	columns[ columns.length - 1 ] += columnTotal % columnCountRounded;
	return columns;
};

/**
 * Quick Links section component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.quickLinks The links to show on the Quick Links section.
 */
const QuickLinks = ( { quickLinks } ) => {
	const columns = getColumnsSize( quickLinks?.length ?? 0 );
	return (
		<Section
			title={ __( 'Quick Links', 'sensei-lms' ) }
			className="sensei-home__quick-links"
		>
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
