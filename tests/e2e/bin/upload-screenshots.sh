if [[ ${RUN_E2E} == 1 && $(ls -A $TRAVIS_BUILD_DIR/tests/e2e/screenshots) ]]; then

	if [[ -z "${ARTIFACTS_KEY}" ]]; then
		echo "Screenshots were not uploaded. Please run the e2e tests locally to see failures."
	else
		# Documentation can be found here: https://github.com/travis-ci/artifacts
		curl -sL https://raw.githubusercontent.com/travis-ci/artifacts/master/install | bash
		artifacts upload
	fi
fi
