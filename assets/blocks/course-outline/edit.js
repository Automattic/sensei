import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, withSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { CourseOutlinePlaceholder } from './placeholder';
import { COURSE_STORE } from './store';
import { useBlocksCreator } from './use-block-creator';

/**
 * Edit course outline block component.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.clientId  Block client ID.
 * @param {string}   props.className Custom class name.
 * @param {Object[]} props.structure Course module and lesson blocks
 */
const EditCourseOutlineBlock = ( { clientId, className, structure } ) => {
	const { setBlocks } = useBlocksCreator( clientId );

	const isEmpty = useSelect(
		( select ) =>
			! select( 'core/block-editor' ).getBlocks( clientId ).length,
		[ clientId, structure ]
	);

	useEffect( () => {
		if ( structure && structure.length ) {
			setBlocks( structure );
		}
	}, [ structure, setBlocks ] );

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


export default withSelect( ( select ) => {
	return {
		structure: select( COURSE_STORE ).getStructure(),
	};
} )( EditCourseOutlineBlock );

