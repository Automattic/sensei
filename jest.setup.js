/**
 * External dependencies
 */
import nock from 'nock';
import 'whatwg-fetch';
import { TextDecoder, TextEncoder } from 'util';

/**
 * WordPress dependencies
 */
import '@wordpress/jest-preset-default/scripts/setup-globals';
import '@testing-library/jest-dom';

if ( ! global.TextDecoder ) {
	global.TextDecoder = TextDecoder;
}
if ( ! global.TextEncoder ) {
	global.TextEncoder = TextEncoder;
}

beforeAll( () => nock.cleanAll() );
