import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { createContext, useEffect } from '@wordpress/element';

import { CourseOutlinePlaceholder } from './placeholder';
import { COURSE_STORE } from '../store';
import { useBlocksCreator } from '../use-block-creator';
import { OutlineBlockSettings } from './settings';
import { __ } from '@wordpress/i18n';
import {
	withColorSettings,
	withDefaultBlockStyle,
} from '../../../shared/blocks/settings';

/**
 * A React context which contains the attributes and the setAttributes callback of the Outline block.
 */
export const OutlineAttributesContext = createContext();

/**
 * Edit course outline block component.
 *
 * @param {Object}   props                       Component props.
 * @param {string}   props.clientId              Block client ID.
 * @param {string}   props.className             Custom class name.
 * @param {Object[]} props.structure             Course module and lesson blocks.
 * @param {Function} props.toggleLegacyMetaboxes Function to toggle metaboxes.
 * @param {Object}   props.attributes            Block attributes.
 * @param {Function} props.setAttributes         Block setAttributes callback.
 * @param {Object}   props.borderColor           Border color.
 */
const EditCourseOutlineBlock = ( {
	clientId,
	className,
	structure,
	toggleLegacyMetaboxes,
	attributes,
	setAttributes,
	borderColor,
} ) => {
	// Toggle legacy metaboxes.
	useEffect( () => {
		toggleLegacyMetaboxes( false );

		return () => {
			toggleLegacyMetaboxes( true );
		};
	}, [ toggleLegacyMetaboxes ] );

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
		if ( structure?.length && ! attributes.isPreview ) {
			setBlocks( structure );
		}
	}, [ structure, setBlocks, attributes.isPreview ] );

	if ( isEmpty ) {
		return (
			<CourseOutlinePlaceholder
				addBlock={ ( type ) => setBlocks( [ { type } ] ) }
			/>
		);
	}

	return (
		<>
			<OutlineAttributesContext.Provider
				value={ {
					outlineAttributes: attributes,
					outlineSetAttributes: setAttributes,
				} }
			>
				<OutlineBlockSettings
					animationsEnabled={ attributes.animationsEnabled }
					setAnimationsEnabled={ updateAnimationsEnabled }
				/>

				<section
					className={ className }
					style={ { borderColor: borderColor.color } }
				>
					<InnerBlocks
						allowedBlocks={ [
							'sensei-lms/course-outline-module',
							'sensei-lms/course-outline-lesson',
						] }
					/>
				</section>
			</OutlineAttributesContext.Provider>
		</>
	);
};

const selectors = ( select ) => ( {
	structure: select( COURSE_STORE ).getStructure(),
} );

const dispatches = ( dispatch, ownProps, { select } ) => {
	const editPost = select( 'core/edit-post' );
	const { toggleEditorPanelEnabled } = dispatch( 'core/edit-post' );

	const hasLegacyMetaBoxes =
		editPost.isEditorPanelEnabled( 'meta-box-course-lessons' ) ||
		editPost.isEditorPanelEnabled( 'meta-box-module_course_mb' );

	const toggleLegacyMetaboxes = ( enable ) => {
		// Side-effect - Prevent submit modules.
		document
			.querySelectorAll( '#module_course_mb input' )
			.forEach( ( input ) => {
				input.disabled = ! enable;
			} );

		// If the `enable` is already as requested, do nothing.
		if ( enable === hasLegacyMetaBoxes ) {
			return;
		}

		toggleEditorPanelEnabled( 'meta-box-course-lessons' );
		toggleEditorPanelEnabled( 'meta-box-module_course_mb' );
	};

	return { toggleLegacyMetaboxes };
};

export default compose(
	withSelect( selectors ),
	withDispatch( dispatches ),
	withColorSettings( {
		borderColor: {
			style: 'border-color',
			label: __( 'Border color', 'sensei-lms' ),
		},
	} ),
	withDefaultBlockStyle()
)( EditCourseOutlineBlock );
