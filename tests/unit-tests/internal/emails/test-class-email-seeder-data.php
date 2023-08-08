<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Seeder_Data;

/**
 * Tests for Sensei\Internal\Emails\Email_Seeder_Data class.
 *
 * @covers \Sensei\Internal\Emails\Email_Seeder_Data
 */
class Email_Seeder_Data_Test extends \WP_UnitTestCase {


	private $email_keys = [
		'course_created',
		'course_welcome',
		'quiz_graded',
		'course_completed',
		'student_starts_course',
		'student_completes_course',
		'student_completes_lesson',
		'student_submits_quiz',
		'student_sends_message',
		'new_course_assigned',
		'student_message_reply',
		'teacher_message_reply',
		'content_drip',
		'student_no_progress_3_days',
		'student_no_progress_7_days',
		'student_no_progress_28_days',
		'course_expiration_today',
		'course_expiration_3_days',
		'course_expiration_7_days',
	];

	public function testGetEmailData_Always_ReturnsEmailData() {
		/* Arrange. */
		$email_seeder_data = new Email_Seeder_Data();

		/* Act. */
		$email_data = $email_seeder_data->get_email_data();

		/* Assert. */
		$this->assertNotEmpty( $email_data );
	}

	public function testGetEmailData_Always_ReturnsArrayWithExpectedKeys() {
		/* Arrange. */
		$email_seeder_data = new Email_Seeder_Data();

		/* Act. */
		$email_data = $email_seeder_data->get_email_data();

		/* Assert. */
		self::assertSame( $this->email_keys, array_keys( $email_data ) );
	}

	public function testGetEmailData_Always_ReturnsArrayWithExpectedProValues() {
		/* Arrange. */
		$email_seeder_data = new Email_Seeder_Data();
		$pro_email_keys    = [
			'content_drip',
			'student_no_progress_3_days',
			'student_no_progress_7_days',
			'student_no_progress_28_days',
			'course_expiration_today',
			'course_expiration_3_days',
			'course_expiration_7_days',
		];

		/* Act. */
		$email_data = $email_seeder_data->get_email_data();

		/* Assert. */
		foreach ( $this->email_keys as $email_key ) {
			$is_pro = in_array( $email_key, $pro_email_keys, true );
			$this->assertSame( $is_pro, $email_data[ $email_key ]['is_pro'] ?? false );
		}
	}
}
