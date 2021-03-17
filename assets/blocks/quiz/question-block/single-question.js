/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	PluginPostStatusInfo,
	PluginPrePublishPanel,
} from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import { Effect, usePostSavingEffect } from '../../../shared/helpers/blocks';

/**
 * Additional controls for a question block in the single question editor.
 *
 * @param {Object} props Question block props.
 */
const SingleQuestion = ( props ) => {
	const {
		meta: { validationErrors, showValidationErrors },
		setMeta,
	} = props;

	const toggleValidationErrors = useCallback(
		( on = true ) => {
			setMeta( { showValidationErrors: on } );
		},
		[ setMeta ]
	);

	usePostSavingEffect( () => toggleValidationErrors( false ), [
		toggleValidationErrors,
	] );

	if ( ! validationErrors?.length ) return null;

	const notice = (
		<Notice
			isDismissible={ false }
			status="warning"
			className="sensei-lms-quiz-block__pre-publish-validation__notice"
			actions={
				showValidationErrors
					? []
					: [
							{
								label: __( 'View issues', 'sensei-lms' ),
								onClick: () => toggleValidationErrors( true ),
								className: 'is-link',
							},
					  ]
			}
		>
			{ __( 'This question is incomplete.', 'sensei-lms' ) }
		</Notice>
	);

	return (
		<>
			<PluginPostStatusInfo>{ notice }</PluginPostStatusInfo>
			<PluginPrePublishPanel
				title={ __( 'Validation', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<Effect onMount={ toggleValidationErrors } />
				{ notice }
				<p>
					{ __(
						"Incomplete questions added to a quiz won't be displayed to the learner.",
						'sensei-lms'
					) }
				</p>
			</PluginPrePublishPanel>
		</>
	);
};

export default SingleQuestion;
