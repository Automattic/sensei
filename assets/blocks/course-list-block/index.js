/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';
import { list } from '@wordpress/icons';
import { addFilter } from '@wordpress/hooks';

export const registerCourseListBlock = () => {
	const DEFAULT_ATTRIBUTES = {
		className: 'course-list-block',
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

// Hide the settings which are inherited from the Query Loop block
// but not applicable to our Course List block.
const withUnnecessarySettingsHidden = ( BlockEdit ) => {
	return ( props ) => {
		if (
			'core/query' === props.name &&
			props.isSelected &&
			props.attributes &&
			props.attributes.className &&
			'course-list-block' === props.attributes.className
		) {
			setTimeout( () => {
				const postTypeContainerQuery =
					'.components-input-control__label:contains(' +
					/* eslint-disable-next-line @wordpress/i18n-text-domain */
					__( 'Post type' ) +
					')';
				const inheritContextContainerQuery =
					'.components-toggle-control__label:contains(' +
					/* eslint-disable-next-line @wordpress/i18n-text-domain */
					__( 'Inherit query from template' ) +
					')';

				// eslint-disable-next-line no-undef
				const toBeHiddenSettingContainers = jQuery(
					`${ postTypeContainerQuery },${ inheritContextContainerQuery }`
				).parents( '.components-base-control' );

				toBeHiddenSettingContainers.hide();
			}, 0 );
		}
		return <BlockEdit { ...props } />;
	};
};

addFilter(
	'editor.BlockEdit',
	'sensei-lms/course-list-block',
	withUnnecessarySettingsHidden
);
