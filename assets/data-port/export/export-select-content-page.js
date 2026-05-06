/**
 * WordPress dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';
import { Button, CheckboxControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PostTokenField } from './post-token-field';
import { ROWS } from './constants';

/**
 * Build the text shown for one row in the summary list.
 *
 * @param {Object}  args
 * @param {Object}  args.row      Row config (label + i18n helpers).
 * @param {boolean} args.included
 * @param {number}  args.count    Number of items selected for this type.
 * @param {?number} args.total    Total available for this type, or null if unknown.
 *
 * @return {string} Sentence describing what will be exported for this type.
 */
const summaryFor = ( { row, included, count, total } ) => {
	const i18n = row.i18n;
	if ( ! included ) {
		return i18n.skipped;
	}
	// No filter applied: describe the full set. `total === null` means the
	// count fetch failed or hasn't returned yet — fall back to the bare label.
	if ( count === 0 ) {
		if ( total === null ) return i18n.unknownTotal;
		if ( total === 0 ) return i18n.none;
		if ( total === 1 ) return i18n.one;
		return i18n.all( total );
	}
	// Filter applied: show "N selected" or "N of M" when the total is known.
	return total === null ? i18n.count( count ) : i18n.countOf( count, total );
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
	const [ itemsByType, setItemsByType ] = useState( {
		course: new Map(),
		lesson: new Map(),
		question: new Map(),
	} );

	const cacheItems = useCallback( ( type, items ) => {
		setItemsByType( ( current ) => {
			const next = new Map( current[ type ] );
			items.forEach( ( item ) => next.set( item.id, item ) );
			return { ...current, [ type ]: next };
		} );
	}, [] );

	// Fetch the total count for each content type so the summary can show
	// "All N courses" instead of just "All courses". `parse: false` gives us
	// access to the raw response so we can read the X-WP-Total header.
	useEffect( () => {
		let cancelled = false;
		ROWS.forEach( ( { type, restBase } ) => {
			apiFetch( {
				path: `/wp/v2/${ restBase }?per_page=1&status=any&_fields=id&context=edit`,
				parse: false,
			} )
				.then( ( response ) => {
					// Guard against setting state after unmount.
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
				.catch( ( error ) => {
					// Totals are cosmetic — the summary falls back to the
					// bare type label — but log the underlying reason so a
					// real REST regression doesn't go unnoticed indefinitely.
					window.console?.warn(
						`[sensei export] failed to fetch ${ type } total`,
						error
					);
				} );
		} );
		return () => {
			cancelled = true;
		};
	}, [] );

	const isLoading = job && 'creating' === job.status;
	// Preserve ROWS order so the wire payload (and analytics) is deterministic
	// regardless of the order the user toggled checkboxes.
	const enabledTypes = ROWS.filter( ( { type } ) => enabled[ type ] ).map(
		( { type } ) => type
	);
	const canSubmit = enabledTypes.length > 0 && ! isLoading;

	// Build a selections object containing only enabled types. The presence of
	// a key marks the type as enabled; an empty array means "no filter — export
	// every item of this type."
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
							cachedItems={ itemsByType[ type ] }
							onItemsFetched={ ( items ) =>
								cacheItems( type, items )
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
