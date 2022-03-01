const core = require( '@actions/core' );
const github = require( '@actions/github' );

async function run() {
	try {
		const GITHUB_TOKEN = core.getInput( 'GITHUB_TOKEN' );
		const octokit = github.getOctokit( GITHUB_TOKEN );

		const { context } = github;

		const { owner, repo } = context.repo;

		const artifactsResponse = await octokit.rest.actions.listWorkflowRunArtifacts(
			{
				owner,
				repo,
				run_id: context.payload.workflow_run.id,
			}
		);

		const baseUrl =
			'https://github.com/' +
			owner +
			'/' +
			repo +
			'/suites/' +
			context.payload.workflow_run.check_suite_id;

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

		core.setOutput( 'artifacts_list', artifacts_list );
		core.setOutput( 'artifact_url_by_name', artifact_url_by_name );

		if ( artifacts_list.length === 1 ) {
			await octokit.rest.repos.createCommitStatus( {
				owner,
				repo,
				sha: context.payload.workflow_run.head_sha,
				state: 'success',
				target_url: artifacts_list[ 0 ].url,
				description: 'Plugin build',
				context: 'Plugin Build',
			} );
		}
	} catch ( error ) {
		core.info( error );
		core.setFailed( error.message );
	}
}

run();
