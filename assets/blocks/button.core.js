import { registerBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

addFilter( 'blocks.registerBlockType', 'sensei-lms/button', getButtonDef );

let registered = false;
function getButtonDef( settings ) {
	if ( settings.name === 'core/button' && ! registered ) {
		registered = true;
		registerButton( settings );
	}

	return settings;
}

function registerButton( settings ) {
	registerBlockType( 'sensei-lms/button-core', {
		...settings,
		//...metadata,
		name: 'sensei-lms/button-core',
		title: 'Take Course Core',
		parent: null,
		attributes: {
			...settings.attributes,
			text: {
				...settings.attributes.text,
				default: 'Take Course',
			},
		},
		edit( props ) {
			const ButtonEdit = settings.edit;
			return (
				<>
					<ButtonEdit { ...props } />
				</>
			);
		},
	} );
}
