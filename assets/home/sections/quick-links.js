/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../section';
import { Grid, Col } from '../grid';
import Link from '../link';

/**
 * Component to Install Demo Course. Invoked when clicking on a link pointing to "sensei://install-demo-course".
 *
 * @param {Object}   props        Component props.
 * @param {Function} props.remove Function to call to remove the item from the Quick Links Column.
 * @return {string} A message to the user with the status of the installation.
 */
function InstallDemoCourse( { remove } ) {
	const [ title, setTitle ] = useState( 'Installing' );
	const timer = useRef( 0 );
	const installedMessage = 'Installed!';
	const clear = () => {
		if ( timer.current ) {
			clearTimeout( timer.current );
		}
		timer.current = 0;
	};
	useEffect( () => {
		if ( null === title ) {
			remove();
		}
	}, [ title, remove ] );
	useEffect( () => {
		const run = () => {
			setTitle( ( oldTitle ) => {
				if ( installedMessage === oldTitle ) {
					clear();
					return null;
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
	}, [] );
	return title;
}

const quickLinksSpecialMapping = {
	'sensei://install-demo-course': InstallDemoCourse,
};

/**
 * Component that shows a Quick Link.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.item The item to show.
 */
const QuickLink = ( { item } ) => {
	let { url, title } = item;

	let onClick = null;
	const [ showComponent, setShowComponent ] = useState( false );
	const [ remove, setRemove ] = useState( false );

	const Component = quickLinksSpecialMapping[ url ];
	if ( !! Component ) {
		url = '#';
		onClick = ( e ) => {
			e.preventDefault();
			setShowComponent( true );
		};
	}
	if ( remove ) {
		return null;
	}
	return (
		<li>
			{ showComponent ? (
				<Component
					restoreLink={ () => setShowComponent( false ) }
					remove={ () => setRemove( true ) }
				/>
			) : (
				<Link url={ url } onClick={ onClick } label={ title } />
			) }
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
