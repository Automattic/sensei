/**
 * External dependencies
 */
import classnames from 'classnames';
import { unescape } from 'lodash';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useMemo } from 'react';
import useColors from './hooks/use-colors';
import useCourseCategories from './hooks/use-course-categories';
import {
	withColorSettings,
	withDefaultColor,
} from '../../shared/blocks/settings';
import { compose } from '@wordpress/compose';

export function CourseCategoryEdit( props ) {
	const { context, attributes } = props;
	const { textAlign } = attributes;
	const { postId } = context;
	const term = 'course-category';

	const {
		postTerms: categories,
		hasPostTerms: hasCategories,
		isLoading,
	} = useCourseCategories( postId );

	const { textColor, backgroundColor } = useColors( props );

	const blockProps = useBlockProps( {
		className: classnames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
			[ `taxonomy-${ term }` ]: term,
		} ),
	} );

	const inlineStyle = useMemo(
		() => ( {
			color: textColor?.color,
			backgroundColor: backgroundColor?.color,
		} ),
		[ textColor, backgroundColor ]
	);

	return (
		<>
			<div { ...blockProps }>
				{ isLoading && <Spinner /> }
				{ ! isLoading &&
					hasCategories &&
					categories.map( ( category ) => (
						<a
							key={ category.id }
							href={ category.link }
							onClick={ ( event ) => event.preventDefault() }
							style={ inlineStyle }
						>
							{ unescape( category.name ) }
						</a>
					) ) }
			</div>
		</>
	);
}

export default compose(
	withColorSettings( {
		categoryBackgroundColor: {
			style: 'background-color',
			label: __( 'Category background color', 'sensei-lms' ),
		},
		categoryTextColor: {
			style: 'color',
			label: __( 'Text color', 'sensei-lms' ),
		},
	} ),
	withDefaultColor( {
		defaultCategoryBackgroundColor: {
			style: 'background-color',
			probeKey: 'primaryColor',
		},
		defaultCategoryTextColor: {
			style: 'color',
			probeKey: 'primaryContrastColor',
		},
	} )
)( CourseCategoryEdit );
