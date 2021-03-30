/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { Notice, PanelBody, SelectControl } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../../editor-components/number-control';
import { useQuestionCategories } from '../question-categories';

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
								nextNumber &&
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
