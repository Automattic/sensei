/**
 * First Course component.
 *
 * @param {Object} props               Component props.
 * @param {string} props.siteTitle     Site title.
 * @param {string} props.courseTitle   Course title.
 * @param {string} props.siteLogo      Site logo.
 * @param {string} props.featuredImage Course featured image.
 */
const FirstCourse = ( { siteTitle, courseTitle, siteLogo, featuredImage } ) => (
	<section className="sensei-home-first-course">
		<header className="sensei-home-first-course__site-header">
			{ siteLogo && (
				<img
					className="sensei-home-first-course__site-logo"
					src={ siteLogo }
					alt="Site logo"
				/>
			) }
			{ siteTitle || (
				<div className="sensei-home-first-course__site-title-placeholder" />
			) }
		</header>
		<div className="sensei-home-first-course__content">
			<h3 className="sensei-home-first-course__course-title">
				{ courseTitle || (
					<div className="sensei-home-first-course__course-title-placeholder" />
				) }
			</h3>
			<div
				className="sensei-home-first-course__featured-image"
				style={
					featuredImage && {
						backgroundImage: `url("${ featuredImage }")`,
					}
				}
			/>
		</div>
	</section>
);

export default FirstCourse;
