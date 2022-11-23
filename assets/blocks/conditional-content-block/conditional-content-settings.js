/**
 * WordPress dependencies
 */
import {
	BlockControls,
	BlockSettingsMenuControls,
} from '@wordpress/block-editor';
import { ToolbarGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Conditions, ConditionLabels } from './conditional-content-edit';
import ToolbarDropdown from '../editor-components/toolbar-dropdown';

/**
 * Check if the current block is the only one that is selected.
 *
 * @param {string} clientId The block client id.
 */
const useIsConditionalBlockSelected = ( clientId ) => {
	return useSelect(
		( select ) => {
			const selectedClientIds = select(
				'core/block-editor'
			).getSelectedBlockClientIds();

			return (
				selectedClientIds.length === 1 &&
				selectedClientIds[ 0 ] === clientId
			);
		},
		[ clientId ]
	);
};

/**
 * A hook that returns a function which unwraps the inner blocks from the conditional content block.
 *
 * @param {string} clientId The block client id.
 */
const useOnConditionRemoval = ( clientId ) => {
	const block = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( clientId ),
		[ clientId ]
	);
	const { replaceBlocks } = useDispatch( 'core/block-editor' );

	return () => {
		if ( block.innerBlocks.length ) {
			replaceBlocks( clientId, block.innerBlocks );
		}
	};
};

/**
 * The conditional content block settings.
 *
 * @param {Object}   props                   Component properties.
 * @param {string}   props.selectedCondition The condition that is currently selected.
 * @param {Function} props.onConditionChange Callback which is called when a new option is selected.
 * @param {string}   props.clientId          The block client id.
 * @param {boolean}  props.hasInnerBlocks    True if there are inner blocks.
 */
const ConditionalContentSettings = ( {
	selectedCondition,
	onConditionChange,
	clientId,
	hasInnerBlocks,
} ) => {
	const isConditionalBlockSelected = useIsConditionalBlockSelected(
		clientId
	);
	const onConditionRemoval = useOnConditionRemoval( clientId );

	const toolbarOptions = Object.keys( Conditions ).map( ( optionKey ) => ( {
		value: Conditions[ optionKey ],
		label: ConditionLabels[ Conditions[ optionKey ] ],
	} ) );

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdown
						options={ toolbarOptions }
						optionsLabel={ __( 'Visible when', 'sensei-lms' ) }
						value={ selectedCondition }
						onChange={ onConditionChange }
					/>
				</ToolbarGroup>
			</BlockControls>
			{ isConditionalBlockSelected &&
				hasInnerBlocks &&
				BlockSettingsMenuControls && (
					<BlockSettingsMenuControls>
						{ ( { onClose } ) => (
							<MenuItem
								onClick={ () => {
									onConditionRemoval();
									onClose();
								} }
							>
								{ __( 'Remove condition', 'sensei-lms' ) }
							</MenuItem>
						) }
					</BlockSettingsMenuControls>
				) }
		</>
	);
};

export default ConditionalContentSettings;
