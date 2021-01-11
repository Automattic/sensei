import { registerBlockType, updateCategory } from '@wordpress/blocks';

import { SenseiIcon } from '../icons';

const blocksSetup = ( blocks ) => {
	updateCategory( 'sensei-lms', {
		icon: SenseiIcon( { width: '20', height: '20' } ),
	} );

	blocks.forEach( ( block ) => {
		const { name, ...settings } = block;
		registerBlockType( name, settings );
	} );
};

export default blocksSetup;
