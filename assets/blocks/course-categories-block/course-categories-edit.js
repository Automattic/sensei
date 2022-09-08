/**
 * External dependencies
 */
import classnames from 'classnames';
import { unescape } from 'lodash';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCourseCategories from './hooks/use-course-categories';
import InvalidUsageError from '../../shared/components/invalid-usage';

import {
	withColorSettings,
	withDefaultColor,
} from '../../shared/blocks/settings';

export function CourseCategoryEdit( props ) {
	const {
		attributes,
		backgroundColor,
		context,
		defaultBackgroundColor,
		defaultTextColor,
		textColor,
		setAttributes,
	} = props;

	const { textAlign, previewCategories } = attributes;
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
			backgroundColor:
				backgroundColor?.color || defaultBackgroundColor?.color,
			color: textColor?.color || defaultTextColor?.color,
		} ),
		[ backgroundColor, defaultBackgroundColor, defaultTextColor, textColor ]
	);

	const getCategories = ( categoriesToDisplay ) => {
		return categoriesToDisplay.map( ( category ) => (
			<a
				key={ category.id }
				href={ category.link }
				onClick={ ( event ) => event.preventDefault() }
				style={ inlineStyle }
			>
				{ unescape( category.name ) }
			</a>
		) );
	};

	if ( previewCategories ) {
		return (
			<div { ...blockProps }>{ getCategories( previewCategories ) }</div>
		);
	}

	if ( 'course' !== postType ) {
		setAttributes( { align: false } );
		return (
			<InvalidUsageError
				message={ __(
					'The Course Categories block can only be used inside the Course List block.',
					'sensei-lms'
				) }
			/>
		);
	}

	setAttributes( { id: postId } );
	return (
		<div { ...blockProps }>
			{ isLoading && <Spinner /> }
			{ ! isLoading && hasCategories && getCategories( categories ) }
		</div>
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
