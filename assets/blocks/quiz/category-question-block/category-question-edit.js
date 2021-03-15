/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useQuestionIndex } from '../question-index';
import { useQuestionCategories } from '../question-categories';
import CategoryQuestionSettings from './category-question-settings';

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
	} = props;
	const index = useQuestionIndex( clientId );
	const [ , getCategoryTermById ] = useQuestionCategories();

	const nextNumber = index;
	let range = nextNumber;
	if ( number !== 1 ) {
		range += ' - ' + ( nextNumber + number - 1 );
	}

	const questionIndex = (
		<h2 className="sensei-lms-question-block__index">{ range }.</h2>
	);

	const categoryName =
		getCategoryTermById( category )?.name ?? props.attributes.categoryName;

	return (
		<>
			<CategoryQuestionSettings { ...props } />
			<div
				className={ `sensei-lms-question-block ${
					! category ? 'is-draft' : ''
				}` }
			>
				{ questionIndex }
				<h2 className="sensei-lms-question-block__title">
					<strong>
						{ categoryName ??
							__( 'Category Question', 'sensei-lms' ) }
					</strong>
					{ categoryName &&
						number &&
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
		</>
	);
};

export default CategoryQuestionEdit;
