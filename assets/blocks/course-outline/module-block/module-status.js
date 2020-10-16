import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { StatusControl, Status, StatusLabels } from '../status-control';
import { COURSE_STATUS_STORE } from '../status-store';
import { dispatch, useSelect } from '@wordpress/data';

/**
 * Module status preview with setting control.
 *
 * @param {Object} props          Component props
 * @param {string} props.clientId The module block id.
 */
export const ModuleStatus = ( { clientId } ) => {
	const status = useSelect(
		( select ) => select( COURSE_STATUS_STORE ).getModuleStatus( clientId ),
		[]
	);

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
						setStatus={ ( newStatus ) => {
							dispatch( COURSE_STATUS_STORE ).setModuleStatus(
								clientId,
								newStatus
							);
						} }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
