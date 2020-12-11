import { BlockControls } from '@wordpress/block-editor';
import {
	Button,
	Dropdown,
	NavigableMenu,
	Toolbar,
} from '@wordpress/components';
import { RestrictOptions, RestrictOptionLabels } from './edit';

/**
 * The restricted content block settings.
 *
 * @param {Object}   props                     Component properties.
 * @param {number}   props.selectedRestriction The restriction that is currently selected.
 * @param {Function} props.onRestrictionChange Callback which is called when a new option is selected.
 */
export function RestrictedContentSettings( {
	selectedRestriction,
	onRestrictionChange,
} ) {
	return (
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
												onRestrictionChange( option );
												onClose();
											} }
											role="menuitem"
										>
											{ RestrictOptionLabels[ option ] }
										</Button>
									)
								) }
							</NavigableMenu>
						);
					} }
				/>
			</Toolbar>
		</BlockControls>
	);
}
