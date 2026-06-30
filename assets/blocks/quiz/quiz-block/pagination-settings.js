/**
 * WordPress dependencies
 */
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	SelectControl,
	ToolbarItem,
	ToolbarGroup,
} from '@wordpress/components';
import { forwardRef } from '@wordpress/element';
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
		label: __( 'Multi-page', 'sensei-lms' ),
		value: MULTI,
	},
];

const onDropdownChange = ( updatePagination ) => ( value ) => {
	updatePagination( {
		paginationNumber: value === MULTI ? 1 : null,
	} );
};

/**
 * A component which contains a NumberControl and the 'per page' accompanying text.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.settings         Pagination settings object.
 * @param {Function} props.updatePagination Update pagination options function.
 */
const QuestionsControl = forwardRef(
	( { settings, updatePagination, ...props }, ref ) => {
		const { paginationNumber } = settings;

		return (
			<>
				<NumberControl
					ref={ ref }
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
						updatePagination( { paginationNumber: value } )
					}
					{ ...props }
				/>
				<span>{ __( 'per page', 'sensei-lms' ) }</span>
			</>
		);
	}
);

/**
 * Quiz sidebar settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.settings         Pagination settings object.
 * @param {Function} props.updatePagination Update pagination options function.
 */
export const PaginationSidebarSettings = ( { settings, updatePagination } ) => {
	const {
		paginationNumber,
		showProgressBar,
		progressBarRadius,
		progressBarHeight,
	} = settings;

	return (
		<>
			<PanelBody
				title={ __( 'Pagination', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<PanelRow className="sensei-lms-quiz-block-settings__pagination">
					<SelectControl
						label={ __( 'Pagination', 'sensei-lms' ) }
						hideLabelFromVision
						value={ paginationNumber === null ? SINGLE : MULTI }
						options={ paginationOptions }
						onChange={ onDropdownChange( updatePagination ) }
					/>
				</PanelRow>
				{ paginationNumber !== null && (
					<PanelRow className="sensei-lms-quiz-block-settings__question-count">
						<QuestionsControl
							settings={ settings }
							updatePagination={ updatePagination }
						/>
					</PanelRow>
				) }
				{ paginationNumber !== null && (
					<>
						<PanelRow>
							<ToggleControl
								checked={ showProgressBar }
								label={ __(
									'Show Progress Bar',
									'sensei-lms'
								) }
								value={ progressBarRadius }
								onChange={ ( value ) =>
									updatePagination( {
										showProgressBar: value,
									} )
								}
							/>
						</PanelRow>
						<PanelRow className="sensei-lms-quiz-block-settings__progress-bar">
							<NumberControl
								label={ __( 'Radius', 'sensei-lms' ) }
								min={ 1 }
								step={ 1 }
								suffix={ __( 'PX', 'sensei-lms' ) }
								value={ progressBarRadius }
								onChange={ ( value ) =>
									updatePagination( {
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
									updatePagination( {
										progressBarHeight: value,
									} )
								}
							/>
						</PanelRow>
					</>
				) }
			</PanelBody>
		</>
	);
};

/**
 * Quiz toolbar settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.settings         Pagination settings object.
 * @param {Function} props.updatePagination Update pagination options function.
 */
export const PaginationToolbarSettings = ( { settings, updatePagination } ) => {
	const { paginationNumber } = settings;

	return (
		<>
			<ToolbarGroup>
				<ToolbarDropdown
					options={ paginationOptions }
					optionsLabel={ __( 'Quiz pagination', 'sensei-lms' ) }
					value={ paginationNumber === null ? SINGLE : MULTI }
					onChange={ onDropdownChange( updatePagination ) }
				/>
			</ToolbarGroup>
			{ paginationNumber !== null && (
				<ToolbarGroup className="sensei-lms-quiz-block__toolbar-group">
					<ToolbarItem
						as={ QuestionsControl }
						settings={ settings }
						updatePagination={ updatePagination }
					/>
				</ToolbarGroup>
			) }
		</>
	);
};
