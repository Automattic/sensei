/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { Notice, PanelBody, SelectControl } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import NumberControl from '../../editor-components/number-control';
import { useQuestionCategories } from '../question-categories';

/**
 * Calculate the number of questions that are available in a category.
 *
 * @param {number}   categoryId The category id.
 * @param {Function} onError    Called when an error happens.
 *
 * @return {number|boolean} The number of questions in a category or false if unknown.
 */
const useCategoryQuestionsCount = ( categoryId, onError ) => {
	const [ categoriesQuestionCount, setCategoriesQuestionCount ] = useState(
		{}
	);

	useEffect( () => {
		if (
			categoryId &&
			! categoriesQuestionCount.hasOwnProperty( categoryId )
		) {
			apiFetch( {
				path: `/wp/v2/questions?question-category=${ categoryId }`,
				method: 'GET',
				parse: false,
			} )
				.then( ( res ) => {
					categoriesQuestionCount[ categoryId ] = +res.headers.get(
						'X-WP-Total'
					);
					setCategoriesQuestionCount( {
						...categoriesQuestionCount,
					} );
				} )
				.catch( ( res ) => {
					res.json().then( ( error ) => onError( error.message ) );
				} );
		}
	}, [ categoryId, categoriesQuestionCount, onError ] );

	return categoriesQuestionCount.hasOwnProperty( categoryId )
		? categoriesQuestionCount[ categoryId ]
		: false;
};

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

	const [ questionsFetchError, setQuestionsFetchError ] = useState( null );
	const categoryQuestionsCount = useCategoryQuestionsCount(
		options.category,
		setQuestionsFetchError
	);

	const categoryOptions = [
		{
			value: '',
			label: '',
		},
		...( questionCategories || [] ).map( ( questionCategory ) => ( {
			value: questionCategory.id,
			label: questionCategory.name,
		} ) ),
	];

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

								setQuestionsFetchError( null );
							} }
						/>
						<NumberControl
							label={ __( 'Number of Questions', 'sensei-lms' ) }
							min={ 1 }
							step={ 1 }
							value={ options.number ?? 1 }
							onChange={ ( nextNumber ) =>
								nextNumber &&
								setOptions( {
									number: nextNumber || 1,
								} )
							}
						/>
						{ questionsFetchError !== null && (
							<Notice status="error" isDismissible={ false }>
								{ sprintf(
									// translators: The underlying error message.
									__(
										'An error occurred while retrieving questions: %s',
										'sensei-lms'
									),
									questionsFetchError
								) }
							</Notice>
						) }
						{ categoryQuestionsCount !== false &&
							questionsFetchError === null &&
							options.number > categoryQuestionsCount && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ sprintf(
										// translators: Placeholder is number of questions in category.
										_n(
											'The selected category has %d question.',
											'The selected category has %d questions.',
											categoryQuestionsCount,
											'sensei-lms'
										),
										categoryQuestionsCount
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
