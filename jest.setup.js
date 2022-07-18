/**
 * External dependencies
 */
import nock from 'nock';
import 'whatwg-fetch';

/**
 * WordPress dependencies
 */
import '@wordpress/jest-preset-default/scripts/setup-globals';
import '@testing-library/jest-dom';

beforeAll( () => nock.cleanAll() );
