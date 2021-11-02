/**
 * WordPress dependencies
 */
import { ContrastChecker, PanelColorSettings } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../../editor-components/number-control';

const SINGLE = 'single';
const MULTI = 'multi';

/**
 * Quiz settings.
 *
 * @param {Object}   props          Block props.
 * @param {Object}   props.settings Pagination settings object.
 * @param {Function} props.onChange Callback called when a setting changed.
 */
const PaginationSettings = ( { settings, onChange } ) => {
	const {
		paginationNumber,
		showProgressBar,
		progressBarRadius,
		progressBarHeight,
		progressBarColor,
		progressBarBackground,
	} = settings;

	return (
		<>
			<PanelBody
				title={ __( 'Quiz front end', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<p>
					{ __(
						'Adjust how your quiz is displayed to your learners.',
						'sensei-lms'
					) }
				</p>
				<PanelRow className="sensei-lms-quiz-block-panel__row-title">
					<h2>{ __( 'Pagination', 'sensei-lms' ) }</h2>
					<SelectControl
						label={ __( 'Pagination', 'sensei-lms' ) }
						hideLabelFromVision
						value={ paginationNumber !== null ? MULTI : SINGLE }
						options={ [
							{
								label: __( 'Single page', 'sensei-lms' ),
								value: SINGLE,
							},
							{
								label: __( 'Multi page', 'sensei-lms' ),
								value: MULTI,
							},
						] }
						onChange={ ( value ) => {
							if ( value === MULTI ) {
								onChange( {
									...settings,
									paginationNumber: 1,
								} );
							} else {
								onChange( {
									...settings,
									paginationNumber: null,
								} );
							}
						} }
					/>
					<NumberControl
						label={ __( 'Number of Questions', 'sensei-lms' ) }
						min={ 1 }
						step={ 1 }
						hideLabelFromVision
						suffix={ __( 'QNS', 'sensei-lms' ) }
						value={ paginationNumber }
						onChange={ ( value ) =>
							onChange( { ...settings, paginationNumber: value } )
						}
					/>
				</PanelRow>
				<PanelRow className="sensei-lms-quiz-block-panel__row-title">
					<h2>{ __( 'Progress Bar', 'sensei-lms' ) }</h2>
					<ToggleControl
						className="sensei-lms-quiz-block-panel__row-item"
						checked={ showProgressBar }
						label={ __( 'Show Progress Bar', 'sensei-lms' ) }
						value={ progressBarRadius }
						onChange={ ( value ) =>
							onChange( { ...settings, showProgressBar: value } )
						}
					/>
					<NumberControl
						label={ __( 'Radius', 'sensei-lms' ) }
						min={ 1 }
						step={ 1 }
						suffix={ __( 'PX', 'sensei-lms' ) }
						value={ progressBarRadius }
						onChange={ ( value ) =>
							onChange( {
								...settings,
								progressBarRadius: value,
							} )
						}
					/>
					<NumberControl
						label={ __( 'Height', 'sensei-lms' ) }
						min={ 1 }
						step={ 1 }
						suffix={ __( 'PX', 'sensei-lms' ) }
						value={ progressBarHeight }
						onChange={ ( value ) =>
							onChange( {
								...settings,
								progressBarHeight: value,
							} )
						}
					/>
				</PanelRow>
			</PanelBody>
			<PanelColorSettings
				title={ __( 'Color settings', 'sensei-lms' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: progressBarColor,
						onChange: ( value ) =>
							onChange( {
								...settings,
								progressBarColor: value,
							} ),
						label: __( 'Progress bar color', 'sensei-lms' ),
					},
					{
						value: progressBarBackground,
						onChange: ( value ) =>
							onChange( {
								...settings,
								progressBarBackground: value,
							} ),
						label: __(
							'Progress bar background color',
							'sensei-lms'
						),
					},
				] }
			>
				<ContrastChecker
					textColor={ progressBarColor }
					backgroundColor={ progressBarBackground }
					isLargeText={ false }
				/>
			</PanelColorSettings>
		</>
	);
};

export default PaginationSettings;
