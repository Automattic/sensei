/**
 * WordPress dependencies
 */
import { ContrastChecker, PanelColorSettings } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	SelectControl,
	Toolbar,
	ToolbarGroup,
} from '@wordpress/components';
import { __, _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../../editor-components/number-control';
import ToolbarDropdown from '../../editor-components/toolbar-dropdown';

const SINGLE = 'single';
const MULTI = 'multi';

const paginationOptions = [
	{
		label: __( 'Single page', 'sensei-lms' ),
		value: SINGLE,
	},
	{
		label: __( 'Multi-Page', 'sensei-lms' ),
		value: MULTI,
	},
];

const onDropdownChange = ( settings, onChange ) => ( value ) => {
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
};

/**
 * A component which contains a NumberControl and the 'per page' accompanying text.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.settings Pagination settings object.
 * @param {Function} props.onChange Callback called when a setting changed.
 */
const QuestionsControl = ( { settings, onChange, ...props } ) => {
	const { paginationNumber } = settings;

	return (
		<>
			<NumberControl
				label={ __( 'Number of Questions', 'sensei-lms' ) }
				min={ 1 }
				step={ 1 }
				hideLabelFromVision
				suffix={ _n(
					'question',
					'questions',
					paginationNumber,
					'sensei-lms'
				) }
				value={ paginationNumber }
				onChange={ ( value ) =>
					onChange( {
						...settings,
						paginationNumber: value,
					} )
				}
				{ ...props }
			/>
			<span>{ __( 'per page', 'sensei-lms' ) }</span>
		</>
	);
};

/**
 * Quiz sidebar settings.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.settings Pagination settings object.
 * @param {Function} props.onChange Callback called when a setting changed.
 */
export const PaginationSidebarSettings = ( { settings, onChange } ) => {
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
				title={ __( 'Pagination', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<PanelRow className="sensei-lms-quiz-block__pagination">
					<SelectControl
						label={ __( 'Pagination', 'sensei-lms' ) }
						hideLabelFromVision
						value={ paginationNumber === null ? SINGLE : MULTI }
						options={ paginationOptions }
						onChange={ onDropdownChange( settings, onChange ) }
					/>
				</PanelRow>
				{ paginationNumber !== null && (
					<PanelRow className="sensei-lms-quiz-block__question-count">
						<QuestionsControl
							settings={ settings }
							onChange={ onChange }
						/>
					</PanelRow>
				) }
			</PanelBody>

			<PanelBody
				title={ __( 'Progress bar settings', 'sensei-lms' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<ToggleControl
						checked={ showProgressBar }
						label={ __( 'Show Progress Bar', 'sensei-lms' ) }
						value={ progressBarRadius }
						onChange={ ( value ) =>
							onChange( {
								...settings,
								showProgressBar: value,
							} )
						}
					/>
				</PanelRow>
				<PanelRow className="sensei-lms-quiz-block__progress-bar">
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

/**
 * Quiz toolbar settings.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.settings Pagination settings object.
 * @param {Function} props.onChange Callback called when a setting changed.
 */
export const PaginationToolbarSettings = ( { settings, onChange } ) => {
	const { paginationNumber } = settings;

	return (
		<>
			<Toolbar>
				<ToolbarDropdown
					options={ paginationOptions }
					optionsLabel={ __( 'Quiz pagination', 'sensei-lms' ) }
					value={ paginationNumber === null ? SINGLE : MULTI }
					onChange={ onDropdownChange( settings, onChange ) }
				/>
			</Toolbar>
			{ paginationNumber !== null && (
				<ToolbarGroup className="sensei-lms-quiz-block__toolbar-group">
					<QuestionsControl
						settings={ settings }
						onChange={ onChange }
					/>
				</ToolbarGroup>
			) }
		</>
	);
};
