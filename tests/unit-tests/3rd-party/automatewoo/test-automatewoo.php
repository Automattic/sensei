<?php

namespace SenseiTest\AutomateWoo;

use function Sensei\AutomateWoo\sensei_add_automatewoo_actions;

/**
 * Tests for the AutomateWoo initialization.
 */
class Test_AutomateWoo extends \WP_UnitTestCase {
	public function testInitialization_WhenLoaded_AddsHooks() {
		/* Assert. */
		$this->assertSame( 10, has_filter( 'automatewoo/actions', 'Sensei\AutomateWoo\sensei_add_automatewoo_actions' ) );
	}

	public function testSensei_Add_Automatewoo_Actions_WhenCalled_AddsActions() {
		/* Act. */
		$actions = sensei_add_automatewoo_actions( [] );

		/* Assert. */
		$this->assertSame(
			[
				'sensei_add_to_course'      => \Sensei\AutomateWoo\Actions\Add_To_Course_Action::class,
				'sensei_remove_from_course' => \Sensei\AutomateWoo\Actions\Remove_From_Course_Action::class,
			],
			$actions
		);
	}
}
