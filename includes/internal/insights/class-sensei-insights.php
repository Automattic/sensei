<?php
/**
 * File containing the Sensei_Insights class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Insights;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course Insights bootstrapper.
 *
 * Entry point for the Course Insights feature. Responsible for wiring up
 * the REST controller, admin assets, and cache-invalidation hooks when the
 * feature flag is enabled.
 *
 * When the flag is disabled, {@see Sensei_Insights::init()} is a no-op.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Sensei_Insights {
	/**
	 * Feature flag name.
	 */
	public const FEATURE_FLAG = 'course_insights';

	/**
	 * Whether the feature is enabled.
	 *
	 * @var bool
	 */
	private $enabled;

	/**
	 * Constructor.
	 *
	 * @param bool $enabled Whether the course_insights feature flag is enabled.
	 */
	public function __construct( bool $enabled ) {
		$this->enabled = $enabled;
	}

	/**
	 * Initialize the feature.
	 *
	 * No-op when the feature flag is disabled.
	 */
	public function init(): void {
		if ( ! $this->enabled ) {
			return;
		}

		// Hooks land in later tasks (REST registration, enqueue, cache invalidation).
	}

	/**
	 * Whether the feature is currently enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}
}
