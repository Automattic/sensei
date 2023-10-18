<?php
namespace SenseiTest\Internal\Tools;

use Sensei\Internal\Installer\Schema;
use Sensei\Internal\Tools\Progress_Tables_Eraser;

/**
 * Class Progress_Tables_Eraser_Test
 *
 * @covers \Sensei\Internal\Tools\Progress_Tables_Eraser
 */
class Progress_Tables_Eraser_Test extends \WP_UnitTestCase {

	/**
	 * Eraser instance.
	 *
	 * @var Progress_Tables_Eraser
	 */
	private $eraser;

	protected function setUp(): void {
		parent::setUp();
		$this->eraser = new Progress_Tables_Eraser( new Schema() );
	}

	public function testRegisterTool_Always_AddsItselfToTools(): void {
		/* Act. */
		$tools = $this->eraser->register_tool( array() );

		/* Assert. */
		self::assertSame( $this->eraser, $tools[0] );
	}

	public function testGetId_Always_ReturnsMatchingString(): void {
		/* Act. */
		$id = $this->eraser->get_id();

		/* Assert. */
		self::assertSame( 'student-progress-eraser', $id );
	}

	public function testGetName_Always_ReturnsMatchingString(): void {
		/* Act. */
		$name = $this->eraser->get_name();

		/* Assert. */
		self::assertSame( 'Erase content of student progress tables', $name );
	}

	public function testGetDescription_Always_ReturnsMatchingString(): void {

		/* Act. */
		$description = $this->eraser->get_description();

		/* Assert. */
		self::assertSame( 'Erase the content of the student progress and quiz submission tables. This will delete all data in those tables, but won\'t affect comment-based data.', $description );
	}

	public function testProcess_Always_DeletesTablesContents(): void {
		/* Arrange. */
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'post_id'        => 1,
				'user_id'        => 2,
				'parent_post_id' => 3,
				'type'           => 'a',
				'status'         => 'b',
				'started_at'     => '2000-01-01 00:00:00',
				'completed_at'   => '2000-01-01 00:00:00',
				'created_at'     => '2000-01-01 00:00:00',
				'updated_at'     => '2000-01-01 00:00:00',
			)
		);

		/* Act. */
		$this->eraser->process();

		/* Assert. */
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_progress" );
		self::assertSame( 0, $count );
	}

	public function testIsAvailable_Always_ReturnsTrue(): void {
		/* Act. */
		$result = $this->eraser->is_available();

		/* Assert. */
		self::assertTrue( $result );
	}
}
