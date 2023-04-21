/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';
import { select, subscribe } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './hooks';
import icon from '../../icons/course-list.svg';
import FeaturedLabel from './featured-label';

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
		icon,
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

/**
 * Add a HOC to a featured image block.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 */
export function addWrapperAroundFeaturedImageBlock( settings, name ) {
	if ( 'core/post-featured-image' !== name ) {
		return settings;
	}

	const BlockEdit = settings.edit;

	settings = {
		...settings,
		edit: ( props ) => {
			const shouldWrap =
				props.context?.postType === 'course' &&
				!! props.context?.queryId;

			if ( ! shouldWrap ) {
				return <BlockEdit { ...props } />;
			}

			return (
				<FeaturedLabel
					postId={ props.context.postId }
					isFeaturedImage={ true }
				>
					<BlockEdit { ...props } />
				</FeaturedLabel>
			);
		},
	};
	return settings;
}

addFilter(
	'blocks.registerBlockType',
	'sensei-lms/course-list-block',
	addWrapperAroundFeaturedImageBlock
);

/**
 * Add a HOC to a featured course categories block.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 */
export function addWrapperAroundCourseCategoriesBlock( settings, name ) {
	if ( 'sensei-lms/course-categories' !== name ) {
		return settings;
	}

	const BlockEdit = settings.edit;
	settings.attributes.align = false;

	settings = {
		...settings,
		edit: ( props ) => {
			const shouldWrap =
				props.context?.postType === 'course' &&
				!! props.context?.queryId;

			if ( ! shouldWrap ) {
				return <BlockEdit { ...props } />;
			}

			return (
				<FeaturedLabel
					postId={ props.context.postId }
					isFeaturedImage={ false }
				>
					<BlockEdit { ...props } />
				</FeaturedLabel>
			);
		},
	};
	return settings;
}

addFilter(
	'blocks.registerBlockType',
	'sensei-lms/course-categories',
	addWrapperAroundCourseCategoriesBlock
);
