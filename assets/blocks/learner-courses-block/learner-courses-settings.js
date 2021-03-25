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
import { grid, list } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Learner Settings component.
 *
 * @param {Object}   props
 * @param {Object}   props.options    Block options attribute.
 * @param {Function} props.setOptions Set options function.
 */
const LearnerCoursesSettings = ( { options, setOptions } ) => {
	const courseSettingsTogglers = [
		{
			optionKey: 'courseDescriptionEnabled',
			label: __( 'Course description', 'sensei-lms' ),
		},
		{
			optionKey: 'featuredImageEnabled',
			label: __( 'Featured image', 'sensei-lms' ),
		},
		{
			optionKey: 'courseCategoryEnabled',
			label: __( 'Course category', 'sensei-lms' ),
		},
		{
			optionKey: 'progressBarEnabled',
			label: __( 'Progress bar', 'sensei-lms' ),
		},
	];

	const layoutViewTogglers = [
		{
			view: 'list',
			label: __( 'List view', 'sensei-lms' ),
			icon: list,
		},
		{
			view: 'grid',
			label: __( 'Grid view', 'sensei-lms' ),
			icon: grid,
		},
	];

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Course settings', 'sensei-lms' ) }
					initialOpen={ true }
				>
					{ courseSettingsTogglers.map( ( { optionKey, label } ) => (
						<PanelRow key={ optionKey }>
							<ToggleControl
								checked={ options[ optionKey ] }
								onChange={ ( value ) => {
									setOptions( {
										[ optionKey ]: value,
									} );
								} }
								label={ label }
							/>
						</PanelRow>
					) ) }
				</PanelBody>
			</InspectorControls>
			<BlockControls>
				<ToolbarGroup>
					{ layoutViewTogglers.map( ( { view, label, icon } ) => (
						<ToolbarButton
							key={ view }
							data-testid={ view }
							isActive={ view === options.layoutView }
							icon={ icon }
							label={ label }
							onClick={ () => {
								setOptions( { layoutView: view } );
							} }
						/>
					) ) }
				</ToolbarGroup>
			</BlockControls>
		</>
	);
};

export default LearnerCoursesSettings;
