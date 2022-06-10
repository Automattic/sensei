/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
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
 * Hook to set the default post pattern with replaced content.
 *
 * @param {Object} replaces Object containing content to be replaced. The keys are the block
 *                          classNames to find. The values are the content to be replaced.
 *
 * @return {Function} Function to set the default pattern.
 */
export const useSetDefaultPattern = ( replaces ) => {
	const { patterns } = useSelect( ( select ) => ( {
		patterns: select(
			blockEditorStore
		).__experimentalGetPatternsByBlockTypes( 'sensei-lms/post-content' ),
	} ) );
	const { template } = useSelect( ( select ) => ( {
		template: select( blockEditorStore ).getTemplate(),
	} ) );
	const { resetBlocks } = useDispatch( blockEditorStore );

	// Get the default pattern based on what's set in the template.
	const pattern = patterns.find(
		( p ) => p.name === template?.[ 0 ]?.[ 1 ]?.slug
	);

	// Set default pattern with replaced content.
	return () => {
		if ( ! pattern ) {
			return;
		}

		const replacedBlocks = replacePlaceholders( pattern.blocks, replaces );
		resetBlocks( replacedBlocks );
	};
};
