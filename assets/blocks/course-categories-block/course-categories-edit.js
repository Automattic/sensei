/**
 * External dependencies
 */
import classnames from 'classnames';
import { unescape } from 'lodash';

/**
 * WordPress dependencies
 */
import { useBlockProps, withColors } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useMemo } from 'react';
import useColors from './hooks/use-colors';
import useCourseCategories from './hooks/use-course-categories';
import { Settings } from './course-categories-settings';

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

	const {
		textColor,
		backgroundColor,
		setTextColor,
		setBackgroundColor,
	} = useColors( props );

	const colorSettings = useMemo(
		() => [
			{
				label: __( 'Text color', 'sensei-lms' ),
				style: 'color',
				value: textColor?.color,
				onChange: setTextColor,
			},
			{
				label: __( 'Background color', 'sensei-lms' ),
				style: 'background-color',
				value: backgroundColor?.color,
				onChange: setBackgroundColor,
			},
		],
		[ textColor, setTextColor, backgroundColor, setBackgroundColor ]
	);

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
			<Settings
				textColor={ textColor }
				backgroundColor={ backgroundColor }
				colorSettings={ colorSettings }
			></Settings>

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

export default withColors(
	'categoryTextColor',
	'categoryBackgroundColor'
)( CourseCategoryEdit );
