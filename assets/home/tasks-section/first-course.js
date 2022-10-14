/**
 * First Course component.
 *
 * @param {Object} props             Component props.
 * @param {string} props.siteTitle   Site title.
 * @param {string} props.siteImage   Site image.
 * @param {string} props.courseTitle Course title.
 * @param {string} props.courseImage Course image.
 */
const FirstCourse = ( { siteTitle, siteImage, courseTitle, courseImage } ) => (
	<section className="sensei-home-first-course">
		<header className="sensei-home-first-course__site-header">
			{ siteImage && (
				<img
					className="sensei-home-first-course__site-logo"
					src={ siteImage }
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
					courseImage && {
						backgroundImage: `url("${ courseImage }")`,
					}
				}
			/>
		</div>
	</section>
);

export default FirstCourse;
