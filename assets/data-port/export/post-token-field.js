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

// `title.rendered` in `view` context, `title.raw` in `edit` — accept either.
const titleOf = ( item ) =>
	decodeEntities( item.title?.rendered || item.title?.raw || '' );

/**
 * Build a unique label for a post. When two posts share a title we
 * disambiguate with the post ID so FormTokenField (which works on
 * strings) can map labels back to IDs unambiguously.
 *
 * @param {Object}   item       Post item ({ id, title }).
 * @param {Object[]} knownItems Items in the same context to check for collisions.
 * @return {string} Display label for the token.
 */
const buildLabel = ( item, knownItems ) => {
	const title = titleOf( item );
	const collides = knownItems.some(
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
 * @param {string}   props.type           Content type ('course', 'lesson', 'question').
 * @param {string}   props.ariaLabel      Accessible name for the field (no visible label is rendered).
 * @param {string}   props.placeholder    Placeholder text inside the field.
 * @param {number[]} props.selectedIds    Currently-selected post IDs.
 * @param {Function} props.onChange       Called with the next ID array.
 * @param {Map}      props.cachedItems    Parent-owned id→item cache for this type.
 * @param {Function} props.onItemsFetched Called with the array of items returned by each suggestion fetch so the parent can merge them into its cache.
 */
export const PostTokenField = ( {
	type,
	ariaLabel,
	placeholder,
	selectedIds,
	onChange,
	cachedItems,
	onItemsFetched,
} ) => {
	const [ inputValue, setInputValue ] = useState( '' );
	const [ debouncedInput, setDebouncedInput ] = useState( '' );
	const [ suggestionIds, setSuggestionIds ] = useState( [] );

	// Debounce the search input so we don't fire a REST request per keystroke.
	useEffect( () => {
		const handle = setTimeout( () => setDebouncedInput( inputValue ), 300 );
		return () => clearTimeout( handle );
	}, [ inputValue ] );

	// `cancelled` guards against an older request resolving after a newer one
	// and clobbering its result.
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
				if ( cancelled ) {
					return;
				}
				onItemsFetched( items );
				setSuggestionIds( items.map( ( item ) => item.id ) );
			} )
			.catch( ( error ) => {
				if ( cancelled ) {
					return;
				}
				setSuggestionIds( [] );
				// Suggestions failing leaves the dropdown empty, which is
				// indistinguishable from "no matches" for the user. Surface
				// the underlying reason for the developer console at least.
				window.console?.warn(
					`[sensei export] failed to fetch ${ type } suggestions`,
					error
				);
			} );

		return () => {
			cancelled = true;
		};
		// `onItemsFetched` is intentionally excluded; including it would re-run
		// the effect on every parent render whenever the parent recreates the
		// callback inline. The effect only needs to re-fire on type/search.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ type, debouncedInput ] );

	const knownItems = Array.from( cachedItems.values() );

	// Fall back to "#<id>" when an id isn't in the cache yet — e.g. the page
	// mounted with pre-selected ids we haven't fetched titles for.
	const tokenForId = ( id ) => {
		const item = cachedItems.get( id );
		if ( ! item ) {
			return `#${ id }`;
		}
		return buildLabel( item, knownItems );
	};

	const tokenValues = selectedIds.map( tokenForId );

	// FormTokenField gives us back labels (the displayed strings), not ids.
	// Convert that label list back into the id list our parent expects.
	const onTokensChange = ( tokens ) => {
		// Build a label → id index from every cached item, using the same
		// `buildLabel` rules so the strings here match what the field renders.
		const labelToId = new Map();
		knownItems.forEach( ( item ) =>
			labelToId.set( buildLabel( item, knownItems ), item.id )
		);

		const nextIds = [];
		const seen = new Set();

		tokens.forEach( ( token ) => {
			// Tokens come in as either a plain string or a `{ value }` object,
			// depending on FormTokenField internals — normalize to a string.
			const tokenLabel = typeof token === 'string' ? token : token?.value;

			// Skip empties and duplicates (the field allows the same string
			// to appear twice; we don't want the same post twice).
			if ( ! tokenLabel || seen.has( tokenLabel ) ) {
				return;
			}
			seen.add( tokenLabel );

			// If the label doesn't resolve to an id it's free text the user
			// typed but didn't pick from suggestions — silently drop it.
			const id = labelToId.get( tokenLabel );
			if ( id ) {
				nextIds.push( id );
			}
		} );

		onChange( nextIds );
	};

	// Drop already-selected ids and render the rest as labels for the dropdown.
	const suggestions = suggestionIds
		.filter( ( id ) => ! selectedIds.includes( id ) )
		.map( ( id ) => buildLabel( cachedItems.get( id ), knownItems ) );

	return (
		<FormTokenField
			label={ ariaLabel }
			value={ tokenValues }
			suggestions={ suggestions }
			onInputChange={ setInputValue }
			onChange={ onTokensChange }
			__experimentalExpandOnFocus
			__experimentalAutoSelectFirstMatch
			__experimentalShowHowTo={ false }
			__next40pxDefaultSize
			placeholder={ placeholder }
		/>
	);
};
