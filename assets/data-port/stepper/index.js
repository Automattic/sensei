/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * @typedef  {Object}   Step
 * @property {string}  key         Unique key for the step.
 * @property {string}  description A description of the step that is going to be displayed.
 * @property {boolean} isActive    True if the step is the currently active one.
 * @property {boolean} isComplete  True if the step is completed.
 */
/**
 * A simple component to display a stepper on data port pages.
 *
 * @param {Step[]} steps The array of the steps.
 */
const DataPortStepper = ( { steps } ) => (
	<ol className="sensei-data-port-steps">
		{ steps.map( ( step ) => {
			const stepClass = classnames( {
				active: step.isNext,
				done: step.isComplete,
			} );

			return (
				<li key={ step.key } className={ stepClass }>
					{ step.label }
				</li>
			);
		} ) }
	</ol>
);

export { DataPortStepper };
