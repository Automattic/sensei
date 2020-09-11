<?php
/**
 * This file contains the Sensei_Export_Package_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Export_Package class.
 *
 * @group data-port
 */
class Sensei_Export_Package_Tests extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'Package tests require ZipArchive' );
		}
	}

	/**
	 * Tests the basic packaging of CSVs.
	 */
	public function testRun() {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$job = Sensei_Export_Job::create( 'test', 0 );
		$job->save_file( 'course', wp_tempnam( 'courses' ), 'courses.csv' );
		$job->save_file( 'lesson', wp_tempnam( 'lessons' ), 'lessons.csv' );
		$job->save_file( 'question', wp_tempnam( 'questions' ), 'questions.csv' );

		$package = new Sensei_Export_Package( $job );
		$package->run();

		$package_zip_file = $job->get_file_path( 'package' );
		$this->assertNotFalse( $package_zip_file );

		$zip        = new ZipArchive();
		$opened_zip = $zip->open( $package_zip_file );
		$this->assertTrue( $opened_zip, 'Readable zip file was not produced' );

		$filenames = [];
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHP core class property.
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$filenames[] = $zip->getNameIndex( $i );
		}
		$zip->close();

		$this->assertEquals( 3, count( $filenames ), 'Zip should include 3 files' );

		$expected_files = [ 'courses.csv', 'lessons.csv', 'questions.csv' ];
		foreach ( $expected_files as $filename ) {
			$this->assertTrue( in_array( $filename, $filenames, true ), "Zip should have included the file {$filename}" );
		}
	}

}
