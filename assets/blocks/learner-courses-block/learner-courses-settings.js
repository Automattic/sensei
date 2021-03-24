/**
 * WordPress dependencies
 */
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	ToolbarGroup,
	ToolbarButton,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GridViewIcon, ListViewIcon } from '../../icons';

/**
 * Learner Settings component.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block set attributes function.
 */
const LearnerCoursesSettings = ( { attributes, setAttributes } ) => {
	const courseSettingsTogglers = [
		{
			attributeKey: 'courseDescriptionEnabled',
			label: __( 'Course description', 'sensei-lms' ),
		},
		{
			attributeKey: 'featuredImageEnabled',
			label: __( 'Featured image', 'sensei-lms' ),
		},
		{
			attributeKey: 'courseCategoryEnabled',
			label: __( 'Course category', 'sensei-lms' ),
		},
		{
			attributeKey: 'progressBarEnabled',
			label: __( 'Progress bar', 'sensei-lms' ),
		},
	];

	const layoutViewTogglers = [
		{
			view: 'list',
			label: __( 'List view', 'sensei-lms' ),
			icon: ListViewIcon,
		},
		{
			view: 'grid',
			label: __( 'Grid view', 'sensei-lms' ),
			icon: GridViewIcon,
		},
	];

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Course settings', 'sensei-lms' ) }
					initialOpen={ true }
				>
					{ courseSettingsTogglers.map(
						( { attributeKey, label } ) => (
							<PanelRow key={ attributeKey }>
								<ToggleControl
									checked={ attributes[ attributeKey ] }
									onChange={ ( value ) => {
										setAttributes( {
											[ attributeKey ]: value,
										} );
									} }
									label={ label }
								/>
							</PanelRow>
						)
					) }
				</PanelBody>
			</InspectorControls>
			<BlockControls>
				<ToolbarGroup>
					{ layoutViewTogglers.map( ( { view, label, icon } ) => (
						<ToolbarButton
							key={ view }
							data-testid={ view }
							isActive={ view === attributes.layoutView }
							icon={ icon }
							label={ label }
							onClick={ () => {
								setAttributes( { layoutView: view } );
							} }
						/>
					) ) }
				</ToolbarGroup>
			</BlockControls>
		</>
	);
};

export default LearnerCoursesSettings;
