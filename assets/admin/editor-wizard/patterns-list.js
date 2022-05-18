/**
 * WordPress dependencies
 */
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalBlockPatternsList as BlockPatternsList,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';

const PatternsList = () => {
	const { patterns } = useSelect( ( select ) => ( {
		patterns: select( blockEditorStore ).__experimentalGetAllowedPatterns(),
	} ) );
	const { resetEditorBlocks } = useDispatch( editorStore );

	return (
		<BlockPatternsList
			blockPatterns={ patterns }
			shownPatterns={ patterns }
			onClickPattern={ ( pattern, blocks ) => {
				resetEditorBlocks( blocks );
			} }
		/>
	);
};

export default PatternsList;
