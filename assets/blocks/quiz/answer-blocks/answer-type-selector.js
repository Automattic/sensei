import answerBlocks from './index';

export const AnswerTypeSelector = ( {
	onSelect,
	value,
	className,
	...props
} ) => {
	return (
		<div
			className={ `sensei-lms-question-block__answer-type-selector ${ className }` }
			{ ...props }
		>
			{ Object.entries( answerBlocks ).map( ( [ type, settings ] ) => {
				const AnswerBlock = settings.edit;
				return (
					<div
						key={ type }
						className={ `sensei-lms-question-block__answer-type-selector__option ${
							value === type ? 'is-selected' : ''
						}` }
						onClick={ () => onSelect( type ) }
					>
						{ /*<div className="sensei-lms-question-block__answer-type-selector__preview">*/ }
						{ /*	<AnswerBlock attributes={ {} } />*/ }
						{ /*</div>*/ }
						<div>
							<strong> { settings.title }</strong>
							<div className="sensei-lms-question-block__answer-type-selector__option__description">
								{ settings.description }
							</div>
						</div>
					</div>
				);
			} ) }
		</div>
	);
};
