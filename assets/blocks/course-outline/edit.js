import { InnerBlocks } from '@wordpress/block-editor';
import { useDispatch, useSelect, withSelect } from '@wordpress/data';
import { useCallback, useEffect } from '@wordpress/element';
import { extractStructure, getChildBlockAttributes } from './data';
import { CourseOutlinePlaceholder } from './placeholder';
import { COURSE_STORE } from './store';
import { useBlocksCreator } from './use-block-creator';
import { useSavePost } from './use-save-post';

/**
 * Edit course outline block component.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.clientId      Block client ID.
 * @param {string}   props.className     Custom class name.
 * @param {Object[]} props.structure     Course module and lesson blocks
 * @param {Function} props.setAttributes
 */
const EditCourseOutlineBlock = ( {
	clientId,
	className,
	structure,
	setAttributes,
} ) => {
	const { setBlocks } = useBlocksCreator( clientId );

	const { getBlocks } = useSelect(
		( select ) => select( 'core/block-editor' ),
		[]
	);

	const { save: saveStructure } = useDispatch( COURSE_STORE );

	const saveChildAttributes = useCallback( () => {
		const childBlockAttributes = getChildBlockAttributes(
			extractStructure( getBlocks( clientId ) )
		);
		setAttributes( {
			blocks: childBlockAttributes,
		} );
	}, [ setAttributes, clientId, getBlocks ] );

	useSavePost( saveChildAttributes );
	useSavePost( saveStructure );

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
