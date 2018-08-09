<?php

return [
		'authorization_url' => env('SERVER_AUTHORIZATION_URL', false),
		'redirect_url' => env('SERVER_REDIRECT_URL', false),
		'token_url' => env('SERVER_TOKEN_URL', false),
		'introspect_url' => env('SERVER_INTROSPECT_URL', false),
		'client_id' => env('SERVER_CLIENT_ID', false),
		'client_secret' => env('SERVER_CLIENT_SECRET', false)
];

