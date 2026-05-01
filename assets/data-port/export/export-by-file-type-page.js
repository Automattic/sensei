/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { Button, CheckboxControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PostTokenField } from './post-token-field';
import { ExportPreviewPanel } from './export-preview-panel';

const TYPE_CONFIG = [
	{
		type: 'course',
		file: 'courses.csv',
		label: __( 'courses.csv', 'sensei-lms' ),
		fieldLabel: __( 'Filter courses', 'sensei-lms' ),
		placeholder: __( 'Search to limit to specific courses…', 'sensei-lms' ),
	},
	{
		type: 'lesson',
		file: 'lessons.csv',
		label: __( 'lessons.csv', 'sensei-lms' ),
		fieldLabel: __( 'Filter lessons', 'sensei-lms' ),
		placeholder: __( 'Search to limit to specific lessons…', 'sensei-lms' ),
	},
	{
		type: 'question',
		file: 'questions.csv',
		label: __( 'questions.csv', 'sensei-lms' ),
		fieldLabel: __( 'Filter questions', 'sensei-lms' ),
		placeholder: __(
			'Search to limit to specific questions…',
			'sensei-lms'
		),
	},
];

/**
 * Step 2 — "By file type" mode. User picks which CSVs to produce
 * (per-row checkbox) and optionally narrows each CSV to specific
 * items via a filter token field. No cross-type cascade — each CSV is
 * exactly what the user said.
 *
 * @param {Object}   props
 * @param {Object}   props.job      Current job state.
 * @param {Function} props.onSubmit Called with ({ types, selections, mode }).
 * @param {Function} props.onBack   Called when the user clicks "Back".
 */
export const ExportByFileTypePage = ( { job, onSubmit, onBack } ) => {
	const [ enabled, setEnabled ] = useState( {
		course: true,
		lesson: true,
		question: true,
	} );
	const [ selections, setSelections ] = useState( {
		course: [],
		lesson: [],
		question: [],
	} );

	const isLoading = job && 'creating' === job.status;

	const enabledTypes = TYPE_CONFIG.filter(
		( { type } ) => enabled[ type ]
	).map( ( { type } ) => type );

	const submit = () => {
		const trimmedSelections = {};
		enabledTypes.forEach( ( type ) => {
			if ( selections[ type ].length > 0 ) {
				trimmedSelections[ type ] = selections[ type ];
			}
		} );

		onSubmit( {
			types: enabledTypes,
			selections: trimmedSelections,
			mode: 'by_file_type',
		} );
	};

	const previewLines = TYPE_CONFIG.filter(
		( { type } ) => enabled[ type ]
	).map( ( { file, type } ) => ( {
		file,
		detail:
			selections[ type ].length > 0
				? sprintf(
						/* translators: %d is the number of selected items. */
						__( '%d selected', 'sensei-lms' ),
						selections[ type ].length
				  )
				: __( 'all', 'sensei-lms' ),
	} ) );

	return (
		<div className="sensei-data-port-step__body">
			<p className="sensei-export__by-file-type__label">
				{ __(
					'Choose which CSV files to generate. Each file contains exactly the items you select for it. Leave a filter empty to include every item of that type.',
					'sensei-lms'
				) }
			</p>
			{ TYPE_CONFIG.map( ( { type, label, fieldLabel, placeholder } ) => (
				<div key={ type } className="sensei-export__by-file-type__row">
					<CheckboxControl
						label={ label }
						checked={ enabled[ type ] }
						onChange={ ( isChecked ) =>
							setEnabled( ( current ) => ( {
								...current,
								[ type ]: isChecked,
							} ) )
						}
					/>
					{ enabled[ type ] && (
						<PostTokenField
							type={ type }
							label={ fieldLabel }
							placeholder={ placeholder }
							selectedIds={ selections[ type ] }
							onChange={ ( ids ) =>
								setSelections( ( current ) => ( {
									...current,
									[ type ]: ids,
								} ) )
							}
						/>
					) }
				</div>
			) ) }
			<ExportPreviewPanel lines={ previewLines } />
			<div className="sensei-data-port-step__footer">
				<Button isSecondary onClick={ onBack } disabled={ isLoading }>
					{ __( 'Back', 'sensei-lms' ) }
				</Button>
				<Button
					isPrimary
					onClick={ submit }
					disabled={ isLoading || enabledTypes.length === 0 }
					isBusy={ isLoading }
				>
					{ __( 'Start Export', 'sensei-lms' ) }
				</Button>
			</div>
		</div>
	);
};
