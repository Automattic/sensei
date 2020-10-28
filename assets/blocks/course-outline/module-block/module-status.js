import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { StatusControl, Status, StatusLabels } from '../status-control';
import { COURSE_STATUS_STORE } from '../status-store';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Module status preview with setting control.
 *
 * @param {Object} props          Component props
 * @param {string} props.clientId The module block id.
 */
export const ModuleStatus = ( { clientId } ) => {
	const { setModuleStatus } = useDispatch( COURSE_STATUS_STORE );

	const counts = useSelect(
		( select ) =>
			select( COURSE_STATUS_STORE ).getModuleLessonCounts( clientId ),
		[ clientId ]
	);

	let status = Status.IN_PROGRESS;

	if ( 0 === counts.completedLessonsCount ) {
		status = Status.NOT_STARTED;
	} else if (
		counts.totalLessonsCount === counts.completedLessonsCount &&
		counts.totalLessonsCount > 0
	) {
		status = Status.COMPLETED;
	}

	const options =
		counts.totalLessonsCount > 1
			? [ Status.NOT_STARTED, Status.IN_PROGRESS, Status.COMPLETED ]
			: [ Status.NOT_STARTED, Status.COMPLETED ];

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
						options={ options }
						status={ status }
						disabled={ 0 === counts.totalLessonsCount }
						setStatus={ ( newStatus ) => {
							setModuleStatus( clientId, newStatus );
						} }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
