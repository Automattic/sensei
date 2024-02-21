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

	public function filterWpRedirect( $location, $status ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		throw new \Exception( $location, $status );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->eraser = new Progress_Tables_Eraser();
		add_filter( 'wp_redirect', [ $this, 'filterWpRedirect' ], 10, 2 );
	}

	protected function tearDown(): void {
		parent::tearDown();
		remove_filter( 'wp_redirect', [ $this, 'filterWpRedirect' ], 10 );
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
		self::assertSame( 'progress-tables-eraser', $id );
	}

	public function testGetName_Always_ReturnsMatchingString(): void {
		/* Act. */
		$name = $this->eraser->get_name();

		/* Assert. */
		self::assertSame( 'Delete student progress tables', $name );
	}

	public function testGetDescription_Always_ReturnsMatchingString(): void {

		/* Act. */
		$description = $this->eraser->get_description();

		/* Assert. */
		self::assertSame( 'Delete student progress and quiz submission tables. This will delete those tables, but won\'t affect comment-based data. The tables can be deleted only if progress sync is disabled (Settings -> Experimental Features).', $description );
	}

	public function testProcess_ConfirmationProvided_DeletesTables(): void {
		/* Arrange. */
		$this->create_tables();

		$_POST['_wpnonce']      = wp_create_nonce( Progress_Tables_Eraser::NONCE_ACTION );
		$_POST['confirm']       = 'yes';
		$_POST['delete-tables'] = 'yes';

		/* Act. */
		$this->expectException( \Exception::class );
		$this->eraser->process();

		/* Assert. */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sensei_lms_progress'" );
		self::assertFalse( $table_exists );
	}


	public function testProcess_NoConfirmationProvided_DeletesTables(): void {
		/* Arrange. */
		$this->create_tables();

		$_POST['_wpnonce']      = wp_create_nonce( Progress_Tables_Eraser::NONCE_ACTION );
		$_POST['delete-tables'] = 'yes';

		/* Act. */
		$this->expectException( \Exception::class );
		$this->eraser->process();

		/* Assert. */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sensei_lms_progress'" );
		self::assertTrue( $table_exists );
	}

	public function testIsAvailable_HppsSyncEnabled_ReturnsFalse(): void {
		/* Arrange. */
		$settings                    = Sensei()->settings->settings;
		Sensei()->settings->settings = array( 'experimental_progress_storage_synchronization' => true );

		/* Act. */
		$result = $this->eraser->is_available();

		/* Assert. */
		Sensei()->settings->settings = $settings;
		self::assertFalse( $result );
	}

	public function testIsAvailable_HppsRepositoryWasTables_ReturnsFalse(): void {
		/* Arrange. */
		$settings                    = Sensei()->settings->settings;
		Sensei()->settings->settings = array(
			'experimental_progress_storage_synchronization' => false,
			'experimental_progress_storage_repository' => 'custom_tables',
		);

		/* Act. */
		$result = $this->eraser->is_available();

		/* Assert. */
		Sensei()->settings->settings = $settings;
		self::assertFalse( $result );
	}

	public function testIsAvailable_HppsSyncDisabledAndRepositoryWasComments_ReturnsTrue(): void {
		/* Arrange. */
		$settings                    = Sensei()->settings->settings;
		Sensei()->settings->settings = array(
			'experimental_progress_storage_synchronization' => false,
			'experimental_progress_storage_repository' => 'comments',
		);

		/* Act. */
		$result = $this->eraser->is_available();

		/* Assert. */
		Sensei()->settings->settings = $settings;
		self::assertTrue( $result );
	}

	private function create_tables(): void {
		$schema = new Schema( Sensei()->feature_flags );
		$schema->create_tables();
	}
}
