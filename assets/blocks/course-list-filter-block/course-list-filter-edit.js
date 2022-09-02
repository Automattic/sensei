/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

const filters = {
	categories: {
		label: __( 'Categories', 'sensei-lms' ),
		defaultOption: {
			label: __( 'Select Category', 'sensei-lms' ),
			value: 0,
		},
	},
	featured: {
		label: __( 'Featured', 'sensei-lms' ),
		defaultOption: {
			label: __( 'All Courses', 'sensei-lms' ),
			value: 'all',
		},
	},
	activity: {
		label: __( 'Activity', 'sensei-lms' ),
		defaultOption: {
			label: __( 'All Courses', 'sensei-lms' ),
			value: 0,
		},
	},
};
/**
 * Internal dependencies
 */
import { useSelect } from '@wordpress/data';
/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import InvalidUsageError from '../../shared/components/invalid-usage';

function useFilterOptions( type ) {
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

	switch ( type ) {
		case 'categories':
			return [ filters.categories.defaultOption, ...categories ];
		case 'featured':
			return [
				filters.featured.defaultOption,
				{
					label: __( 'Featured', 'sensei-lms' ),
					value: 'yes',
				},
				{
					label: __( 'Not Featured', 'sensei-lms' ),
					value: 'no',
				},
			];
		case 'activity':
			return [ filters.activity.defaultOption ];
	}
}

function CourseListFilter( {
	attributes: { type },
	context: { query },
	setAttributes,
} ) {
	const options = useFilterOptions( type );
	const blockProps = useBlockProps();

	if ( 'course' !== query?.postType ) {
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
							checked={ key === type }
							onChange={ () => setAttributes( { type: key } ) }
						/>
					) ) }
				</PanelBody>
			</InspectorControls>
			<SelectControl
				options={ options }
				onChange={ () => {} }
				value={ filters[ type ].defaultOption.value }
			/>
		</div>
	);
}

export default CourseListFilter;
