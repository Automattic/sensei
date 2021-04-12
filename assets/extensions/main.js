/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import QueryStringRouter, { Route } from '../shared/query-string-router';
import AllExtensions from './all-extensions';
import FilteredExtensions from './filtered-extensions';

// prettier-ignore
const extensionsMock = [{"version":"5.0.0.0.0.0","hash":"d1a69640d53a32a9fb13e93d1c8f3104","title":"WooCommerce Paid Courses","image":null,"excerpt":"Sell your courses using the most popular eCommerce platform on the web \u2013 WooCommerce.","link":"https:\/\/senseilms.com\/product\/woocommerce-paid-courses\/","price":"&#36;129.00","is_featured":false,"product_slug":"sensei-wc-paid-courses","hosted_location":"external","type":"plugin","plugin_file":"woothemes-sensei\/woothemes-sensei.php","wccom_product_id":"152116"},{"hash":"372d3f309fef061977fb2f7ba36d74d2","title":"Sensei Content Drip","image":null,"excerpt":"Keep students engaged and improve knowledge retention by setting a delivery schedule for course content.","has_update":true,"version":"4.0.0","link":"https:\/\/senseilms.com\/product\/sensei-content-drip\/","price":"&#36;29.00","is_featured":false,"product_slug":"sensei-content-drip","hosted_location":"external","type":"plugin","plugin_file":"sensei-content-drip\/sensei-content-drip.php","wccom_product_id":"543363"},{"hash":"08040837089cdf46631a10aca5258e16","title":"Sensei LMS Certificates","image":null,"excerpt":"Award your students with a certificate of completion and a sense of accomplishment after finishing a course.","link":"https:\/\/senseilms.com\/product\/sensei-certificates\/","price":0,"is_featured":false,"product_slug":"sensei-certificates","hosted_location":"dotorg","type":"plugin","plugin_file":"sensei-certificates\/woothemes-sensei-certificates.php"},{"hash":"99adff456950dd9629a5260c4de21858","title":"Sensei LMS Course Progress","image":null,"excerpt":"Enable your students to easily see their progress and pick up where they left off in a course.","link":"https:\/\/senseilms.com\/product\/sensei-course-progress\/","price":0,"is_featured":false,"product_slug":"sensei-course-progress","hosted_location":"dotorg","type":"plugin","plugin_file":"sensei-course-progress\/sensei-course-progress.php"},{"hash":"35309226eb45ec366ca86a4329a2b7c3","title":"Sensei LMS Media Attachments","image":null,"excerpt":"Provide your students with easy access to additional learning materials, from audio files to slideshows and PDFs.","link":"https:\/\/senseilms.com\/product\/sensei-media-attachments\/","price":0,"is_featured":false,"product_slug":"sensei-media-attachments","hosted_location":"dotorg","type":"plugin","plugin_file":"sensei-media-attachments\/sensei-media-attachments.php"},{"hash":"748ba69d3e8d1af87f84fee909eef339","title":"Sensei Share Your Grade","image":null,"excerpt":"Let your students strut their stuff (and promote your course) by sharing their progress on social media.","link":"https:\/\/senseilms.com\/product\/sensei-share-your-grade\/","price":0,"is_featured":false,"product_slug":"sensei-share-your-grade","hosted_location":"external","type":"plugin","plugin_file":"sensei-share-your-grade\/sensei-share-your-grade.php"},{"hash":"d63fbf8c3173730f82b150c5ef38b8ff","title":"Sensei Course Participants","image":null,"excerpt":"Increase course enrolments by showing site visitors just how popular your courses are.","link":"https:\/\/senseilms.com\/product\/sensei-course-participants\/","price":0,"is_featured":false,"product_slug":"sensei-course-participants","hosted_location":"external","type":"plugin","plugin_file":"sensei-course-participants\/sensei-course-participants.php"},{"hash":"73f104c9fba50050eea11d9d075247cc","title":"Sensei LMS Post to Course Creator","image":null,"excerpt":"Turn your blog posts into online courses.","link":"https:\/\/senseilms.com\/product\/sensei-lms-post-to-course-creator\/","price":0,"is_featured":false,"product_slug":"sensei-post-to-course","hosted_location":"dotorg","type":"plugin","plugin_file":"sensei-post-to-course\/sensei-post-to-course.php"},{"hash":"4fc848051e4459b8a6afeb210c3664ec","title":"Sensei LMS Modules for Divi","image":null,"excerpt":"Edit and style Sensei LMS elements using the Divi Builder.","link":"https:\/\/senseilms.com\/product\/sensei-lms-modules-for-divi\/","price":0,"is_featured":false,"product_slug":"sensei-lms-divi","hosted_location":"dotorg","type":"plugin","plugin_file":"sensei-lms-divi\/sensei-lms-divi.php"},{"hash":"f0bda020d2470f2e74990a07a607ebd9","title":"Collapsible Content for Sensei LMS","image":null,"excerpt":"Simplify the online learning experience for your students by enabling the collapsing and expanding of course content.","link":"https:\/\/senseilms.com\/product\/collapsible-content-for-sensei-lms\/","price":0,"is_featured":false,"product_slug":"collapsible-content-for-sensei-lms","hosted_location":"dotorg","type":"plugin","plugin_file":"collapsible-content-for-sensei-lms\/sensei-collapsible-content.php"}];

const Main = () => {
	const [ extensions, setExtensions ] = useState( false );

	useEffect( () => {
		// TODO: Update to real endpoint.
		apiFetch( { path: '/sensei-internal/v1/setup-wizard/features' } ).then(
			() => {
				setExtensions( extensionsMock );
			}
		);
	}, [] );

	if ( false === extensions ) {
		return (
			<div className="sensei-extensions__loader">
				<Spinner />
			</div>
		);
	}

	return (
		<main className="sensei-extensions">
			<div className="sensei-extensions__grid">
				<QueryStringRouter paramName="tab" defaultRoute="all">
					<div className="sensei-extensions__section sensei-extensions__grid__col --col-12">
						<Header />
						<Tabs />
					</div>

					<UpdateNotification extensions={ extensions } />

					<Route route="all">
						<AllExtensions />
					</Route>
					<Route route="free">
						<FilteredExtensions />
					</Route>
					<Route route="third-party">
						<FilteredExtensions />
					</Route>
					<Route route="installed">
						<FilteredExtensions />
					</Route>
				</QueryStringRouter>
			</div>
		</main>
	);
};

export default Main;
