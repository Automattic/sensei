/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { select, subscribe, dispatch, use } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';
import { find as findObject } from 'lodash';
/**
 * Internal dependencies
 */
import { getFirstBlockByName } from '../../blocks/course-outline/data';

export const hasOutlineBlock = () =>
	getFirstBlockByName(
		'sensei-lms/course-outline',
		select( 'core/block-editor' ).getBlocks()
	);

export const handleCourseOutlineBlockIncomplete = async () => {
	let courseOutlineBlock = hasOutlineBlock();

	// If the course outline block doesn't exist, create it and insert it.
	if ( ! courseOutlineBlock ) {
		const { insertBlock } = dispatch( 'core/block-editor' );

		courseOutlineBlock = createBlock( 'sensei-lms/course-outline' );
		await insertBlock( courseOutlineBlock );
	}

	dispatch( 'core/editor' ).selectBlock( courseOutlineBlock.clientId );
};

// If the function isn't globally available, the link button doesn't find the reference.
window.handleCourseOutlineBlockIncomplete = handleCourseOutlineBlockIncomplete;

export const hasPublishedLessonInOutline = ( blocks ) => {
	return blocks.some( ( block ) => {
		if (
			block.name === 'sensei-lms/course-outline-lesson' &&
			! block.attributes.draft
		) {
			return true;
		}

		if ( block.innerBlocks?.length ) {
			return hasPublishedLessonInOutline( block.innerBlocks );
		}

		return false;
	} );
};

// Detect when a user selects a Sensei pattern.
let selectedSenseiPattern = false;

const rewrittenActions = {};
const originalActions = {};

const checkIfAnySenseiPatternGotInserted = ( ...args ) => {
	const meta = findObject( args, ( item ) => item?.patternName );
	let patternName = meta?.patternName;
	if ( patternName && patternName.includes( 'sensei' ) ) {
		selectedSenseiPattern = true;
	}
};

const REDUX_ACTION_OVERRIDERS = {
	'core/block-editor': {
		insertBlocks: checkIfAnySenseiPatternGotInserted,
	},
	'core/editor': {
		resetEditorBlocks: checkIfAnySenseiPatternGotInserted,
	},
};

use( ( registry ) => ( {
	dispatch: ( namespace ) => {
		const namespaceName =
			typeof namespace === 'object' ? namespace.name : namespace;
		const actions = { ...registry.dispatch( namespaceName ) };
		const overriderActions = REDUX_ACTION_OVERRIDERS[ namespaceName ];

		if ( overriderActions ) {
			Object.keys( overriderActions ).forEach( ( actionName ) => {
				const originalAction = actions[ actionName ];
				const newAction = overriderActions[ actionName ];

				if ( ! rewrittenActions[ namespaceName ] ) {
					rewrittenActions[ namespaceName ] = {};
				}
				if ( ! originalActions[ namespaceName ] ) {
					originalActions[ namespaceName ] = {};
				}

				if (
					! originalActions[ namespaceName ][ actionName ] ||
					originalActions[ namespaceName ][ actionName ] !==
						originalAction
				) {
					originalActions[ namespaceName ][
						actionName
					] = originalAction;
					rewrittenActions[ namespaceName ][ actionName ] = (
						...args
					) => {
						try {
							newAction( ...args );
						} catch ( err ) {}
						return originalAction( ...args );
					};
				}

				actions[ actionName ] =
					rewrittenActions[ namespaceName ][ actionName ];
			} );
		}

		return actions;
	},
} ) );

export const handleFirstCourseCreationHelperNotice = () => {
	const { createInfoNotice, removeNotice } = dispatch( 'core/notices' );
	const userId = select( 'core' ).getCurrentUser()?.id;
	const firstCourseNoticeDismissedKey =
		'sensei-lms-first-course-notice-dismissed-' + userId;
	const isFirstCourseNoticeDismissed = !! window.localStorage.getItem(
		firstCourseNoticeDismissedKey
	);
	const noticeId = 'course-outline-block-setup-incomplete';

	let noticeCreated = false;
	let noticeRemoved = false;

	const notice = __(
		'Nice! Now you can <a href="javascript:;" onclick="window?.handleCourseOutlineBlockIncomplete();">add some lessons</a> to your course.',
		'sensei-lms'
	);

	subscribe( () => {
		if (
			selectedSenseiPattern &&
			noticeCreated &&
			! noticeRemoved &&
			hasOutlineBlock() &&
			hasPublishedLessonInOutline( [ hasOutlineBlock() ] )
		) {
			noticeRemoved = true;
			noticeCreated = false;
			removeNotice( noticeId );
		}

		// If the user selects a Sensei pattern, and the notice hasn't been created, and notice hasn't been dismissed, and either the course outline block hasn't been created OR there are no published lessons in the outline, create the notice.
		if (
			selectedSenseiPattern &&
			! noticeCreated &&
			! isFirstCourseNoticeDismissed &&
			! (
				hasOutlineBlock() &&
				hasPublishedLessonInOutline( [ hasOutlineBlock() ] )
			)
		) {
			noticeCreated = true;
			noticeRemoved = false;

			createInfoNotice( notice, {
				id: noticeId,
				isDismissible: true,
				__unstableHTML: true, // Necessary to render the link in the middle of the notice message.
				onDismiss: () => {
					window.localStorage.setItem(
						firstCourseNoticeDismissedKey,
						'1'
					);
				},
			} );
		}
	} );
};

// Call function on dom ready.
domReady( handleFirstCourseCreationHelperNotice );
