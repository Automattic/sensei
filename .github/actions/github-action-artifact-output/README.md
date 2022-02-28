# Github Action Artifact Output

This action simply gets all the artifacts detected for the current action run and return it as outputs in different 
forms. The idea is that, after using `@actions/upload-artifact@v2`, you use this action and get the artifacts URLs or list
by using the outputs `artifacts_list` (for a JSON containing an array of artifacts represented by name and url) or `artifact_url_by_name`.