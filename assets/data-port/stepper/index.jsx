import classnames from 'classnames';

/**
 * A simple component to display a stepper on data port pages.
 *
 * @param {array} steps The array of the steps.
 */
const DataPortStepper = ( { steps } ) => {
	return(
		<ol className='sensei-progress-steps'>
			{
				steps.map( ( step ) => {
					let stepClass = classnames( {
						active: step.isActive,
						done: step.isComplete,
					});

					return (
						<li className={ stepClass}>
							{ step.description }
						</li>
					);
				} )
			}
		</ol>
	);
}

export default DataPortStepper;
