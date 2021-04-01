/**
 * WordPress dependencies
 */
import {
	BlockControls,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	ToolbarGroup,
	ToolbarButton,
	SelectControl,
} from '@wordpress/components';
import { grid, list } from '@wordpress/icons';
import { __, sprintf, _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CourseProgressSettings from '../editor-components/course-progress-settings';

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

	const colorSettings = [
		{
			optionKey: 'primaryColor',
			label: __( 'Primary color', 'sensei-lms' ),
			value: options.primaryColor,
		},
		{
			optionKey: 'accentColor',
			label: __( 'Accent color', 'sensei-lms' ),
			value: options.accentColor,
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
				{ 'grid' === options.layoutView && (
					<PanelBody
						title={ __( 'Styling', 'sensei-lms' ) }
						initialOpen={ true }
					>
						<PanelRow>
							<SelectControl
								label={ __( 'Layout', 'sensei-lms' ) }
								options={ [ 2, 3 ].map( ( columns ) => ( {
									value: columns,
									label: sprintf(
										// translators: placeholder is number of columns.
										_n(
											'%d column',
											'%d columns',
											columns,
											'sensei-lms'
										),
										columns
									),
								} ) ) }
								value={ options.columns }
								onChange={ ( value ) => {
									setOptions( {
										columns: value,
									} );
								} }
							/>
						</PanelRow>
					</PanelBody>
				) }
				{ options.progressBarEnabled && (
					<CourseProgressSettings
						borderRadius={ options.progressBarBorderRadius }
						setBorderRadius={ ( value ) => {
							setOptions( {
								progressBarBorderRadius: value,
							} );
						} }
						height={ options.progressBarHeight }
						setHeight={ ( value ) => {
							setOptions( {
								progressBarHeight: value,
							} );
						} }
					/>
				) }
				<PanelColorSettings
					title={ __( 'Color settings', 'sensei-lms' ) }
					initialOpen={ false }
					colorSettings={ colorSettings.map(
						( { optionKey, ...settings } ) => ( {
							...settings,
							onChange: ( value ) => {
								setOptions( { [ optionKey ]: value } );
							},
						} )
					) }
				/>
			</InspectorControls>
			<BlockControls>
				<ToolbarGroup>
					{ layoutViewTogglers.map( ( { view, label, icon } ) => (
						<ToolbarButton
							key={ view }
							extraProps={ { 'data-testid': view } }
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
