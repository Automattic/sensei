import { registerBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

addFilter( 'blocks.registerBlockType', 'sensei-lms/button', getButtonDef );

// import {
// 	settings,
// 	metadata,
// } from '../../../node_modules/@wordpress/block-library/src/button';

function getButtonDef( settings ) {
	if ( settings.name === 'core/button' ) {
		registerButton( settings );
	}

	return settings;
}

function registerButton( settings ) {
	registerBlockType( 'sensei-lms/button', {
		...settings,
		//...metadata,
		name: 'sensei-lms/button',
		title: 'Take Course',
		parent: null,
		edit( props ) {
			const ButtonEdit = settings.edit;

			const r = <ButtonEdit { ...props } />;
			const children = r.props.children.filter(
				( c ) => c.type.name !== 'URLPicker'
			);
			return <>{ children }</>;
		},
	} );
}
