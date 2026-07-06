/**
 * External dependencies
 */
import type { FrameLocator, Page } from '@playwright/test';

/**
 * Get a locator scoped to the block editor's canvas iframe.
 *
 * Since all Sensei blocks were migrated to Block API version 3, the post
 * editor now renders its canvas inside an `iframe[name="editor-canvas"]`
 * (see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-api-versions/README.md).
 * Locators that target block content must go through this frame; toolbars,
 * popovers, and modals (including Sensei's own wizard) still render in the
 * top-level document.
 *
 * @param {Page} page Playwright page.
 */
export function getEditorCanvas( page: Page ): FrameLocator {
	return page.frameLocator( 'iframe[name="editor-canvas"]' );
}
