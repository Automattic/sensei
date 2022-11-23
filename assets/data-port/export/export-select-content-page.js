/**
 * WordPress dependencies
 */
import { Button, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useMergeReducer } from '../../react-hooks/use-merge-reducer';
import { getSelectedKeys } from '../../shared/helpers/data';
import { postTypeLabels } from '../../shared/helpers/labels';

export const ExportSelectContentPage = ( { onSubmit, job } ) => {
	const [ values, updateValues ] = useMergeReducer( {
		course: false,
		lesson: false,
		question: false,
	} );
	const submit = ( event ) => {
		event.preventDefault();
		onSubmit( getSelectedKeys( values ) );
	};

	const hasSelected = Object.values( values ).some( ( v ) => v );
	const isLoading = job && 'creating' === job.status;

	return (
		<form onSubmit={ submit }>
			<div className="sensei-data-port-step__body">
				<p className="sensei-export__select-content__label">
					{ __(
						'Which type of content would you like to export?',
						'sensei-lms'
					) }
				</p>

				<div className="sensei-export__select-content__options">
					{ [ 'course', 'lesson', 'question' ].map( ( type ) => (
						<CheckboxControl
							className="sensei-export__select-content__option sensei-data-port-step__line"
							key={ type }
							name={ type }
							checked={ values[ type ] }
							onChange={ ( v ) =>
								updateValues( { [ type ]: v } )
							}
							label={ postTypeLabels[ type ] }
						/>
					) ) }
				</div>
				<div className="sensei-data-port-step__footer">
					<Button
						type="submit"
						isPrimary
						disabled={ ! hasSelected || isLoading }
						isBusy={ isLoading }
					>
						{ __( 'Continue', 'sensei-lms' ) }
					</Button>
				</div>
			</div>
		</form>
	);
};
