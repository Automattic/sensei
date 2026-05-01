/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Button, FormTokenField } from '@wordpress/components';
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

const SUGGESTION_LIMIT = 20;

/**
 * Build a unique label for a post. When two posts share a title we
 * disambiguate with the post ID so FormTokenField (which works on
 * strings) can map labels back to IDs unambiguously.
 *
 * @param {Object}   item Post item ({ id, title }).
 * @param {Object[]} pool Items in the same context to check for collisions.
 * @return {string} Display label for the token.
 */
const buildLabel = ( item, pool ) => {
	const title = item.title?.rendered || item.title?.raw || '';
	const collides = pool.some(
		( other ) =>
			other.id !== item.id &&
			( other.title?.rendered || other.title?.raw || '' ) === title
	);
	const safeTitle = title || __( '(no title)', 'sensei-lms' );
	return collides || ! title ? `${ safeTitle } (#${ item.id })` : safeTitle;
};

/**
 * FormTokenField-based item picker for a single content type.
 *
 * @param {Object}   props
 * @param {string}   props.type        Content type ('course', 'lesson', 'question').
 * @param {number[]} props.selectedIds Currently-selected post IDs.
 * @param {Function} props.onChange    Called with the next ID array.
 */
const ExportTypeItemPicker = ( { type, selectedIds, onChange } ) => {
	const [ inputValue, setInputValue ] = useState( '' );
	const [ debouncedInput, setDebouncedInput ] = useState( '' );
	const [ suggestionItems, setSuggestionItems ] = useState( [] );
	const [ selectedItems, setSelectedItems ] = useState( [] );

	useEffect( () => {
		const handle = setTimeout( () => setDebouncedInput( inputValue ), 300 );
		return () => clearTimeout( handle );
	}, [ inputValue ] );

	useEffect( () => {
		let cancelled = false;
		const params = new URLSearchParams( {
			per_page: String( SUGGESTION_LIMIT ),
			status: 'any',
			_fields: 'id,title',
			context: 'edit',
		} );
		if ( debouncedInput ) {
			params.set( 'search', debouncedInput );
		}

		apiFetch( {
			path: `/wp/v2/${
				REST_BASE_BY_TYPE[ type ]
			}?${ params.toString() }`,
		} )
			.then( ( items ) => {
				if ( ! cancelled ) {
					setSuggestionItems( items );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setSuggestionItems( [] );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ type, debouncedInput ] );

	const knownItemsById = new Map();
	[ ...selectedItems, ...suggestionItems ].forEach( ( item ) => {
		knownItemsById.set( item.id, item );
	} );
	const knownItemsPool = Array.from( knownItemsById.values() );

	const tokenForId = ( id ) => {
		const item = knownItemsById.get( id );
		if ( ! item ) {
			return `#${ id }`;
		}
		return buildLabel( item, knownItemsPool );
	};

	const tokenValues = selectedIds.map( tokenForId );

	const suggestions = suggestionItems
		.filter( ( item ) => ! selectedIds.includes( item.id ) )
		.map( ( item ) => buildLabel( item, knownItemsPool ) );

	const onTokensChange = ( tokens ) => {
		const labelToId = new Map();
		knownItemsPool.forEach( ( item ) =>
			labelToId.set( buildLabel( item, knownItemsPool ), item.id )
		);

		const nextIds = [];
		const nextSelectedItems = [];
		const seen = new Set();

		tokens.forEach( ( token ) => {
			const label = typeof token === 'string' ? token : token?.value;
			if ( ! label || seen.has( label ) ) {
				return;
			}
			seen.add( label );

			const id = labelToId.get( label );
			if ( ! id ) {
				return;
			}
			nextIds.push( id );
			const item = knownItemsById.get( id );
			if ( item ) {
				nextSelectedItems.push( item );
			}
		} );

		setSelectedItems( nextSelectedItems );
		onChange( nextIds );
	};

	return (
		<div className="sensei-export__item-picker">
			<FormTokenField
				label={ postTypeLabels[ type ] }
				value={ tokenValues }
				suggestions={ suggestions }
				onInputChange={ setInputValue }
				onChange={ onTokensChange }
				__experimentalExpandOnFocus
				__experimentalShowHowTo={ false }
				placeholder={ __( 'Search to add items…', 'sensei-lms' ) }
			/>
			<p className="sensei-export__item-picker__hint">
				{ __(
					'Leave empty to export all of this type. Selecting items also exports their lessons and questions where applicable.',
					'sensei-lms'
				) }
			</p>
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
