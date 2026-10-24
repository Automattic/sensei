const { spawn } = require( 'node:child_process' );

// Keep SQL off disk and check both processes, including a consumer that closes early.
async function stream( source, target ) {
	const children = [];
	const errors = [];
	let timer;
	function stop( error ) {
		errors.push( error );
		for ( const child of children ) {
			if ( child.exitCode === null ) {
				child.kill();
			}
		}
		if ( ! timer ) {
			timer = setTimeout( () => {
				for ( const child of children ) {
					if ( child.exitCode === null ) {
						child.kill( 'SIGKILL' );
					}
				}
			}, 1000 );
		}
	}
	function launch( spec, label ) {
		const child = spawn( spec.executable, spec.args, {
			...spec.options,
			stdio: [ 'pipe', 'pipe', 'pipe' ],
		} );
		children.push( child );
		let stderr = '';
		child.stderr.on( 'data', ( data ) => {
			stderr = ( stderr + data ).slice( -8192 );
		} );
		child.on( 'error', ( error ) =>
			stop( new Error( `${ label } failed: ${ error.message }` ) )
		);
		const done = new Promise( ( resolve ) => {
			child.on( 'close', ( code ) => {
				if ( code !== 0 ) {
					stop(
						new Error(
							`${ label } failed (${ code }). ${ stderr }`
						)
					);
				}
				resolve();
			} );
		} );
		return { child, done };
	}
	const exporter = launch( source, 'Database export' );
	const importer = launch( target, 'Database import' );
	exporter.child.stdin.end();
	importer.child.stdout.resume();
	exporter.child.stdout.on( 'error', stop );
	importer.child.stdin.on( 'error', stop );
	importer.child.on( 'exit', ( code ) => {
		if ( code === 0 && ! exporter.child.stdout.readableEnded ) {
			stop(
				new Error(
					'Database import closed before the export finished.'
				)
			);
		}
	} );
	exporter.child.stdout.pipe( importer.child.stdin );
	try {
		await Promise.all( [ exporter.done, importer.done ] );
		if ( errors.length ) {
			throw errors[ 0 ];
		}
	} finally {
		clearTimeout( timer );
		exporter.child.stdout.destroy();
		importer.child.stdin.destroy();
	}
}
module.exports = { stream };
