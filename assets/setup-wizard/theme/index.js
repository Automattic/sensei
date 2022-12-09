/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useLayoutEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import { useQueryStringRouter } from '../../shared/query-string-router';
import { H } from '../../shared/components/section';
import BigScreen from './big-screen';
import SmallScreen from './small-screen';

/**
 * Theme step for Setup Wizard.
 */
const Theme = () => {
	const { goTo } = useQueryStringRouter();
	const [ isBigScreen, setIsBigScreen ] = useState( false );
	const [ isScrolled, setIsScrolled ] = useState( false );
	const themeContentRef = useRef();
	const scrollOffset = 70;

	const { submitStep, isSubmitting, errorNotice } = useSetupWizardStep(
		'theme'
	);

	useLayoutEffect( () => {
		const w = themeContentRef.current?.ownerDocument.defaultView;

		// Check if it's a big screen.
		const screenSizeCheck = () => {
			setIsBigScreen( window.innerWidth >= 600 );
		};

		// Checks if it was scrolled until the theme content.
		const scrollCheck = () => {
			setIsScrolled(
				themeContentRef.current.getBoundingClientRect().y <=
					scrollOffset + 10
			);
		};

		// Checks on load.
		screenSizeCheck();
		scrollCheck();

		w.addEventListener( 'resize', screenSizeCheck );
		w.addEventListener( 'scroll', scrollCheck );

		return () => {
			w.removeEventListener( 'resize', screenSizeCheck );
			w.removeEventListener( 'scroll', scrollCheck );
		};
	}, [] );

	const scrollToThemeContent = () => {
		const w = themeContentRef.current?.ownerDocument.defaultView;

		w.scroll( {
			top:
				themeContentRef.current.getBoundingClientRect().y -
				scrollOffset,
			behavior: 'smooth',
		} );
	};

	const goToNextStep = () => {
		goTo( 'tracking' );
	};

	const submitPage = ( installSenseiTheme ) => () => {
		submitStep(
			{ theme: { install_sensei_theme: installSenseiTheme } },
			{ onSuccess: goToNextStep }
		);
	};

	return (
		<>
			{ isBigScreen && isScrolled && (
				<div className="sensei-setup-wizard-theme-top-actions sensei-setup-wizard-theme-top-actions--enter-animation">
					<button
						disabled={ isSubmitting }
						className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
						onClick={ submitPage( false ) }
					>
						{ __( 'Skip', 'sensei-lms' ) }
					</button>

					<button
						disabled={ isSubmitting }
						className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
						onClick={ submitPage( true ) }
					>
						{ __( 'Get the Course theme', 'sensei-lms' ) }
					</button>
				</div>
			) }

			<div
				className={ classnames(
					'sensei-setup-wizard__content sensei-setup-wizard__content--large',
					{
						'sensei-setup-wizard__content--hidden':
							isBigScreen && isScrolled,
					}
				) }
			>
				<div className="sensei-setup-wizard__title">
					<H className="sensei-setup-wizard__step-title">
						{ __( 'Use our default theme', 'sensei-lms' ) }
					</H>
					<p>
						{ __(
							"'Course' is a free WordPress theme built to work perfectly with Sensei and courses. You can use any WordPress theme with Sensei, or activate 'Course'.",
							'sensei-lms'
						) }
					</p>
				</div>

				<div className="sensei-setup-wizard__actions sensei-setup-wizard__actions--full-width">
					{ errorNotice }
					<div className="sensei-setup-wizard__theme-actions">
						<button
							disabled={ isSubmitting }
							className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
							onClick={ submitPage( true ) }
						>
							{ __( 'Get the Course theme', 'sensei-lms' ) }
						</button>

						<button
							className="sensei-setup-wizard__button sensei-setup-wizard__button--secondary sensei-setup-wizard__button--only-medium"
							onClick={ scrollToThemeContent }
						>
							{ __( 'Explore the theme', 'sensei-lms' ) }
						</button>
					</div>

					<div className="sensei-setup-wizard__action-skip">
						<button
							disabled={ isSubmitting }
							className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
							onClick={ submitPage( false ) }
						>
							{ __( 'Keep my current theme', 'sensei-lms' ) }
						</button>
					</div>
				</div>
			</div>

			<div ref={ themeContentRef }>
				{ isBigScreen ? <BigScreen /> : <SmallScreen /> }
			</div>
		</>
	);
};

export default Theme;
