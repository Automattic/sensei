/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { Notice, PanelBody, SelectControl } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../../editor-components/number-control';
import { useQuestionCategories } from '../question-categories';
import { QUIZ_STORE } from '../quiz-store';

/**
 * Category question block settings controls.
 *
 * @param {Object}   props                    Block props.
 * @param {Object}   props.attributes         Block attributes.
 * @param {Object}   props.attributes.options Block options attribute.
 * @param {Function} props.setAttributes      Update block attributes.
 */
const CategoryQuestionSettings = ( {
	attributes: { options = {} },
	setAttributes,
} ) => {
	const setOptions = ( next, otherAttributes = {} ) =>
		setAttributes( {
			...otherAttributes,
			options: { ...options, ...next },
		} );

	const [
		questionCategories,
		getQuestionCategoryById,
	] = useQuestionCategories();

	// Used categories.
	const quizClientId = useSelect(
		( select ) => select( QUIZ_STORE ).getBlock(),
		[]
	);
	const usedCategories = useSelect(
		( select ) =>
			quizClientId
				? select( 'core/block-editor' )
						.getBlocks( quizClientId )
						.map(
							( question ) => question.attributes.options.category
						)
						.filter( ( question ) => question )
				: [],
		[ quizClientId ]
	);

	const categoryOptions = [
		{
			value: '',
			label: __( 'Category', 'sensei-lms' ),
		},
		...( questionCategories || [] )
			.map( ( questionCategory ) => ( {
				value: questionCategory.id,
				label: questionCategory.name,
			} ) )
			// Filter only not used categories.
			.filter(
				( option ) =>
					option.value === options.category ||
					! usedCategories.includes( option.value )
			),
	];

	const questionCategory = getQuestionCategoryById( options.category );

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Category Question Settings', 'sensei-lms' ) }
				initialOpen={ true }
			>
				{ ! categoryOptions.length && (
					<Notice status="warning" isDismissible={ false }>
						{ __( 'No question categories exist.', 'sensei-lms' ) }
					</Notice>
				) }
				{ categoryOptions.length > 0 && (
					<>
						<SelectControl
							label={ __( 'Category', 'sensei-lms' ) }
							options={ categoryOptions }
							value={ options.category ?? '' }
							onChange={ ( nextCategoryTermId ) => {
								const nextQuestionCategory = getQuestionCategoryById(
									+nextCategoryTermId
								);

								setOptions(
									{
										category: +nextCategoryTermId ?? null,
									},
									{
										categoryName:
											nextQuestionCategory?.name,
									}
								);
							} }
						/>
						<NumberControl
							label={ __( 'Number of Questions', 'sensei-lms' ) }
							min={ 1 }
							step={ 1 }
							value={ options.number ?? 1 }
							onChange={ ( nextNumber ) =>
								setOptions( {
									number: nextNumber,
								} )
							}
						/>

						{ questionCategory &&
							options.number > questionCategory.count && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ sprintf(
										// translators: Placeholder is number of questions in category.
										_n(
											'The selected category has %d question.',
											'The selected category has %d questions.',
											questionCategory.count,
											'sensei-lms'
										),
										questionCategory.count
									) }
								</Notice>
							) }
					</>
				) }
			</PanelBody>
		</InspectorControls>
	);
};

export default CategoryQuestionSettings;
