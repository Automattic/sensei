/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Button, CheckboxControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PostTokenField } from './post-token-field';

const ROWS = [
	{
		type: 'course',
		label: __( 'Courses', 'sensei-lms' ),
		restBase: 'courses',
		placeholder: __( 'Search to limit to specific courses…', 'sensei-lms' ),
		filterAriaLabel: __( 'Filter courses to export', 'sensei-lms' ),
		summaryAll: ( total ) => {
			if ( total === 0 ) {
				return __( 'No courses', 'sensei-lms' );
			}
			if ( total === 1 ) {
				return __( '1 course', 'sensei-lms' );
			}
			return sprintf(
				/* translators: %d is the total number of courses on the site. */
				_n( 'All %d course', 'All %d courses', total, 'sensei-lms' ),
				total
			);
		},
		summaryAllUnknown: __( 'Courses', 'sensei-lms' ),
		summaryCountOf: ( count, total ) =>
			sprintf(
				/* translators: 1: number of selected courses, 2: total courses. */
				_n(
					'%1$d of %2$d course',
					'%1$d of %2$d courses',
					total,
					'sensei-lms'
				),
				count,
				total
			),
		summaryCount: ( count ) =>
			sprintf(
				/* translators: %d is the number of courses selected. */
				_n( '%d course', '%d courses', count, 'sensei-lms' ),
				count
			),
		summarySkipped: __( 'Courses — skipped', 'sensei-lms' ),
	},
	{
		type: 'lesson',
		label: __( 'Lessons', 'sensei-lms' ),
		restBase: 'lessons',
		placeholder: __( 'Search to limit to specific lessons…', 'sensei-lms' ),
		filterAriaLabel: __( 'Filter lessons to export', 'sensei-lms' ),
		summaryAll: ( total ) => {
			if ( total === 0 ) {
				return __( 'No lessons', 'sensei-lms' );
			}
			if ( total === 1 ) {
				return __( '1 lesson', 'sensei-lms' );
			}
			return sprintf(
				/* translators: %d is the total number of lessons on the site. */
				_n( 'All %d lesson', 'All %d lessons', total, 'sensei-lms' ),
				total
			);
		},
		summaryAllUnknown: __( 'Lessons', 'sensei-lms' ),
		summaryCountOf: ( count, total ) =>
			sprintf(
				/* translators: 1: number of selected lessons, 2: total lessons. */
				_n(
					'%1$d of %2$d lesson',
					'%1$d of %2$d lessons',
					total,
					'sensei-lms'
				),
				count,
				total
			),
		summaryCount: ( count ) =>
			sprintf(
				/* translators: %d is the number of lessons selected. */
				_n( '%d lesson', '%d lessons', count, 'sensei-lms' ),
				count
			),
		summarySkipped: __( 'Lessons — skipped', 'sensei-lms' ),
	},
	{
		type: 'question',
		label: __( 'Questions', 'sensei-lms' ),
		restBase: 'questions',
		placeholder: __(
			'Search to limit to specific questions…',
			'sensei-lms'
		),
		filterAriaLabel: __( 'Filter questions to export', 'sensei-lms' ),
		summaryAll: ( total ) => {
			if ( total === 0 ) {
				return __( 'No questions', 'sensei-lms' );
			}
			if ( total === 1 ) {
				return __( '1 question', 'sensei-lms' );
			}
			return sprintf(
				/* translators: %d is the total number of questions on the site. */
				_n(
					'All %d question',
					'All %d questions',
					total,
					'sensei-lms'
				),
				total
			);
		},
		summaryAllUnknown: __( 'Questions', 'sensei-lms' ),
		summaryCountOf: ( count, total ) =>
			sprintf(
				/* translators: 1: number of selected questions, 2: total questions. */
				_n(
					'%1$d of %2$d question',
					'%1$d of %2$d questions',
					total,
					'sensei-lms'
				),
				count,
				total
			),
		summaryCount: ( count ) =>
			sprintf(
				/* translators: %d is the number of questions selected. */
				_n( '%d question', '%d questions', count, 'sensei-lms' ),
				count
			),
		summarySkipped: __( 'Questions — skipped', 'sensei-lms' ),
	},
];

/**
 * Build the text shown for one row in the summary list.
 *
 * @param {Object}  args
 * @param {Object}  args.row      Row config (label + summary helpers).
 * @param {boolean} args.included
 * @param {number}  args.count    Number of items selected for this type.
 * @param {?number} args.total    Total available for this type, or null if unknown.
 *
 * @return {string} Sentence describing what will be exported for this type.
 */
