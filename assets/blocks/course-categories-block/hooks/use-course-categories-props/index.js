/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
/**
 * External dependencies
 */
import classNames from 'classnames';
import { isNil, omitBy } from 'lodash';

const CSS_VARIABLE_PREFIX = '--sensei-lms-course-categories';

const useCourseCategoriesProps = ( attributes = {} ) => {
	const { options, textAlign } = attributes;

	return useBlockProps( {
		style: omitBy(
			{
				[ `${ CSS_VARIABLE_PREFIX }-background-color` ]: options?.backgroundColor,
				[ `${ CSS_VARIABLE_PREFIX }-text-color` ]: options?.textColor,
			},
			isNil
		),
		className: classNames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
			[ `taxonomy-course-category` ]: true,
		} ),
	} );
};

export default useCourseCategoriesProps;
