/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
	const setOptions = ( next ) =>
		setAttributes( { options: { ...options, ...next } } );

	const [
		questionCategories,
		getQuestionCategoryById,
	] = useQuestionCategories();

	const categoryOptions = [
		{
			value: '',
			label: __( 'Category', 'sensei-lms' ),
		},
		...( questionCategories || [] ).map( ( questionCategory ) => ( {
			value: questionCategory.id,
			label: questionCategory.name,
		} ) ),
	];

	const questionCategory = getQuestionCategoryById( options.category );
	let maxNumberQuestions;
	if ( questionCategory ) {
		maxNumberQuestions = questionCategory.count;
	}

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Category Question Settings', 'sensei-lms' ) }
				initialOpen={ true }
			>
				{ ! categoryOptions.length && (
					<div>
						{ __( 'No question categories exist.', 'sensei-lms' ) }
					</div>
				) }
				{ categoryOptions.length > 0 && (
					<>
						<SelectControl
							label={ __( 'Category', 'sensei-lms' ) }
							options={ categoryOptions }
							value={ options.category }
							onChange={ ( nextCategory ) => {
								let numberQuestions = options.number;
								nextCategory = parseInt( nextCategory, 10 );
								const nextQuestionCategory = getQuestionCategoryById(
									nextCategory
								);

								if ( nextQuestionCategory ) {
									numberQuestions = Math.min(
										nextQuestionCategory.count,
										numberQuestions
									);
								}

								setOptions( {
									number: numberQuestions,
									category:
										parseInt( nextCategory, 10 ) ?? null,
								} );
							} }
						/>
						<NumberControl
							label={ __( 'Number of Questions', 'sensei-lms' ) }
							min={ 1 }
							max={ maxNumberQuestions }
							step={ 1 }
							value={ Math.min(
								maxNumberQuestions,
								options.number
							) }
							onChange={ ( nextNumber ) =>
								setOptions( {
									number: Math.min(
										maxNumberQuestions,
										nextNumber
									),
								} )
							}
						/>
					</>
				) }
			</PanelBody>
		</InspectorControls>
	);
};

export default CategoryQuestionSettings;
