/**
 * External dependencies
 */
import axios from 'axios';

const instance = axios.create( {
	withCredentials: true,
	baseURL: '/',
	headers: {
		Accept: 'application/json, */*;q=0.1',
	},
} );

async function getNonce( axiosInstance ) {
	if ( axiosInstance.nonce ) return axiosInstance.nonce;

	axiosInstance.nonce = (
		await axios.get( '/wp-admin/admin-ajax.php?action=rest-nonce', {
			baseURL: '/',
		} )
	 )?.data;

	return axiosInstance.nonce;
}

function shouldUseNonce( request ) {
	return request.method.toUpperCase() !== 'GET';
}

const getOnce = async ( request ) => {
	const params = { ...request.params };

	if ( shouldUseNonce( request ) ) {
		params._wpnonce = await getNonce( request );
	}

	request.params = {
		...params,
		rest_route: request.restRoute ? request.restRoute : null,
	};
	return request;
};

instance.interceptors.request.use( getOnce );

export default instance;
