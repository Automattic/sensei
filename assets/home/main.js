/**
 * WordPress dependencies
 */
import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { EditorNotices } from '@wordpress/editor';
import { RawHTML } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { useSenseiColorTheme } from '../react-hooks/use-sensei-color-theme';
import FeaturedProductSenseiPro from './featured-product-sensei-pro';
import Header from './header';
import AllExtensions from './all-extensions';
import { EXTENSIONS_STORE } from '../extensions/store';
import { Grid, Col } from './grid';
import Section from './section';

const Main = () => {
	useSenseiColorTheme();

	const { extensions, layout, isExtensionsLoading, error } = useSelect(
		( select ) => {
			const store = select( EXTENSIONS_STORE );

			return {
				isExtensionsLoading: ! store.hasFinishedResolution(
					'getExtensions'
				),
				extensions: store.getExtensions(),
				layout: store.getLayout(),
				error: store.getError(),
			};
		},
		[]
	);

	if ( isExtensionsLoading ) {
		return (
			<div className="sensei-home__loader">
				<Spinner />
			</div>
		);
	}

	if ( 0 === extensions.length || 0 === layout.length ) {
		return <div>{ __( 'No extensions found.', 'sensei-lms' ) }</div>;
	}

	/**
	 * Filters the featured product display.
	 *
	 * @since 4.1.0
	 *
	 * @param {boolean} hideFeaturedProduct Whether to hide the extensions featured product.
	 */
	const hideFeaturedProduct = applyFilters(
		'senseiExtensionsFeaturedProductHide',
		false
	);

	/**
	 * Filters the featured product component.
	 *
	 * @param {Object} FeaturedProduct Component.
	 */
	const FeaturedProduct = applyFilters(
		'senseiExtensionsFeaturedProduct',
		FeaturedProductSenseiPro
	);

	return (
		<>
			<Grid as="main" className="sensei-home">
				<Col className="sensei-home__section" cols={ 12 }>
					{ ! hideFeaturedProduct && <FeaturedProduct /> }

					<Header />

					{ error !== null && (
						<Notice status="error" isDismissible={ false }>
							<RawHTML>{ error }</RawHTML>
						</Notice>
					) }
				</Col>
				<Col cols={ 12 }>
					<Section>Task List stuff</Section>
				</Col>

				<Col cols={ 6 }>
					<Section title="Quick Links">
						<ul>
							<li>Test</li>
						</ul>
					</Section>
				</Col>
				<Col cols={ 6 }>
					<Section title="Get help">
						<ul>
							<li>Test 2</li>
						</ul>
					</Section>
				</Col>
				<Col cols={ 6 }>
					<Section title="Sensei Guides">
						<ul>
							<li>Test 3</li>
						</ul>
					</Section>
				</Col>
				<Col cols={ 6 }>
					<Section title="Latest News">
						<ul>
							<li>Test 4</li>
						</ul>
					</Section>
				</Col>

				<Col cols={ 12 }>
					<Section title="Extensions">
						<AllExtensions layout={ layout } />
					</Section>
				</Col>
			</Grid>
			<EditorNotices />
		</>
	);
};

export default Main;
