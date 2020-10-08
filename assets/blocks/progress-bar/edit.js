/**
 * Edit course progress bar component.
 */
const EditProgressBarBlock = () => {
	return (
		<div>
			<section className="wp-block-sensei-lms-progress-heading">
				<div className="wp-block-sensei-lms-progress-heading__lessons">
					5 Lessons
				</div>
				<div className="wp-block-sensei-lms-progress-heading__completed">
					3 completed (60%)
				</div>
			</section>
			<progress
				className="wp-block-sensei-lms-progress-bar"
				max="100"
				value="50"
			/>
		</div>
	);
};

export default EditProgressBarBlock;
