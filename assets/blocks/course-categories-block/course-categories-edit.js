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
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useMemo } from 'react';
import useCourseCategories from './hooks/use-course-categories';
import InvalidUsageError from '../../shared/components/invalid-usage';

import {
	withColorSettings,
	withDefaultColor,
} from '../../shared/blocks/settings';

export function CourseCategoryEdit( props ) {
	const { context, attributes, textColor, backgroundColor } = props;
	const { textAlign } = attributes;
	const { postId, postType } = context;
	const term = 'course-category';

	const {
		postTerms: categories,
		hasPostTerms: hasCategories,
		isLoading,
	} = useCourseCategories( postId );

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

	if ( 'course' !== postType ) {
		return (
			<InvalidUsageError
				message={ __(
					'The Course Categories block can only be used inside the Course List block.',
					'sensei-lms'
				) }
			/>
		);
	}

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
		textColor: {
			style: 'color',
			label: __( 'Text color', 'sensei-lms' ),
		},
		backgroundColor: {
			style: 'background-color',
			label: __( 'Category background color', 'sensei-lms' ),
		},
	} ),
	withDefaultColor( {
		defaultTextColor: {
			style: 'color',
			probeKey: 'primaryContrastColor',
		},
		defaultBackgroundColor: {
			style: 'background-color',
			probeKey: 'primaryColor',
		},
	} )
)( CourseCategoryEdit );
