import {
	__experimentalRadio as Radio,
	__experimentalRadioGroup as RadioGroup,
	BaseControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const RadioGroupControl = ( { previewStatus, setPreviewStatus } ) => {
	const id = 'inspector-radio-group-control';

	return (
		<BaseControl
			id={ id }
			help={ __(
				'Preview a module status. The actual status that the learner sees is determined by their progress in the course.',
				'sensei-lms'
			) }
		>
			<div id={ id } aria-describedby={ id + '__help' }>
				<RadioGroup
					checked={ previewStatus }
					onChange={ ( status ) => setPreviewStatus( status ) }
				>
					<Radio value="in-progress">
						{ __( 'In Progress', 'sensei-lms' ) }
					</Radio>
					<Radio value="completed">
						{ __( 'Completed', 'sensei-lms' ) }
					</Radio>
				</RadioGroup>
			</div>
		</BaseControl>
	);
};
