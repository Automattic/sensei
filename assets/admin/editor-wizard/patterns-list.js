/**
 * WordPress dependencies
 */
import {
	store as blockEditorStore,
	BlockPreview,
} from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';

const PatternsList = () => {
	const { patterns } = useSelect( ( select ) => ( {
		patterns: select( blockEditorStore ).__experimentalGetAllowedPatterns(),
	} ) );
	const { resetEditorBlocks } = useDispatch( editorStore );

	return patterns
		.filter( ( { categories } ) => categories.includes( 'sensei-lms' ) )
		.map( ( { name, title, description, blocks, viewportWidth } ) => (
			// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
			<div
				key={ name }
				title={ description }
				onClick={ () => {
					resetEditorBlocks( blocks );
				} }
			>
				<BlockPreview
					blocks={ blocks }
					viewportWidth={ viewportWidth }
				/>
				<div>{ title }</div>
			</div>
		) );
};

export default PatternsList;
