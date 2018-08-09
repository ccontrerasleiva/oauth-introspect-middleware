<?php

Route::get('/redirect', function (Request $request) {
	$query = http_build_query([
			'client_id' => config('authorizationserver.client_id'),
			'redirect_uri' => config('authorizationserver.redirect_url'),
			'response_type' => 'token',
			'scope' => '',
	]);

	return redirect(config('authorizationserver.authorization_url') . '?' . $query);
});
