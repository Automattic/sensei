/**
 * Stepper component from `@woocommerce/components`.
 */

/**
 * WordPress dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import CheckIcon from './check-icon';

/**
 * A stepper component to indicate progress in a set number of steps.
 */
class Stepper extends Component {
	renderCurrentStepContent() {
		const { currentStep, steps } = this.props;
		const step = steps.find( ( s ) => currentStep === s.key );

		if ( ! step.content ) {
			return null;
		}

		return <div className="sensei-stepper_content">{ step.content }</div>;
	}

	render() {
		const { className, currentStep, steps, isPending } = this.props;
		const currentIndex = steps.findIndex( ( s ) => currentStep === s.key );
		const stepperClassName = classnames( 'sensei-stepper', className );

		return (
			<div className={ stepperClassName }>
				<div className="sensei-stepper__steps">
					{ steps.map( ( step, i ) => {
						const {
							key,
							label,
							description,
							isComplete,
							onClick,
						} = step;
						const isCurrentStep = key === currentStep;
						const stepClassName = classnames(
							'sensei-stepper__step',
							{
								'is-active': isCurrentStep,
								'is-complete':
									typeof isComplete !== 'undefined'
										? isComplete
										: currentIndex > i,
							}
						);

						const icon =
							isCurrentStep && isPending ? (
								<Spinner />
							) : (
								<div className="sensei-stepper__step-icon">
									<span className="sensei-stepper__step-number">
										{ i + 1 }
									</span>
									<CheckIcon />
								</div>
							);

						const LabelWrapper =
							typeof onClick === 'function' ? 'button' : 'div';

						return (
							<Fragment key={ key }>
								<div className={ stepClassName }>
									<LabelWrapper
										className="sensei-stepper__step-label-wrapper"
										onClick={
											typeof onClick === 'function'
												? () => onClick( key )
												: null
										}
									>
										{ icon }
										<div className="sensei-stepper__step-text">
											<span className="sensei-stepper__step-label">
												{ label }
											</span>
											{ description && (
												<span className="sensei-stepper__step-description">
													{ description }
												</span>
											) }
										</div>
									</LabelWrapper>
								</div>
								<div className="sensei-stepper__step-divider" />
							</Fragment>
						);
					} ) }
				</div>

				{ this.renderCurrentStepContent() }
			</div>
		);
	}
}

export default Stepper;
