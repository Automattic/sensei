/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import { SelectControl } from '@wordpress/components';

export function CourseListFeaturedFilter() {
	return (
		<div>
			<SelectControl
				style={ { width: 'auto' } }
				options={ [
					{
						label: __( 'Select Category', 'sensei-lms' ),
						value: 0,
					},
				] }
				onChange={ () => {} }
				value={ 0 }
			/>
		</div>
	);
}

export default CourseListFeaturedFilter;
