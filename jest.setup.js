/**
 * External dependencies
 */
import MutationObserver from '@sheerun/mutationobserver-shim';
/**
 * WordPress dependencies
 */
import '@wordpress/jest-preset-default/scripts/setup-globals';
import '@testing-library/jest-dom';
window.MutationObserver = MutationObserver;
