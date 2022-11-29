/**
 * WordPress dependencies
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

	useLayoutEffect( () => {
		const w = themeContentRef.current?.ownerDocument.defaultView;

		// Check if it's a big screen.
		const screenSizeCheck = () => {
			setIsBigScreen( window.innerWidth >= 600 );
		};

		// Checks if it was scrolled until the theme content.
		const scrollCheck = () => {
			setIsScrolled(
				themeContentRef.current.getBoundingClientRect().y < scrollOffset
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

		w.scrollBy( {
			top: themeContentRef.current.offsetTop - scrollOffset,
			behavior: 'smooth',
		} );
	};

	const goToNextStep = () => {
		goTo( 'tracking' );
	};

	return (
		<>
			{ isBigScreen && isScrolled && (
				<div className="sensei-setup-wizard-theme-top-actions sensei-setup-wizard-theme-top-actions--enter-animation">
					<button
						className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
						onClick={ goToNextStep }
					>
						{ __( 'Skip', 'sensei-lms' ) }
					</button>

					<button
						className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
						onClick={ goToNextStep }
					>
						{ __( 'Install the new Sensei theme', 'sensei-lms' ) }
					</button>
				</div>
			) }

			<div
				className={ classnames(
					'sensei-setup-wizard__content sensei-setup-wizard__content--large',
					{
						'sensei-setup-wizard__content--sticky': isBigScreen,
						'sensei-setup-wizard__content--hidden':
							isBigScreen && isScrolled,
					}
				) }
			>
				<div className="sensei-setup-wizard__title">
					<H className="sensei-setup-wizard__step-title">
						{ __( 'Get new Sensei theme', 'sensei-lms' ) }
					</H>
					<p>
						{ __(
							"The new Sensei theme it's build from ground up with Learning Mode in mind to optimize your full site so that everything works smootly together.",
							'sensei-lms'
						) }
					</p>
				</div>

				<div className="sensei-setup-wizard__actions sensei-setup-wizard__actions--full-width">
					<div className="sensei-setup-wizard__theme-actions">
						<button
							className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
							onClick={ goToNextStep }
						>
							{ __(
								'Install the new Sensei theme',
								'sensei-lms'
							) }
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
							className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
							onClick={ goToNextStep }
						>
							{ __( 'Skip theme selection', 'sensei-lms' ) }
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
