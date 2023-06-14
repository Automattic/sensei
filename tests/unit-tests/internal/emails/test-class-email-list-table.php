<?php

namespace SenseiTest\Internal\Emails;

use ReflectionMethod;
use Sensei\Internal\Emails\Email_List_Table;
use Sensei\Internal\Emails\Email_Preview;
use Sensei\Internal\Emails\Email_Repository;
use Sensei_Factory;
use stdClass;
use WP_Post;

/**
 * Tests for Sensei\Internal\Emails\Email_List_Table.
 *
 * @covers \Sensei\Internal\Emails\Email_List_Table
 */
class Email_List_Table_Test extends \WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testConstruct_WhenCalled_RemovesTableSearchFormHook() {
		/* Act. */
		$list_table = new Email_List_Table( new Email_Repository() );

		/* Assert. */
		$priority = has_action( 'sensei_before_list_table', [ $list_table, 'table_search_form' ] );
		$this->assertFalse( $priority );
	}

	public function testGetColumns_WhenCalled_AppliesHook() {
		if ( ! version_compare( get_bloginfo( 'version' ), '6.1.0', '>=' ) ) {
			$this->markTestSkipped( 'Requires `did_filter()` which was introduced in WordPress 6.1.0.' );
		}

		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );

		/* Act. */
		$columns = $list_table->get_columns();

		/* Assert. */
		$applied = did_filter( 'sensei_email_list_columns' );
		$this->assertSame( 1, $applied );
	}

	public function testGetColumns_WhenCalled_ContainsCheckboxColumn() {
		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );

		/* Act. */
		$columns = $list_table->get_columns();

		/* Assert. */
		$this->assertArrayHasKey( 'cb', $columns );
		$this->assertEquals( '<input type="checkbox" />', $columns['cb'] );
	}

	public function testPrepareItems_WhenPaginated_SetsTheCorrectOffset() {
		/* Arrange. */
		$repository = $this->createMock( Email_Repository::class );
		$list_table = new Email_List_Table( $repository );

		$_REQUEST['paged'] = 2;

		/* Expect & Act. */
		$repository
			->expects( $this->once() )
			->method( 'get_all' )
			->with( null, 20, 20 )
			->willReturn( $this->get_empty_items() );
		$list_table->prepare_items();
	}

	private function get_empty_items() {
		return (object) [
			'items'       => [],
			'total_items' => 0,
			'total_pages' => 0,
		];
	}

	public function testPrepareItems_WhenEmailTypeSet_SetsTheMetaQuery() {
		/* Arrange. */
		$repository = $this->createMock( Email_Repository::class );
		$list_table = new Email_List_Table( $repository );

		/* Expect & Act. */
		$repository
			->expects( $this->once() )
			->method( 'get_all' )
			->with( 'student', 20, 0 )
			->willReturn( $this->get_empty_items() );
		$list_table->prepare_items( 'student' );
	}

	public function testPrepareItems_WhenHasItems_SetsTheItems() {
		/* Arrange. */
		$posts      = [ new WP_Post( new stdClass() ), new WP_Post( new stdClass() ) ];
		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'get_all' )->willReturn(
			(object) [
				'items'       => $posts,
				'total_items' => 2,
				'total_pages' => 1,
			]
		);
		$list_table = new Email_List_Table( $repository );

		/* Act. */
		$list_table->prepare_items();

		/* Assert. */
		$this->assertSame( $posts, $list_table->items );
	}

	public function testGetRowData_WhenCalled_AppliesHook() {
		if ( ! version_compare( get_bloginfo( 'version' ), '6.1.0', '>=' ) ) {
			$this->markTestSkipped( 'Requires `did_filter()` which was introduced in WordPress 6.1.0.' );
		}

		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );
		$post_id    = $this->factory->email->create();

		update_post_meta( $post_id, '_sensei_email_description', 'description' );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		ob_end_clean();

		/* Assert. */
		$applied = did_filter( 'sensei_email_list_row_data' );
		$this->assertSame( 1, $applied );
	}

	public function testGetRowData_WhenHasItem_ReturnsTheItemRowData() {
		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );
		$post       = $this->factory->email->create_and_get();

		update_post_meta( $post->ID, '_sensei_email_description', 'description' );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$expected = sprintf(
			'<tr class="sensei-wp-list-table-row--enabled">' .
			'<th class=\'cb column-cb check-column\'  ><label class="screen-reader-text">Select %2$s</label><input id="cb-select-%1$s" type="checkbox" name="email[]" value="%1$s" /></th>' .
			'<td class=\'description column-description column-primary\' data-colname="Email" ><strong><a href="" class="row-title">%5$s</a></strong><div class="row-actions"><span class=\'edit\'><a href="" aria-label="Edit &#8220;%2$s&#8221;">Edit</a> | </span><span class=\'disable-email\'><a href="%3$s" aria-label="Disable &#8220;%2$s&#8221;">Disable</a> | </span><span class=\'preview-email\'><a href="%4$s" aria-label="Preview &#8220;%2$s&#8221;">Preview</a></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>' .
			'<td class=\'subject column-subject\' data-colname="Subject" >%2$s</td>' .
			'<td class=\'last_modified column-last_modified\' data-colname="Last Modified" >1 second ago</td>' .
			'</tr>',
			$post->ID,
			$post->post_title,
			wp_nonce_url( "post.php?action=disable-email&amp;post=$post->ID", 'disable-email-post_' . $post->ID ),
			Email_Preview::get_preview_link( $post->ID ),
			'description'
		);

		$this->assertStringContainsString( $expected, $result );
	}

	public function testGetRowData_WhenHasItemWithNoTitle_ReturnsNoTitleText() {
		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );
		$post_id    = $this->factory->email->create( [ 'post_title' => '' ] );

		update_post_meta( $post_id, '_sensei_email_description', 'description' );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( '(no title)', $result );
	}

	public function testGetRowData_WhenHasDescription_ReturnsTheDescription() {
		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );
		$post_id    = $this->factory->email->create();

		update_post_meta( $post_id, '_sensei_email_description', 'Welcome Student' );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( 'Welcome Student', $result );
	}

	public function testGetRowData_WhenWasModified1HourAgo_ReturnsTheCorrectModifiedTime() {
		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );
		$post       = $this->factory->email->create_and_get(
			[
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
			]
		);

		update_post_meta( $post->ID, '_sensei_email_description', 'description' );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( '1 hour ago', $result );
	}

	public function testGetRowClass_WhenItemIsPublished_ReturnsEnabledClass() {
		/* Arrange. */
		$post       = $this->factory->email->create_and_get();
		$list_table = new Email_List_Table( new Email_Repository() );
		$method     = new ReflectionMethod( $list_table, 'get_row_class' );
		$method->setAccessible( true );

		/* Act. */
		$result = $method->invokeArgs( $list_table, [ $post ] );

		/* Assert. */
		$this->assertSame( 'sensei-wp-list-table-row--enabled', $result );
	}

	public function testGetRowClass_WhenItemIsNotPublished_ReturnsDisabledClass() {
		/* Arrange. */
		$post       = $this->factory->email->create_and_get( [ 'post_status' => 'draft' ] );
		$list_table = new Email_List_Table( new Email_Repository() );
		$method     = new ReflectionMethod( $list_table, 'get_row_class' );
		$method->setAccessible( true );

		/* Act. */
		$result = $method->invokeArgs( $list_table, [ $post ] );

		/* Assert. */
		$this->assertSame( 'sensei-wp-list-table-row--disabled', $result );
	}

	public function testGetRowData_WhenHasProItem_ReturnsDataWithDisabledProEmailStructure() {
		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );
		$post       = $this->factory->email->create_and_get();

		update_post_meta( $post->ID, '_sensei_email_description', 'description' );
		update_post_meta( $post->ID, '_sensei_email_is_pro', true );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$expected = sprintf(
			'<tr class="sensei-wp-list-table-row--disabled">' .
			'<th class=\'cb column-cb check-column\'  ><label class="screen-reader-text">Select %2$s</label><input id="cb-select-%1$s" type="checkbox" name="email[]" value="%1$s" /></th>' .
			'<td class=\'description column-description column-primary\' data-colname="Email" ><strong class="sensei-email-unavailable">description</strong><span class="awaiting-mod sensei-upsell-pro-badge">Pro</span><div class="row-actions"><span class=\'upgrade-to-pro\'><a href="https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&#038;utm_medium=upsell&#038;utm_campaign=email_customization_pro" aria-label="Upgrade to Sensei Pro">Upgrade to Sensei Pro</a></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>' .
			'<td class=\'subject column-subject\' data-colname="Subject" >%2$s</td>' .
			'<td class=\'last_modified column-last_modified\' data-colname="Last Modified" >1 second ago</td>' .
			'</tr>',
			$post->ID,
			$post->post_title,
			wp_nonce_url( "post.php?action=disable-email&amp;post=$post->ID", 'disable-email-post_' . $post->ID ),
			Email_Preview::get_preview_link( $post->ID ),
			'description'
		);

		$this->assertStringContainsString( $expected, $result );
	}

	public function testGetRowData_WhenHasProItemButHookIsSetToTrue_ReturnsTheProEmailEnabledAsFreeEmails() {
		/* Arrange. */
		$list_table = new Email_List_Table( new Email_Repository() );
		$post       = $this->factory->email->create_and_get();

		update_post_meta( $post->ID, '_sensei_email_description', 'description' );
		update_post_meta( $post->ID, '_sensei_email_is_pro', true );
		add_filter( 'sensei_email_is_available', '__return_true' );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$expected = sprintf(
			'<tr class="sensei-wp-list-table-row--enabled">' .
			'<th class=\'cb column-cb check-column\'  ><label class="screen-reader-text">Select %2$s</label><input id="cb-select-%1$s" type="checkbox" name="email[]" value="%1$s" /></th>' .
			'<td class=\'description column-description column-primary\' data-colname="Email" ><strong><a href="" class="row-title">%5$s</a></strong><div class="row-actions"><span class=\'edit\'><a href="" aria-label="Edit &#8220;%2$s&#8221;">Edit</a> | </span><span class=\'disable-email\'><a href="%3$s" aria-label="Disable &#8220;%2$s&#8221;">Disable</a> | </span><span class=\'preview-email\'><a href="%4$s" aria-label="Preview &#8220;%2$s&#8221;">Preview</a></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>' .
			'<td class=\'subject column-subject\' data-colname="Subject" >%2$s</td>' .
			'<td class=\'last_modified column-last_modified\' data-colname="Last Modified" >1 second ago</td>' .
			'</tr>',
			$post->ID,
			$post->post_title,
			wp_nonce_url( "post.php?action=disable-email&amp;post=$post->ID", 'disable-email-post_' . $post->ID ),
			Email_Preview::get_preview_link( $post->ID ),
			'description'
		);

		$this->assertStringContainsString( $expected, $result );
	}
}
