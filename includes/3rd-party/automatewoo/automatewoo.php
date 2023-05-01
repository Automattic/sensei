<?php
/**
 * Adds additional compatibility with AutomateWoo.
 *
 * @package 3rd-Party
 */

namespace Sensei\AutomateWoo;

/**
 * Add the Sensei AutomateWoo actions.
 *
 * @since $$next-version$$
 *
 * @param array $actions The AutomateWoo actions.
 *
 * @return array
 */
function sensei_add_automatewoo_actions( $actions ): array {
	return array_merge(
		$actions,
		[
			'sensei_add_to_course'      => Actions\Add_To_Course_Action::class,
			'sensei_remove_from_course' => Actions\Remove_From_Course_Action::class,
		]
	);
}

add_filter( 'automatewoo/actions', 'Sensei\AutomateWoo\sensei_add_automatewoo_actions' );
