/**
 * External dependencies
 */
import { unescape, noop } from 'lodash';

/**
 * WordPress dependencies
 */
import {
	store as blockEditorStore,
	BlockControls,
	AlignmentToolbar,
	useBlockProps,
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
import { getStyleAndClassesFromAttributes } from './utils/style';

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
	const { postTerms: categories, isLoading } = useCourseCategories( postId );

	const { __unstableMarkNextChangeAsNotPersistent = noop } = useDispatch(
		blockEditorStore
	);

	useEffect(
		() => {
			if ( options ) {
				setBackgroundColor( options.backgroundColor );
				setTextColor( options.textColor );
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	);

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

	const blockProps = useBlockProps(
		getStyleAndClassesFromAttributes( attributes )
	);

	const getCategories = ( categoriesToDisplay ) => {
		return categoriesToDisplay?.map( ( category ) => (
			<a
				key={ category.id }
				href={ category.link }
				onClick={ ( event ) => event.preventDefault() }
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
			<BlockControls>
				<AlignmentToolbar
					value={ textAlign }
					onChange={ ( nextAlign ) => {
						setAttributes( { textAlign: nextAlign } );
					} }
				/>
			</BlockControls>
			<div { ...blockProps }>
				{ isLoading && <Spinner /> }
				{ ! isLoading && getCategories( categories ) }
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
			label: __( 'Background color', 'sensei-lms' ),
		},
	} )
)( CourseCategoryEdit );
