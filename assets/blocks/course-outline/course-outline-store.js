/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerStructureStore } from '../../shared/structure/structure-store';

import {
	syncStructureToBlocks,
	extractStructure,
	getFirstBlockByName,
} from './data';

const getEditorOutlineBlock = () =>
	getFirstBlockByName(
		'sensei-lms/course-outline',
		select( 'core/block-editor' ).getBlocks()
	);

const getEditorOutlineStructure = () => {
	const outlineBlock = getEditorOutlineBlock();

	if ( ! outlineBlock ) {
		return null;
	}

	return { structure: extractStructure( outlineBlock.innerBlocks ) };
};

export const COURSE_STORE = 'sensei/course-structure';

registerStructureStore( {
	storeName: COURSE_STORE,
	*getEndpoint() {
		const courseId = yield select( 'core/editor' ).getCurrentPostId();
		return `course-structure/${ courseId }?context=edit`;
	},
	*updateBlock( structure ) {
		const { clientId = null } = getEditorOutlineBlock();

		if ( ! clientId || ! structure || 0 === structure.length ) {
			return;
		}

		const blocks = yield select( 'core/block-editor' ).getBlocks(
			clientId
		);
		yield dispatch( 'core/block-editor' ).replaceInnerBlocks(
			clientId,
			syncStructureToBlocks( structure, blocks ),
			false
		);
	},
	readBlock: getEditorOutlineStructure,
	*saveError( error ) {
		const errorMessage = sprintf(
			/* translators: Error message. */
			__(
				'Course modules and lessons could not be updated. %s',
				'sensei-lms'
			),
			error.message
		);
		yield dispatch( 'core/notices' ).createErrorNotice( errorMessage, {
			id: 'course-outline-save-error',
		} );
	},
	clearError() {
		// Clear error notices.
		dispatch( 'core/notices' ).removeNotice( 'course-outline-save-error' );
	},

	/**
	 * Prepend structure in server's response.
	 *
	 * @param {Object} structure The structure response.
	 *
	 * @return {Object} The modified response.
	 */
	setServerStructure( structure ) {
		if ( ! structure ) {
			return {};
		}

		return { structure };
	},
} );
