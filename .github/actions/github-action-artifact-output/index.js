const core = require( '@actions/core' );
const github = require( '@actions/github' );

async function run() {
	try {
		const GITHUB_TOKEN = core.getInput( 'GITHUB_TOKEN' );
		const octokit = github.getOctokit( GITHUB_TOKEN );

		const context = github.context;

		const { owner, repo } = context.repo;

		console.dir( context.payload );
		const artifactsResponse = await octokit.rest.actions.listWorkflowRunArtifacts(
			{
				owner,
				repo,
				run_id: context.payload.workflow_run.id,
			}
		);

		const baseUrl = `https://github.com / ${ owner } / ${ repo } / suites / ${ context.payload.workflow_run.check_suite_id }`;

		console.dir( artifactsResponse );

		const artifacts_list = artifactsResponse.data.artifacts.map(
			( artifact ) => {
				return {
					id: artifact.id,
					name: artifact.name,
					url: `${ baseUrl } / artifacts / ${ artifact.id }`,
				};
			}
		);
		const artifact_url_by_name = artifacts_list.reduce(
			( result, artifact ) => {
				result[ artifact.name ] = artifact.url;
				return result;
			},
			{}
		);
		core.setOutput( 'artifacts_list', artifacts_list );
		core.setOutput( 'artifact_url_by_name', artifact_url_by_name );
	} catch ( error ) {
		core.info( error );
		core.setFailed( error.message );
	}
}

run();
