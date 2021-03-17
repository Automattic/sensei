/**
 * WordPress dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { dispatch, useDispatch, useSelect } from '@wordpress/data';
import {
	createContext,
	useCallback,
	useContext,
	useMemo,
} from '@wordpress/element';
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
 * @type {string|Object}
 */
export const BLOCK_META_STORE = createStore( 'sensei/block-metadata', store );

/**
 * Block metadata context for providing parent block meta to inner blocks.
 */
const BlockMetaContext = createContext( {} );

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

	const contextMeta = useBlockMetaContext();

	const setBlockMetaForBlock = useCallback(
		( data ) => setBlockMeta( clientId, data ),
		[ clientId, setBlockMeta ]
	);

	const meta = useMemo( () => ( { ...contextMeta, ...blockMeta } ), [
		blockMeta,
		contextMeta,
	] );

	return [ meta, setBlockMetaForBlock ];
};

/**
 * Provide this block's meta to its inner blocks.
 *
 * The provided parent meta entries will be merged into the inner blocks meta objects.
 */
export const withBlockMetaProvider = createHigherOrderComponent( ( Block ) => {
	return withBlockMeta( ( props ) => {
		const [ blockMeta ] = useBlockMeta( props.clientId );
		return (
			<BlockMetaContext.Provider value={ blockMeta }>
				<Block { ...props } />
			</BlockMetaContext.Provider>
		);
	} );
}, 'withBlockMetaProvider' );

/**
 * Get the parent block's (withBlockMetaProvider) metadata.
 *
 * @return {Object} Contextual meta.
 */
export const useBlockMetaContext = () => useContext( BlockMetaContext );

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
