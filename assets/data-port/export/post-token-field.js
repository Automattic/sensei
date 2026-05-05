/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { FormTokenField } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

const REST_BASE_BY_TYPE = {
	course: 'courses',
	lesson: 'lessons',
	question: 'questions',
};

const SUGGESTION_LIMIT = 20;

const titleOf = ( item ) =>
	decodeEntities( item.title?.rendered || item.title?.raw || '' );

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
	const title = titleOf( item );
	const collides = pool.some(
		( other ) => other.id !== item.id && titleOf( other ) === title
	);
	const safeTitle = title || __( '(no title)', 'sensei-lms' );
	return collides || ! title ? `${ safeTitle } (#${ item.id })` : safeTitle;
};

/**
 * FormTokenField wired to a /wp/v2/{type} REST endpoint. Fetches up
 * to SUGGESTION_LIMIT items as the user types and exposes the picked
 * post IDs to the parent via onChange.
 *
 * @param {Object}   props
 * @param {string}   props.type        Content type ('course', 'lesson', 'question').
 * @param {string}   props.ariaLabel   Accessible name for the field (no visible label is rendered).
 * @param {string}   props.placeholder Placeholder text inside the field.
 * @param {number[]} props.selectedIds Currently-selected post IDs.
 * @param {Function} props.onChange    Called with the next ID array.
 */
export const PostTokenField = ( {
	type,
	ariaLabel,
	placeholder,
	selectedIds,
	onChange,
} ) => {
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
			const tokenLabel = typeof token === 'string' ? token : token?.value;
			if ( ! tokenLabel || seen.has( tokenLabel ) ) {
				return;
			}
			seen.add( tokenLabel );

			const id = labelToId.get( tokenLabel );
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
		<FormTokenField
			label={ ariaLabel }
			value={ tokenValues }
			suggestions={ suggestions }
			onInputChange={ setInputValue }
			onChange={ onTokensChange }
			__experimentalExpandOnFocus
			__experimentalAutoSelectFirstMatch
			help=""
			placeholder={ placeholder }
		/>
	);
};
