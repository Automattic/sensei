import {
	BlockControls,
	BlockSettingsMenuControls,
} from '@wordpress/block-editor';
import {
	Button,
	Dropdown,
	NavigableMenu,
	Toolbar,
	MenuItem,
} from '@wordpress/components';
import { RestrictOptions, RestrictOptionLabels } from './edit';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Check if the current block is the only one that is selected.
 *
 * @param {string} clientId The block client id.
 */
const useIsSingleRestrictSelected = function ( clientId ) {
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
 * A hook that returns a function which unwraps the inner blocks from the restricted content block.
 *
 * @param {string} clientId The block client id.
 */
const useOnRestrictionRemoval = function ( clientId ) {
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
 * The restricted content block settings.
 *
 * @param {Object}   props                     Component properties.
 * @param {string}   props.selectedRestriction The restriction that is currently selected.
 * @param {Function} props.onRestrictionChange Callback which is called when a new option is selected.
 * @param {string}   props.clientId            The block client id.
 * @param {boolean}  props.hasInnerBlocks      True if there are inner blocks.
 */
export function RestrictedContentSettings( {
	selectedRestriction,
	onRestrictionChange,
	clientId,
	hasInnerBlocks,
} ) {
	const isSingleRestrictSelected = useIsSingleRestrictSelected( clientId );
	const onRestrictRemoval = useOnRestrictionRemoval( clientId );

	return (
		<>
			<BlockControls>
				<Toolbar>
					<Dropdown
						className="wp-block-sensei-lms-restricted-toggle"
						contentClassName="wp-block-sensei-lms-restricted-content"
						position="bottom center"
						renderToggle={ ( { isOpen, onToggle } ) => (
							<Button
								className="wp-block-sensei-lms-restricted-toggle-button"
								onClick={ onToggle }
								aria-expanded={ isOpen }
								aria-haspopup="true"
							>
								{ RestrictOptionLabels[ selectedRestriction ] }
							</Button>
						) }
						renderContent={ ( { onClose } ) => {
							return (
								<NavigableMenu role="menu" stopNavigationEvents>
									{ Object.values( RestrictOptions ).map(
										( option ) => (
											<Button
												key={ option }
												className="wp-block-sensei-lms-restricted-content-button"
												onClick={ () => {
													onRestrictionChange(
														option
													);
													onClose();
												} }
												role="menuitem"
											>
												{
													RestrictOptionLabels[
														option
													]
												}
											</Button>
										)
									) }
								</NavigableMenu>
							);
						} }
					/>
				</Toolbar>
			</BlockControls>
			{ isSingleRestrictSelected && hasInnerBlocks && (
				<BlockSettingsMenuControls>
					{ ( { onClose } ) => (
						<MenuItem
							onClick={ () => {
								onRestrictRemoval();
								onClose();
							} }
						>
							{ __( 'Remove restriction', 'sensei-lms' ) }
						</MenuItem>
					) }
				</BlockSettingsMenuControls>
			) }
		</>
	);
}
