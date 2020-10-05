import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { StatusControl, Status, StatusLabels } from '../status-control';

/**
 * Module status preview with setting control.
 *
 */
export const ModuleStatus = () => {
	const [ status, setStatus ] = useState( Status.NOT_STARTED );

	const showIndicator = Status.NOT_STARTED !== status;

	const indicator = (
		<div
			className={ classnames(
				'wp-block-sensei-lms-course-outline-module__progress-indicator',
				status
			) }
		>
			<span className="wp-block-sensei-lms-course-outline-module__progress-indicator__text">
				{ StatusLabels[ status ] }
			</span>
		</div>
	);

	return (
		<>
			{ showIndicator && indicator }
			<InspectorControls>
				<PanelBody
					title={ __( 'Status', 'sensei-lms' ) }
					initialOpen={ false }
				>
					<StatusControl
						options={ [
							Status.NOT_STARTED,
							Status.IN_PROGRESS,
							Status.COMPLETED,
						] }
						status={ status }
						setStatus={ setStatus }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
