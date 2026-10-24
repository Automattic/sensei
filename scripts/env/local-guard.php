<?php
/**
 * Keep development copies from sending mail or starting background jobs.
 *
 * @package sensei
 */

add_filter( 'pre_wp_mail', '__return_true' );
add_filter( 'action_scheduler_allow_async_request_runner', '__return_false' );
