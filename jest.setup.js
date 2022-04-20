/**
 * External dependencies
 */
import MutationObserver from '@sheerun/mutationobserver-shim';
import nock from 'nock';
/**
 * WordPress dependencies
 */
import '@wordpress/jest-preset-default/scripts/setup-globals';
import '@testing-library/jest-dom';
import 'whatwg-fetch';

window.MutationObserver = MutationObserver;
beforeAll( () => nock.cleanAll() );

beforeAll( () => nock.cleanAll() );
