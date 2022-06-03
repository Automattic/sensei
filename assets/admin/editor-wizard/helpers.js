/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { synchronizeBlocksWithTemplate } from '@wordpress/blocks';
import { useEffect, useLayoutEffect, useState } from '@wordpress/element';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Update blocks content, replacing the placeholders with a content.
 *
 * @param {Object[]} blocks   Blocks to replace with the new content.
 * @param {Object}   replaces Object containing content to be replaced. The keys are the block
 *                            classNames to find. The values are the content to be replaced.
 *
 * @return {Object[]} Blocks with the placeholders filled.
 */
export const replacePlaceholders = ( blocks, replaces ) =>
	blocks.map( ( block ) => {
		const { className = '' } = block.attributes;
		const replacesArray = Object.entries( replaces );

		replacesArray.forEach( ( [ placeholder, content ] ) => {
			if ( className.includes( placeholder ) ) {
				block.attributes.content = content;
			}
		} );

		if ( block.innerBlocks ) {
			block.innerBlocks = replacePlaceholders(
				block.innerBlocks,
				replaces
			);
		}

		return block;
	} );

/**
 * A React Hook to observe if a modal is open based on the body class.
 *
 * @param {boolean} shouldObserve If it should observe the changes.
 *
 * @return {boolean|undefined} Whether a modal is open, or `undefined` if it's not initialized yet.
 */
const useObserveOpenModal = ( shouldObserve ) => {
	const [ hasOpenModal, setHasOpenModal ] = useState();

	useEffect( () => {
		if ( ! shouldObserve ) {
			return;
		}

		// Initialize state after modals are open or not.
		setTimeout( () => {
			setHasOpenModal( document.body.classList.contains( 'modal-open' ) );
		}, 1 );

		const observer = new window.MutationObserver( () => {
			setHasOpenModal( document.body.classList.contains( 'modal-open' ) );
		} );
		observer.observe( document.body, {
			attributes: true,
			attributeFilter: [ 'class' ],
		} );

		return () => {
			observer.disconnect();
		};
	}, [ shouldObserve ] );

	return hasOpenModal;
};

/**
 * A React Hook to control the wizard open state.
 *
 * @return {boolean} Whether the modal should be open.
 */
export const useWizardOpenState = () => {
	const [ open, setOpen ] = useState( false );
	const [ done, setDone ] = useState( false );
	const hasOpenModal = useObserveOpenModal( ! done );

	useLayoutEffect( () => {
		if ( done ) {
			setOpen( false );
		} else if ( false === hasOpenModal ) {
			// If no modal is open, it's time to open.
			setOpen( true );
		}
	}, [ done, hasOpenModal ] );

	return [ open, setDone ];
};

/**
 * Default template hook that sets the default post template with replaced content.
 *
 * @param {Object} replaces Object containing content to be replaced. The keys are the block
 *                          classNames to find. The values are the content to be replaced.
 *
 * @return {Function} Function to set the default template.
 */
export const useDefaultTemplate = ( replaces ) => {
	const { resetBlocks } = useDispatch( blockEditorStore );
	const { blocks, template } = useSelect( ( select ) => ( {
		blocks: select( blockEditorStore ).getBlocks(),
		template: select( blockEditorStore ).getTemplate(),
	} ) );

	// Set default template with replaced content.
	return () => {
		const templateBlocks = synchronizeBlocksWithTemplate(
			blocks,
			template
		);
		const replacedBlocks = replacePlaceholders( templateBlocks, replaces );

		resetBlocks( replacedBlocks );
	};
};
