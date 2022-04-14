/**
 * External dependencies
 */
import MutationObserver from '@sheerun/mutationobserver-shim';
/**
 * WordPress dependencies
 */
import '@wordpress/jest-preset-default/scripts/setup-globals';
import '@testing-library/jest-dom';
import 'unfetch/polyfill';
window.MutationObserver = MutationObserver;
