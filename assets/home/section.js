/**
 * Renders the section title, or null if there's no section title.
 *
 * @param {Object}           props       Component props.
 * @param {string|undefined} props.title The title of the section, if defined.
 */
const SectionTitle = ( { title } ) => {
	if ( ! title ) {
		return null;
	}
	return (
		<div className="postbox-header">
			<h2 className="hndle">{ title }</h2>
		</div>
	);
};

/**
 * Component that looks like a metabox, but it's not a metabox.
 *
 * @param {Object}       props          Component props.
 * @param {string}       props.title    Section title.
 * @param {Object|Array} props.children Section content.
 */
const Section = ( { title, children } ) => {
	return (
		<div className="postbox">
			<SectionTitle title={ title } />
			<div className="inside">{ children }</div>
		</div>
	);
};

export default Section;
