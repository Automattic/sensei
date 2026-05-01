/**
 * WordPress dependencies
 */
import { useEffect, useMemo, useState } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { postTypeLabels } from '../../shared/helpers/labels';

const REST_BASE_BY_TYPE = {
	course: 'courses',
	lesson: 'lessons',
	question: 'questions',
};

const PER_PAGE = 20;

/**
 * Item picker for a single content type. Empty selection = export all.
 *
 * @param {Object}   props
 * @param {string}   props.type        Content type ('course', 'lesson', 'question').
 * @param {number[]} props.selectedIds Currently-selected post IDs.
 * @param {Function} props.onChange    Called with the next ID array.
 */
const ExportTypeItemPicker = ( { type, selectedIds, onChange } ) => {
	const [ search, setSearch ] = useState( '' );
	const [ debouncedSearch, setDebouncedSearch ] = useState( '' );
	const [ items, setItems ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	const selectedSet = useMemo(
		() => new Set( selectedIds ),
		[ selectedIds ]
	);

	useEffect( () => {
		const handle = setTimeout( () => {
			setDebouncedSearch( search );
			setPage( 1 );
		}, 300 );
		return () => clearTimeout( handle );
	}, [ search ] );

	useEffect( () => {
		let cancelled = false;
		setIsLoading( true );
		setError( null );

		const params = new URLSearchParams( {
			per_page: String( PER_PAGE ),
			page: String( page ),
			status: 'any',
			_fields: 'id,title',
			context: 'edit',
		} );
		if ( debouncedSearch ) {
			params.set( 'search', debouncedSearch );
		}

		apiFetch( {
			path: `/wp/v2/${
				REST_BASE_BY_TYPE[ type ]
			}?${ params.toString() }`,
			parse: false,
		} )
			.then( async ( response ) => {
				const totalPagesHeader =
					response.headers.get( 'X-WP-TotalPages' ) || '1';
				const data = await response.json();
				if ( cancelled ) {
					return;
				}
				setItems( data );
				setTotalPages(
					Math.max( 1, parseInt( totalPagesHeader, 10 ) )
				);
			} )
			.catch( ( err ) => {
				if ( ! cancelled ) {
					setError(
						err.message ||
							__( 'Failed to load items.', 'sensei-lms' )
					);
				}
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ type, page, debouncedSearch ] );

	const toggleId = ( id, isChecked ) => {
		if ( isChecked ) {
			onChange( Array.from( new Set( [ ...selectedIds, id ] ) ) );
		} else {
			onChange( selectedIds.filter( ( existing ) => existing !== id ) );
		}
	};

	return (
		<div className="sensei-export__item-picker">
			<h3 className="sensei-export__item-picker__heading">
				{ postTypeLabels[ type ] }
			</h3>
			<p className="sensei-export__item-picker__hint">
				{ __(
					'Leave empty to export all. Selecting items also exports their lessons and questions where applicable.',
					'sensei-lms'
				) }
			</p>
			<TextControl
				label={ __( 'Search', 'sensei-lms' ) }
				value={ search }
				onChange={ setSearch }
			/>
			{ error && (
				<p className="sensei-export__item-picker__error">{ error }</p>
			) }
			{ isLoading ? (
				<Spinner />
			) : (
				<ul className="sensei-export__item-picker__list">
					{ items.length === 0 && (
						<li className="sensei-export__item-picker__empty">
							{ __( 'No items found.', 'sensei-lms' ) }
						</li>
					) }
					{ items.map( ( item ) => (
						<li key={ item.id }>
							<CheckboxControl
								label={
									item.title.rendered ||
									item.title.raw ||
									`#${ item.id }`
								}
								checked={ selectedSet.has( item.id ) }
								onChange={ ( isChecked ) =>
									toggleId( item.id, isChecked )
								}
							/>
						</li>
					) ) }
				</ul>
			) }
			{ totalPages > 1 && (
				<div className="sensei-export__item-picker__pagination">
					<Button
						isSecondary
						disabled={ page <= 1 || isLoading }
						onClick={ () => setPage( page - 1 ) }
					>
						{ __( 'Previous', 'sensei-lms' ) }
					</Button>
					<span>
						{
							/* translators: %1$d current page; %2$d total pages. */
							__( 'Page %1$d of %2$d', 'sensei-lms' )
								.replace( '%1$d', page )
								.replace( '%2$d', totalPages )
						}
					</span>
					<Button
						isSecondary
						disabled={ page >= totalPages || isLoading }
						onClick={ () => setPage( page + 1 ) }
					>
						{ __( 'Next', 'sensei-lms' ) }
					</Button>
				</div>
			) }
			{ selectedIds.length > 0 && (
				<p className="sensei-export__item-picker__selected-count">
					{
						/* translators: %d is the number of selected items. */
						__( '%d selected', 'sensei-lms' ).replace(
							'%d',
							selectedIds.length
						)
					}{ ' ' }
					<Button isLink onClick={ () => onChange( [] ) }>
						{ __( 'Clear selection', 'sensei-lms' ) }
					</Button>
				</p>
			) }
		</div>
	);
};

/**
 * Step 2 of the export flow. Lets the user optionally narrow each
 * selected content type down to specific items. Empty selection for a
 * type means "export all of that type".
 *
 * @param {Object}   props
 * @param {string[]} props.types    Content types selected in step 1.
 * @param {Object}   props.job      Current job state.
 * @param {Function} props.onSubmit Called with ({ types, selections }).
 * @param {Function} props.onBack   Called when the user clicks "Back".
 */
export const ExportSelectItemsPage = ( { types, job, onSubmit, onBack } ) => {
	const [ selections, setSelections ] = useState( () =>
		types.reduce( ( acc, type ) => ( { ...acc, [ type ]: [] } ), {} )
	);

	const isLoading = job && 'creating' === job.status;

	const submit = ( event ) => {
		event.preventDefault();
		onSubmit( { types, selections } );
	};

	return (
		<form onSubmit={ submit }>
			<div className="sensei-data-port-step__body">
				<p className="sensei-export__select-items__label">
					{ __(
						'Choose specific items to export, or leave a section empty to export everything of that type.',
						'sensei-lms'
					) }
				</p>
				{ types.map( ( type ) => (
					<ExportTypeItemPicker
						key={ type }
						type={ type }
						selectedIds={ selections[ type ] }
						onChange={ ( ids ) =>
							setSelections( ( current ) => ( {
								...current,
								[ type ]: ids,
							} ) )
						}
					/>
				) ) }
				<div className="sensei-data-port-step__footer">
					<Button
						isSecondary
						onClick={ onBack }
						disabled={ isLoading }
					>
						{ __( 'Back', 'sensei-lms' ) }
					</Button>
					<Button
						type="submit"
						isPrimary
						disabled={ isLoading }
						isBusy={ isLoading }
					>
						{ __( 'Start Export', 'sensei-lms' ) }
					</Button>
				</div>
			</div>
		</form>
	);
};
