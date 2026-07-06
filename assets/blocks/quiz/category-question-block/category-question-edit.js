/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useQuestionNumber } from '../question-number';
import { useQuestionCategories } from '../question-categories';
import CategoryQuestionSettings from './category-question-settings';
import { useEffect } from '@wordpress/element';
import { withBlockMeta } from '../../../shared/blocks/block-metadata';
import { withBlockValidation } from '../../../shared/blocks/block-validation';
import {
	validateCategoryQuestionBlock,
	getCategoryQuestionBlockValidationErrorMessages,
} from './category-question-validation';
import { QuestionValidationNotice } from '../question-block/question-block-helpers';

/**
 * Quiz category question block editor.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes              Block attributes.
 * @param {Object}   props.attributes.categoryName Category name.
 * @param {Function} props.setAttributes           Set block attributes.
 */
const CategoryQuestionEdit = ( props ) => {
	const {
		attributes: {
			options: { number = 1, category },
		},
		clientId,
		setAttributes,
	} = props;
	const questionNumber = useQuestionNumber( clientId );
	const [ , getCategoryTermById ] = useQuestionCategories();

	// Block API version 3 requires `useBlockProps()` on the outermost
	// element for the editor to recognize/select this block, and no
	// longer auto-merges `className`/`attributes.className` into
	// `props.className`.
	const blockProps = useBlockProps( {
		className: `sensei-lms-question-block sensei-lms-category-question-block ${
			! category ? 'is-draft' : ''
		}`,
	} );

	const range =
		! number || 1 === number
			? questionNumber
			: `${ questionNumber } - ${ questionNumber + number - 1 }`;

	const questionIndex = (
		<h2 className="sensei-lms-question-block__index">{ range }.</h2>
	);

	const categoryName =
		getCategoryTermById( category )?.name ?? props.attributes.categoryName;
	const categoryNameMatch = categoryName === props.attributes.categoryName;

	useEffect( () => {
		if ( categoryName && ! categoryNameMatch ) {
			setAttributes( {
				categoryName,
			} );
		}
	}, [ categoryName, categoryNameMatch, setAttributes ] );

	return (
		<>
			<CategoryQuestionSettings { ...props } />
			<div { ...blockProps }>
				{ questionIndex }
				<h2 className="sensei-lms-question-block__title">
					{ categoryName ? (
						<strong>{ categoryName }</strong>
					) : (
						__( 'Category Question', 'sensei-lms' )
					) }
					{ categoryName &&
						number > 0 &&
						' (' +
							sprintf(
								// translators: placeholder is number of questions to show from category.
								_n(
									'%d question',
									'%d questions',
									number,
									'sensei-lms'
								),
								number
							) +
							')' }
				</h2>
			</div>
			<QuestionValidationNotice
				{ ...props }
				getErrorMessages={
					getCategoryQuestionBlockValidationErrorMessages
				}
			/>
		</>
	);
};

export default compose(
	withBlockMeta,
	withBlockValidation( validateCategoryQuestionBlock )
)( CategoryQuestionEdit );
