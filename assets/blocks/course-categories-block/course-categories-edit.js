/**
 * External dependencies
 */
import classnames from 'classnames';
import { unescape, noop } from 'lodash';

/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCourseCategories from './hooks/use-course-categories';
import InvalidUsageError from '../../shared/components/invalid-usage';
import { withColorSettings } from '../../shared/blocks/settings';
import { useDispatch } from '@wordpress/data';

const CSS_VARIABLE_PREFIX = '--sensei-lms-course-categories';

export function CourseCategoryEdit( props ) {
	const {
		attributes,
		backgroundColor,
		context,
		textColor,
		setAttributes,
		setBackgroundColor,
		setTextColor,
	} = props;

	const { textAlign, previewCategories, options } = attributes;
	const { postId, postType } = context;
	const term = 'course-category';
	const {
		postTerms: categories,
		hasPostTerms: hasCategories,
		isLoading,
	} = useCourseCategories( postId );

	const blockProps = useBlockProps( {
		style: {
			[ `${ CSS_VARIABLE_PREFIX }-background-color` ]: options?.backgroundColor,
			[ `${ CSS_VARIABLE_PREFIX }-text-color` ]: options?.textColor,
		},
		className: classnames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
			[ `taxonomy-${ term }` ]: term,
		} ),
	} );

	const { __unstableMarkNextChangeAsNotPersistent = noop } = useDispatch(
		blockEditorStore
	);

	useEffect( () => {
		if ( options ) {
			setBackgroundColor( options.backgroundColor );
			setTextColor( options.textColor );
		}
	}, [] );

	// We need to store the colors inside the option attribute because
	// by default the root backgroundColor and textColor are overwritten by
	// Gutenberg withColors HOC.
	useEffect( () => {
		__unstableMarkNextChangeAsNotPersistent();
		setAttributes( {
			options: {
				backgroundColor: backgroundColor?.color,
				textColor: textColor?.color,
			},
		} );
	}, [
		backgroundColor,
		textColor,
		setAttributes,
		__unstableMarkNextChangeAsNotPersistent,
	] );

	const getCategories = ( categoriesToDisplay ) => {
		return categoriesToDisplay?.map( ( category ) => (
			<a
				key={ category.id }
				href={ category.link }
				onClick={ ( event ) => event.preventDefault() }
			>
				<span>{ unescape( category.name ) }</span>
			</a>
		) );
	};

	if ( previewCategories ) {
		return (
			<div { ...blockProps }>{ getCategories( previewCategories ) }</div>
		);
	}

	if ( 'course' !== postType ) {
		setAttributes( {
			align: false,
		} );
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
		<div { ...blockProps }>
			{ isLoading && <Spinner /> }
			{ ! isLoading && getCategories( categories ) }
			{ ! isLoading && ! hasCategories && (
				<p>{ __( 'No course category', 'sensei-lms' ) }</p>
			) }
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
			label: __( 'Background color', 'sensei-lms' ),
		},
	} )
)( CourseCategoryEdit );
