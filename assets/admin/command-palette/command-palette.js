/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useCommand, useCommandLoader } from '@wordpress/commands';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useMemo } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { plus, page } from '@wordpress/icons';

const COURSE = { slug: 'course', label: __( 'Course', 'sensei-lms' ) };
const LESSON = { slug: 'lesson', label: __( 'Lesson', 'sensei-lms' ) };
const QUESTION = { slug: 'question', label: __( 'Question', 'sensei-lms' ) };

/**
 * Registers an "Add new <post type>" command. `useCommand` has no built-in
 * visibility check, so this is only rendered by `PostTypeCommands` for users
 * who can create that post type.
 *
 * @param {Object} postType       Post type descriptor.
 * @param {string} postType.slug  Registered post type slug.
 * @param {string} postType.label Post type label used in the command label.
 */
function AddNewCommand( { slug, label } ) {
	useCommand( {
		name: `sensei-lms/add-new-${ slug }`,
		label: sprintf(
			/* translators: %s: Post type label, e.g. "Course". */
			__( 'Add new %s', 'sensei-lms' ),
			label
		),
		icon: plus,
		callback: ( { close } ) => {
			close();
			document.location.href = `post-new.php?post_type=${ slug }`;
		},
	} );

	return null;
}

/**
 * Registers a command loader that searches existing posts of the given type
 * and navigates to their edit screen.
 *
 * @param {Object} postType       Post type descriptor.
 * @param {string} postType.slug  Registered post type slug.
 * @param {string} postType.label Post type label used in the command label.
 */
function SearchCommand( { slug, label } ) {
	/**
	 * Searches posts of `slug` matching the palette's current search term.
	 *
	 * @param {Object} args        Loader args from the command palette.
	 * @param {string} args.search Current search term.
	 */
	function useSenseiSearchLoader( { search } ) {
		const { records, isLoading } = useSelect(
			( select ) => {
				const { getEntityRecords, hasFinishedResolution } =
					select( coreStore );
				const query = { search: search || undefined, per_page: 10 };

				return {
					records: getEntityRecords( 'postType', slug, query ),
					isLoading: ! hasFinishedResolution( 'getEntityRecords', [
						'postType',
						slug,
						query,
					] ),
				};
			},
			[ search ]
		);

		return useMemo(
			() => ( {
				isLoading,
				commands: ( records ?? [] ).map( ( record ) => ( {
					name: `sensei-lms/${ slug }-${ record.id }`,
					label: sprintf(
						/* translators: 1: Post type label, 2: Post title. */
						__( '%1$s: %2$s', 'sensei-lms' ),
						label,
						record.title?.rendered ||
							__( '(no title)', 'sensei-lms' )
					),
					icon: page,
					callback: ( { close } ) => {
						close();
						document.location.href = addQueryArgs( 'post.php', {
							post: record.id,
							action: 'edit',
						} );
					},
				} ) ),
			} ),
			[ records, isLoading ]
		);
	}

	useCommandLoader( {
		name: `sensei-lms/search-${ slug }`,
		hook: useSenseiSearchLoader,
	} );

	return null;
}

/**
 * Registers both commands for a Sensei post type. The "Add new" command is
 * only rendered for users who can create that post type — `AddNewCommand`
 * is conditionally mounted rather than conditionally calling `useCommand`,
 * since hooks can't be called conditionally.
 *
 * @param {Object} postType       Post type descriptor.
 * @param {string} postType.slug  Registered post type slug.
 * @param {string} postType.label Post type label used in the command label.
 */
function PostTypeCommands( postType ) {
	const canCreate = useSelect(
		( select ) =>
			select( coreStore ).canUser( 'create', {
				kind: 'postType',
				name: postType.slug,
			} ),
		[ postType.slug ]
	);

	return (
		<>
			{ canCreate && <AddNewCommand { ...postType } /> }
			<SearchCommand { ...postType } />
		</>
	);
}

/**
 * Registers Course, Lesson and Question commands with the block editor
 * Command Palette (Cmd/Ctrl+K).
 */
export default function CommandPalette() {
	return (
		<>
			<PostTypeCommands { ...COURSE } />
			<PostTypeCommands { ...LESSON } />
			<PostTypeCommands { ...QUESTION } />
		</>
	);
}
