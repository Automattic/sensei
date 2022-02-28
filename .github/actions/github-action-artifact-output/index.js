const core = require( '@actions/core' );
const github = require( '@actions/github' );

async function run() {
	try {
		const GITHUB_TOKEN = core.getInput( 'GITHUB_TOKEN' );
		const octokit = github.getOctokit( GITHUB_TOKEN );

		const { context } = github;

		const { owner, repo } = context.repo;

		console.dir( {
			owner,
			repo,
			run_id: context.runId,
		} );

		const workflowRun = await octokit.rest.actions.getWorkflowRun( {
			owner,
			repo,
			run_id: context.runId,
		} );

		const artifactsResponse = await octokit.rest.actions.listWorkflowRunArtifacts(
			{
				owner,
				repo,
				run_id: context.runId,
			}
		);

		const baseUrl =
			'https://github.com/' +
			owner +
			'/' +
			repo +
			'/suites/' +
			workflowRun.check_suite_id;

		console.log( baseUrl );

		const artifacts_list = artifactsResponse.data.artifacts.map(
			( artifact ) => {
				return {
					id: artifact.id,
					name: artifact.name,
					url: baseUrl + '/artifacts/' + artifact.id,
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

		console.dir( artifacts_list );
		console.dir( artifact_url_by_name );

		core.setOutput( 'artifacts_list', artifacts_list );
		core.setOutput( 'artifact_url_by_name', artifact_url_by_name );
	} catch ( error ) {
		core.info( error );
		core.setFailed( error.message );
	}
}

run();