const summaryFor = ( { row, included, count, total } ) => {
	if ( ! included ) {
		return row.summarySkipped;
	}
	if ( count === 0 ) {
		return total === null ? row.summaryAllUnknown : row.summaryAll( total );
	}
	return total === null
		? row.summaryCount( count )
		: row.summaryCountOf( count, total );
};

/**
 * The setup screen for the exporter. Each row toggles whether a CSV
 * is produced for that content type, and lets the user limit it to
 * specific items via a filter field. Empty filter on an enabled row
 * exports every item of that type.
 *
 * @param {Object}   props
 * @param {Object}   props.job      Current job state (from the export store).
 * @param {Function} props.onSubmit Called with the per-type selections object.
 */
export const ExportSelectContentPage = ( { job, onSubmit } ) => {
	const [ enabled, setEnabled ] = useState( {
		course: true,
		lesson: true,
		question: true,
	} );
	const [ selections, setSelections ] = useState( {
		course: [],
		lesson: [],
		question: [],
	} );
	const [ totals, setTotals ] = useState( {
		course: null,
		lesson: null,
		question: null,
	} );

	useEffect( () => {
		let cancelled = false;
		ROWS.forEach( ( { type, restBase } ) => {
			apiFetch( {
				path: `/wp/v2/${ restBase }?per_page=1&status=any&_fields=id&context=edit`,
				parse: false,
			} )
				.then( ( response ) => {
					if ( cancelled ) {
						return;
					}
					const total = parseInt(
						response.headers.get( 'X-WP-Total' ) || '0',
						10
					);
					setTotals( ( current ) => ( {
						...current,
						[ type ]: total,
					} ) );
				} )
				.catch( () => {
					/* leave total as null; summary falls back to "All <type>" */
				} );
		} );
		return () => {
			cancelled = true;
		};
	}, [] );

	const isLoading = job && 'creating' === job.status;
	const enabledTypes = ROWS.filter( ( { type } ) => enabled[ type ] ).map(
		( { type } ) => type
	);
	const canSubmit = enabledTypes.length > 0 && ! isLoading;

	const submit = () =>
		onSubmit(
			enabledTypes.reduce(
				( acc, type ) => ( {
					...acc,
					[ type ]: selections[ type ],
				} ),
				{}
			)
		);

	return (
		<div className="sensei-data-port-step__body">
			<p className="sensei-export__select-content__label">
				{ __( 'Choose what to export.', 'sensei-lms' ) }
			</p>
			{ ROWS.map( ( { type, label, placeholder, filterAriaLabel } ) => (
				<div
					key={ type }
					className="sensei-export__select-content__row"
				>
					<CheckboxControl
						__nextHasNoMarginBottom
						label={ label }
						checked={ enabled[ type ] }
						onChange={ ( isChecked ) =>
							setEnabled( ( current ) => ( {
								...current,
								[ type ]: isChecked,
							} ) )
						}
					/>
					{ enabled[ type ] && (
						<PostTokenField
							type={ type }
							ariaLabel={ filterAriaLabel }
							placeholder={ placeholder }
							selectedIds={ selections[ type ] }
							onChange={ ( ids ) =>
								setSelections( ( current ) => ( {
									...current,
									[ type ]: ids,
								} ) )
							}
						/>
					) }
				</div>
			) ) }
			{ canSubmit && (
				<div className="sensei-export__summary" aria-live="polite">
					<p className="sensei-export__summary__heading">
						{ __( 'Your export will include:', 'sensei-lms' ) }
					</p>
					<ul className="sensei-export__summary__list">
						{ ROWS.map( ( row ) => (
							<li
								key={ row.type }
								className={ `sensei-export__summary__item ${
									enabled[ row.type ]
										? 'is-included'
										: 'is-skipped'
								}` }
							>
								{ summaryFor( {
									row,
									included: enabled[ row.type ],
									count: selections[ row.type ].length,
									total: totals[ row.type ],
								} ) }
							</li>
						) ) }
					</ul>
				</div>
			) }
			<div className="sensei-data-port-step__footer">
				<Button
					isPrimary
					onClick={ submit }
					disabled={ ! canSubmit }
					isBusy={ isLoading }
				>
					{ __( 'Start Export', 'sensei-lms' ) }
				</Button>
			</div>
		</div>
	);
};
