/**
 * External dependencies.
 */
const fs = require( 'fs' );
const path = require( 'path' );

/**
 * Plugin name.
 *
 * @type {string}
 */
const PLUGIN_NAME = 'GenerateChunksMap';

class GenerateChunksMapPlugin {
	constructor( {
		output = path.resolve( '.', 'map.json' ),
		ignoreSrcPattern = false,
		baseDist = '',
	} = {} ) {
		this.output = output;
		this.ignoreSrcPattern = ignoreSrcPattern;
		this.baseDist = baseDist;
	}

	apply( compiler ) {
		compiler.hooks.done.tap( PLUGIN_NAME, ( { compilation } ) => {
			// Generate chunks map
			const { chunks } = compilation;

			const chunksMap = chunks.reduce( ( map, chunk ) => {
				const files = chunk.files;
				const name =
					files.find( ( file ) => /\.js$/.test( file ) ) ||
					files[ 0 ];

				const modules = [ ...chunk.modulesIterable ]
					.reduce(
						( acc, item ) => acc.concat( item.modules || item ),
						[]
					)
					.map(
						( { userRequest } ) =>
							userRequest && path.relative( '.', userRequest )
					)
					.filter( ( module ) => {
						if ( ! module ) {
							return false;
						}

						if ( this.ignoreSrcPattern ) {
							return ! this.ignoreSrcPattern.test( module );
						}

						return true;
					} );

				map[ this.baseDist + name ] = modules;

				return map;
			}, {} );

			// Write chunks map
			fs.writeFileSync( this.output, JSON.stringify( chunksMap ) );
		} );
	}
}

module.exports = GenerateChunksMapPlugin;
