/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import InvalidUsageError from '../../shared/components/invalid-usage';

function useFilterOptions( defaultOptions ) {
	const categories = useSelect( ( select ) => {
		const terms = select( 'core' ).getEntityRecords(
			'taxonomy',
			'course-category',
			{
				per_page: -1,
			}
		);

		return terms ?? [];
	}, [] ).map( ( category ) => {
		return {
			label: category.name,
			value: category.id,
		};
	} );

	return {
		categories: {
			label: __( 'Categories', 'sensei-lms' ),
			options: [
				{
					label: __( 'All Categories', 'sensei-lms' ),
					value: -1,
				},
				...categories,
			],
			defaultOption: defaultOptions?.categories ?? -1,
		},
		featured: {
			label: __( 'Featured', 'sensei-lms' ),
			options: [
				{
					label: __( 'All Courses', 'sensei-lms' ),
					value: 'all',
				},
				{
					label: __( 'Featured', 'sensei-lms' ),
					value: 'featured',
				},
			],
			defaultOption: defaultOptions?.featured ?? 'all',
		},
		student_course: {
			label: __( 'Student Courses', 'sensei-lms' ),
			options: [
				{
					label: __( 'All Courses', 'sensei-lms' ),
					value: 'all',
				},
				{
					label: __( 'Active', 'sensei-lms' ),
					value: 'active',
				},
				{
					label: __( 'Completed', 'sensei-lms' ),
					value: 'completed',
				},
			],
			defaultOption: defaultOptions?.student_course ?? 'all',
		},
	};
}

function updateSelectedTypesList( checked, types, setAttributes, key ) {
	const newTypes = checked
		? [ ...types, key ]
		: types.filter( ( type ) => type !== key );
	setAttributes( { types: newTypes } );
}

function SelectedFilters( { filters, types } ) {
	if ( ! types || types.length < 1 ) {
		return null;
	}
	return Object.keys( filters ).map( ( key ) => {
		const filter = filters[ key ];
		return types.includes( key ) ? (
			<SelectControl
				key={ filter.label }
				options={ filter.options }
				onChange={ () => {} }
				value={ filter.defaultOption }
			/>
		) : null;
	} );
}

function CourseListFilter( {
	attributes: { types, defaultOptions },
	context: { query },
	setAttributes,
} ) {
	const filters = useFilterOptions( defaultOptions );
	const blockProps = useBlockProps();

	if ( 'course' !== query?.postType ) {
		setAttributes( {
			align: false,
			className: 'wp-block-sensei-lms-course-list-filter__warning',
		} );
		return (
			<InvalidUsageError
				message={ __(
					'The Course List Filter block can only be used inside the Course List block.',
					'sensei-lms'
				) }
			/>
		);
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Filter Type', 'sensei-lms' ) }>
					{ Object.keys( filters ).map( ( key ) => (
						<ToggleControl
							key={ key }
							label={ filters[ key ].label }
							checked={ types.includes( key ) }
							onChange={ ( checked ) =>
								updateSelectedTypesList(
									checked,
									types,
									setAttributes,
									key
								)
							}
						/>
					) ) }
				</PanelBody>
			</InspectorControls>
			<SelectedFilters filters={ filters } types={ types } />
		</div>
	);
}

export default CourseListFilter;
