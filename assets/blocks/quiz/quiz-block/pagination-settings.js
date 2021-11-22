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
import { __ } from '@wordpress/i18n';

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
 * @param {Object}   props               Component props.
 * @param {Object}   props.settings      Pagination settings object.
 * @param {Function} props.onChange      Callback called when a setting changed.
 * @param {number}   props.questionCount Number of questions in the quiz.
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
				suffix={ __( 'Questions', 'sensei-lms' ) }
				value={ paginationNumber }
				onChange={ ( value ) =>
					onChange( {
						...settings,
						paginationNumber: value,
					} )
				}
				{ ...props }
			/>
			<p>{ __( 'per page', 'sensei-lms' ) }</p>
		</>
	);
};

/**
 * Quiz sidebar settings.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.settings      Pagination settings object.
 * @param {Function} props.onChange      Callback called when a setting changed.
 * @param {number}   props.questionCount Number of questions in the quiz.
 */
export const PaginationSidebarSettings = ( {
	settings,
	onChange,
	questionCount,
} ) => {
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
				className="sensei-lms-quiz-block-styling"
				title={ __( 'Quiz styling', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<p>
					{ __(
						'Adjust how your quiz is displayed to your learners.',
						'sensei-lms'
					) }
				</p>
				<PanelRow className="sensei-lms-quiz-block-panel">
					<h2 className="sensei-lms-quiz-block-panel__row">
						{ __( 'Pagination', 'sensei-lms' ) }
					</h2>
					<div className="sensei-lms-quiz-block-panel__row">
						<SelectControl
							label={ __( 'Pagination', 'sensei-lms' ) }
							hideLabelFromVision
							value={ paginationNumber === null ? SINGLE : MULTI }
							options={ paginationOptions }
							onChange={ onDropdownChange(
								settings,
								onChange,
								questionCount
							) }
						/>
					</div>
					{ paginationNumber !== null && (
						<div className="sensei-lms-quiz-block-panel__row sensei-lms-quiz-block-panel__questions">
							<QuestionsControl
								settings={ settings }
								onChange={ onChange }
								questionCount={ questionCount }
							/>
						</div>
					) }
				</PanelRow>
				<PanelRow className="sensei-lms-quiz-block-panel">
					<h2 className="sensei-lms-quiz-block-panel__row">
						{ __( 'Progress Bar', 'sensei-lms' ) }
					</h2>
					<ToggleControl
						className="sensei-lms-quiz-block-panel__row"
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
					<div className="sensei-lms-quiz-block-panel__row sensei-lms-quiz-block-panel__progress-bar">
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
					</div>
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
 * @param {Object}   props               Component props.
 * @param {Object}   props.settings      Pagination settings object.
 * @param {Function} props.onChange      Callback called when a setting changed.
 * @param {number}   props.questionCount Number of questions in the quiz.
 */
export const PaginationToolbarSettings = ( {
	settings,
	onChange,
	questionCount,
} ) => {
	const { paginationNumber } = settings;

	return (
		<>
			<Toolbar>
				<ToolbarDropdown
					options={ paginationOptions }
					optionsLabel={ __( 'Quiz pagination', 'sensei-lms' ) }
					value={ paginationNumber === null ? SINGLE : MULTI }
					onChange={ onDropdownChange(
						settings,
						onChange,
						questionCount
					) }
				/>
			</Toolbar>
			{ paginationNumber !== null && (
				<ToolbarGroup className="sensei-lms-quiz-block-toolbar__group">
					<QuestionsControl
						settings={ settings }
						onChange={ onChange }
						questionCount={ questionCount }
					/>
				</ToolbarGroup>
			) }
		</>
	);
};
