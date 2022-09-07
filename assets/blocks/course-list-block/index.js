/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';
import { list } from '@wordpress/icons';
import { select, subscribe } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './hooks';

export const registerCourseListBlock = () => {
	const DEFAULT_ATTRIBUTES = {
		className: 'wp-block-sensei-lms-course-list',
		query: {
			perPage: 3,
			pages: 0,
			offset: 0,
			postType: 'course',
			order: 'desc',
			orderBy: 'date',
			author: '',
			search: '',
			exclude: [],
			sticky: '',
			inherit: false,
		},
	};

	registerBlockVariation( 'core/query', {
		name: 'sensei-lms/course-list',
		title: __( 'Course List', 'sensei-lms' ),
		description: __( 'Show a list of courses.', 'sensei-lms' ),
		icon: list,
		category: 'sensei-lms',
		keywords: [
			__( 'Course', 'sensei-lms' ),
			__( 'List', 'sensei-lms' ),
			__( 'Courses', 'sensei-lms' ),
		],
		attributes: { ...DEFAULT_ATTRIBUTES },
		isActive: ( blockAttributes, variationAttributes ) => {
			// Using className instead of postType because otherwise a normal Query Loop block
			// will turn into a Course List block if the post type 'course' is selected. As we're planning
			// to hide the Post Type dropdown for Course List block, so after changing the type to course,
			// the Query loop user will not be able to change the post type again. We don't want that to
			// happen.
			return (
				blockAttributes.className?.match(
					variationAttributes.className
				) &&
				blockAttributes.query.postType ===
					variationAttributes.query.postType
			);
		},
		scope: [ 'inserter' ],
	} );
};

const unsubscribe = subscribe( () => {
	const blockSettingsPanel = document.querySelector(
		'.interface-interface-skeleton__sidebar'
	);
	if ( ! blockSettingsPanel ) {
		return;
	}
	observeAndRemoveSettingsFromPanel( blockSettingsPanel );
	unsubscribe();
} );

const observeAndRemoveSettingsFromPanel = ( blockSettingsPanel ) => {
	// eslint-disable-next-line no-undef
	const observer = new MutationObserver( () => {
		const selectedBlock = select( 'core/block-editor' ).getSelectedBlock();

		if ( 'core/query' !== selectedBlock?.name ) {
			return;
		}

		if (
			selectedBlock?.attributes?.className?.includes(
				'wp-block-sensei-lms-course-list'
			)
		) {
			hideUnnecessarySettingsForCourseList();
		} else if ( document.querySelector( '.border-block-support-panel' ) ) {
			// Hide border setting for Query Loop block (leave for Course List block).
			document.querySelector(
				'.border-block-support-panel'
			).style.display = 'none';
		}
	} );

	// configuration for settings panel observer.
	const config = { childList: true, subtree: true };

	// pass in the settings panel node, as well as the options.
	observer.observe( blockSettingsPanel, config );
};

// Hide the settings which are inherited from the Query Loop block
// but not applicable to our Course List block.
const hideUnnecessarySettingsForCourseList = () => {
	const postTypeContainerQuery = '.components-input-control__label',
		inheritContextContainerQuery = '.components-toggle-control__label';

	const toBeHiddenSettingContainers = document.querySelectorAll(
		`${ postTypeContainerQuery },${ inheritContextContainerQuery }`
	);

	if (
		! toBeHiddenSettingContainers ||
		0 === toBeHiddenSettingContainers.length
	) {
		return;
	}

	Array.from( toBeHiddenSettingContainers ).forEach( ( element ) => {
		if (
			[
				/* eslint-disable-next-line @wordpress/i18n-text-domain */
				__( 'Post type' ).toLowerCase(),
				/* eslint-disable-next-line @wordpress/i18n-text-domain */
				__( 'Inherit query from template' ).toLowerCase(),
			].includes( element.textContent.toLowerCase() )
		) {
			element.closest( '.components-base-control' ).style.display =
				'none';
		}
	} );
};
