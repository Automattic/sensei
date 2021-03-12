/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useBlockIndex } from '../../../shared/blocks/block-index';

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
		attributes: { categoryName, options },
		clientId,
	} = props;
	const number = options.number ?? 1;
	const index = useBlockIndex( clientId );

	const nextNumber = index;
	let range = nextNumber;
	if ( number !== 1 ) {
		range += '-' + ( nextNumber + number - 1 );
	}

	const questionIndex = (
		<h2 className="sensei-lms-question-block__index">{ range }.</h2>
	);

	const categoryDescription = sprintf(
		// translators: Temporary placeholder is either the category name or ID.
		__( 'Term %s', 'sensei-lms' ),
		categoryName ? categoryName : options.category
	);

	return (
		<div
			className={ `sensei-lms-question-block ${
				! options.category ? 'is-draft' : ''
			}` }
		>
			{ questionIndex }
			<h2 className="sensei-lms-question-block__title">
				{ categoryDescription }
			</h2>
		</div>
	);
};

export default CategoryQuestionEdit;
