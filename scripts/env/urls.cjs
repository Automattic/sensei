const { current } = require( './environment.cjs' );
const state = current();
if ( ! state && ! process.env.CI ) {
	throw new Error( 'No environment for this worktree. Run make up first.' );
}
module.exports = {
	testURL:
		process.env.SENSEI_TEST_URL ||
		`http://localhost:${ state?.testsPort || 8889 }`,
};
