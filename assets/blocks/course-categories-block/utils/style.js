/**
 * External dependencies
 */
import classNames from 'classnames';
import { omitBy, isNil } from 'lodash';

const CSS_VARIABLE_PREFIX = '--sensei-lms-course-categories';

export const getStyleAndClassesFromAttributes = ( attributes = {} ) => {
	const { options, textAlign } = attributes;

	return {
		style: omitBy(
			{
				[ `${ CSS_VARIABLE_PREFIX }-background-color` ]: options?.backgroundColor,
				[ `${ CSS_VARIABLE_PREFIX }-text-color` ]: options?.textColor,
			},
			isNil
		),
		className: classNames( {
			[ `has-text-align-${ textAlign }` ]: !! textAlign,
		} ),
	};
};
