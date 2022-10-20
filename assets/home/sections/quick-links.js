/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import Section from '../section';
import { Grid, Col } from '../grid';
import Link from '../link';
import InstallDemoCourse from '../install-demo-course';

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
				<Link
					url={ url }
					onClick={ onClick }
					label={ decodeEntities( title ) }
				/>
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
