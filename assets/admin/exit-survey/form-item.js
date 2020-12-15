/**
 * Exit survey reason item with details text field.
 *
 * @param {Object} props
 * @param {string} props.id           Option key.
 * @param {string} props.label        Option text label.
 * @param {string} props.detailsLabel Label for details field.
 */
export const ExitSurveyFormItem = ( { id, label, detailsLabel } ) => {
	const idAttr = `sensei-exit-reason__${ id }`;
	const detailsIdAttr = `${ idAttr }-details`;
	return (
		<div className="sensei-exit-survey__item">
			<input
				id={ idAttr }
				type="radio"
				name="reason"
				value={ id }
				className="sensei-exit-survey__radio"
			/>
			<label htmlFor={ idAttr }> { label }</label>
			{ detailsLabel && (
				<div className="sensei-exit-survey__details">
					<input
						id={ detailsIdAttr }
						name={ `details-${ id }` }
						defaultValue=""
						type="text"
						placeholder={ detailsLabel }
					/>
				</div>
			) }
		</div>
	);
};
