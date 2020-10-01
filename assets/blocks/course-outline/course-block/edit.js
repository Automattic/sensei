import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { createContext, useEffect } from '@wordpress/element';

import { CourseOutlinePlaceholder } from './placeholder';
import { COURSE_STORE } from '../store';
import { useBlocksCreator } from '../use-block-creator';
import { OutlineBlockSettings } from './settings';

/**
 * A React context which contains the attributes and the setAttributes callback of the Outline block.
 */
export const OutlineAttributesContext = createContext();

/**
 * Edit course outline block component.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.clientId      Block client ID.
 * @param {string}   props.className     Custom class name.
 * @param {Object[]} props.structure     Course module and lesson blocks.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block setAttributes callback.
 */
const EditCourseOutlineBlock = ( {
	clientId,
	className,
	structure,
	attributes,
	setAttributes,
} ) => {
	// Toggle legacy metaboxes.
	useEffect( () => {
		window.sensei_toggleLegacyMetaboxes( false );

		return () => {
			window.sensei_toggleLegacyMetaboxes( true );
		};
	}, [] );

	const { setBlocks } = useBlocksCreator( clientId );

	/**
	 * Handle update animationsEnabled setting.
	 *
	 * @param {boolean} value Value of the setting.
	 */
	const updateAnimationsEnabled = ( value ) => {
		setAttributes( { animationsEnabled: value } );
	};

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
		<>
			<OutlineBlockSettings
				animationsEnabled={ attributes.animationsEnabled }
				setAnimationsEnabled={ updateAnimationsEnabled }
			/>

			<section className={ className }>
				<OutlineAttributesContext.Provider
					value={ {
						outlineAttributes: attributes,
						outlineSetAttributes: setAttributes,
					} }
				>
					<InnerBlocks
						allowedBlocks={ [
							'sensei-lms/course-outline-module',
							'sensei-lms/course-outline-lesson',
						] }
					/>
				</OutlineAttributesContext.Provider>
			</section>
		</>
	);
};

const selectors = ( select ) => ( {
	structure: select( COURSE_STORE ).getStructure(),
} );

export default compose( withSelect( selectors ) )( EditCourseOutlineBlock );
