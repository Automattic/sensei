import { InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { useSelect, withSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { CourseOutlinePlaceholder } from './placeholder';
import { COURSE_STORE } from './store';
import { useBlocksCreator } from './use-block-creator';
import { useDescendantAttributes } from './hooks';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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

	useDescendantAttributes( clientId, attributes, setAttributes );

	if ( isEmpty ) {
		return (
			<CourseOutlinePlaceholder
				addBlock={ ( type ) => setBlocks( [ { type } ] ) }
			/>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Enable Animations', 'sensei-lms' ) }
					initialOpen={ true }
				>
					<ToggleControl
						checked={ attributes.animationsEnabled }
						onChange={ updateAnimationsEnabled }
						label={ __(
							'Enable animations on module collapse/expand.',
							'sensei-lms'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<section className={ className }>
				<InnerBlocks
					allowedBlocks={ [
						'sensei-lms/course-outline-module',
						'sensei-lms/course-outline-lesson',
					] }
				/>
			</section>
		</>
	);
};

export default withSelect( ( select ) => {
	return {
		structure: select( COURSE_STORE ).getStructure(),
	};
} )( EditCourseOutlineBlock );
