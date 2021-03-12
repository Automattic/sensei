/**
 * WordPress dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { dispatch, useDispatch, useSelect } from '@wordpress/data';
/**
 * External dependencies
 */
import { pick, mapValues } from 'lodash';
/**
 * Internal dependencies
 */
import { createReducerFromActionMap, createStore } from '../data/store-helpers';

/**
 * Block metadata store definition.
 */
const store = {
	reducer: createReducerFromActionMap(
		{
			SET_BLOCK_META: ( { clientId, blockState }, state ) => ( {
				...state,
				[ clientId ]: { ...( state[ clientId ] || {} ), ...blockState },
			} ),
			CLEAR: ( state, clientId ) => {
				if ( clientId ) {
					return { ...state, [ clientId ]: undefined };
				}
				return {};
			},
			DEFAULT: ( state ) => state,
		},
		{}
	),
	actions: {
		setBlockMeta( clientId, blockState ) {
			return { type: 'SET_BLOCK_META', clientId, blockState };
		},
		clear: ( clientId = null ) => ( { type: 'CLEAR', clientId } ),
	},
	selectors: {
		getBlockMeta: ( state, clientId, key = null ) =>
			key ? state[ clientId ]?.[ key ] : state[ clientId ],
		getMultipleBlockMeta: ( state, clientIds = [], key = null ) => {
			const blocks = clientIds?.length
				? pick( state, clientIds )
				: { ...state };
			return key ? mapValues( blocks, key ) : blocks;
		},
	},
};

/**
 * Block metadata store.
 *
 * @type {string}
 */
export const BLOCK_META_STORE = createStore( 'sensei/block-metadata', store );

/**
 * Use metadata for the block.
 *
 * @param {string} clientId Block ID.
 * @return {[Object, Function]} Metadata and setter function.
 */
export const useBlockMeta = ( clientId ) => {
	const blockMeta = useSelect(
		( select ) => select( BLOCK_META_STORE ).getBlockMeta( clientId ),
		[ clientId ]
	);
	const { setBlockMeta } = useDispatch( BLOCK_META_STORE );

	const setBlockMetaForBlock = ( data ) => setBlockMeta( clientId, data );

	return [ blockMeta || {}, setBlockMetaForBlock ];
};

/**
 * Attach metadata store to the block.
 * Provides the following props:
 *
 * @property {Object}   meta    Block metadata.
 * @property {Function} setMeta Block metadata setter.
 */
export const withBlockMeta = createHigherOrderComponent( ( Block ) => {
	return ( props ) => {
		const [ blockMeta, setBlockMeta ] = useBlockMeta( props.clientId );
		return (
			<Block { ...props } meta={ blockMeta } setMeta={ setBlockMeta } />
		);
	};
}, 'withBlockMeta' );

/**
 * Set metadata for a block.
 *
 * @param {string} clientId Block ID.
 * @param {Object} data     Metadata to update.
 */
export const setBlockMeta = ( clientId, data ) =>
	dispatch( BLOCK_META_STORE ).setBlockMeta( clientId, data );
