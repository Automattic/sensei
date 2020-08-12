const Lesson = ( { href, children } ) => {
	const content = href ? <a href={ href }>{ children }</a> : children;

	return (
		<div
			className="sensei-course-block-editor__lesson"
			style={ {
				borderBottom: '1px solid #32af7d',
				padding: '0.25em',
				display: 'flex',
				alignItems: 'center',
			} }
		>
			{ content }
		</div>
	);
};

export default Lesson;
