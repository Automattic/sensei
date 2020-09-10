import { InnerBlocks } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { useSelect, withSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { CourseOutlinePlaceholder } from './placeholder';

import { useBlocksCreator } from './use-block-creator';

/**
 * Edit course outline block component.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.clientId  Block client ID.
 * @param {string}   props.className Custom class name.
 * @param {Object[]} props.blocks    Course module and lesson blocks
 */
const EditCourseOutlineBlock = ( { clientId, className, blocks } ) => {
	const { setBlocks } = useBlocksCreator( clientId );

	useEffect( () => {
		if ( blocks && blocks.length ) {
			setBlocks( blocks );
		}
	}, [ blocks, setBlocks ] );

	const isEmpty = useSelect(
		( select ) =>
			! select( 'core/block-editor' ).getBlocks( clientId ).length,
		[ clientId, blocks ]
	);

	if ( isEmpty ) {
		return (
			<CourseOutlinePlaceholder
				addBlock={ ( type ) => setBlocks( [ { type } ] ) }
			/>
		);
	}
	return (
		<section className={ className }>
			<InnerBlocks
				allowedBlocks={ [
					'sensei-lms/course-outline-module',
					'sensei-lms/course-outline-lesson',
				] }
			/>
		</section>
	);
};

export default compose(
	withSelect( () => {
		return {
			blocks: [],
		};
	} )
)( EditCourseOutlineBlock );
