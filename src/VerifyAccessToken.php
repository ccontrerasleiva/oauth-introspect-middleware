<?php

namespace Tiandgi\OAuth2\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Tiandgi\OAuth2\Middleware\\Exceptions\InvalidAccessTokenException;
use Tiandgi\OAuth2\Middleware\\Exceptions\InvalidInputException;
use Tiandgi\OAuth2\Middleware\\Exceptions\InvalidEndpointException;

class VerifyAccessToken {
	
	private $client = null;
	
	private function getClient() {
		if ($this->client == null) {
			$this->client = new \GuzzleHttp\Client ();
		}
		
		return $this->client;
	}
	
	public function setClient(\GuzzleHttp\Client $client) {
		$this->client = $client;
	}

	protected function getValidToken($accessToken) {
		$guzzle = $this->getClient ();		
		$response = $guzzle->post ( config ( 'authorizationserver.introspect_url' ), [ 
				'form_params' => [ 
						'token_type_hint' => 'access_token',
						'token' => $accessToken
				],
				'headers' => [ 
						'Authorization' => 'Bearer ' . $this->getAccessToken ()  
				] 
		] );

		return json_decode ( ( string ) $response->getBody (), true );
	}
	
	protected function getAccessToken() {
		$accessToken = Cache::get ( 'accessToken' );
		if (! $accessToken) {
			$guzzle = $this->getClient ();
			$response = $guzzle->post ( config ( 'authorizationserver.token_url' ), [ 
					'form_params' => [ 
							'grant_type' => 'client_credentials',
							'client_id' => config ( 'authorizationserver.client_id' ),
							'client_secret' => config ( 'authorizationserver.client_secret' ),
							'scope' => '' 
					] 
			] );
			
			$result = json_decode ( ( string ) $response->getBody (), true );
			if ($result && isset ( $result ['access_token'] )) {
				$accessToken = $result ['access_token'];
				\Cache::add ( 'accessToken', $accessToken, intVal ( $result ['expires_in'] ) / 60 );
			} else {
				throw new InvalidEndpointException ( "No se recibió access_token" );
			}
		}
		return $accessToken;
	}

	public function handle($request, Closure $next, ...$scopes) {
		$authorization = $request->header ( 'Authorization' );
		if (strlen ( $authorization ) == 0) {
			throw new InvalidInputException ( "Sin header de Autorización" );
		}
		$receivedAccessToken = preg_replace ( '/^Bearer (.*?)$/', '$1', $authorization );
		
		if (strlen ( $receivedAccessToken ) == 0) {
			throw new InvalidInputException ( "Sin token en header de autorización" );
		}

		try {
			$result = $this->getValidToken ( $receivedAccessToken );
			if (! $result ['active']) {
				throw new InvalidAccessTokenException ( "Token no válido!" );
			} else if ($scopes != null) {
				if (! \is_array ( $scopes )) {
					$scopes = [ 
							$scopes 
					];
				}
				$scopesForToken = \explode ( " ", $result ['scope'] );
				
				if ( count($misingScopes = array_diff ( $scopes, $scopesForToken ) ) > 0 ) {
					throw new InvalidAccessTokenException ( "no se encuentran los siguientes scopes obligatorios: " . implode(" ,",$misingScopes) );
				} else {
				}
			}
		} catch ( RequestException $e ) {
			if ($e->hasResponse ()) {
				$result = json_decode ( ( string ) $e->getResponse ()->getBody (), true );
				var_dump($result);exit;

				if (isset ( $result ['error'] )) {
					throw new InvalidAccessTokenException ( $result ['error'] ['title'] ?? "Token no válido!");
				} else {
					throw new InvalidAccessTokenException ( "Token no válido!" );
				}
			} else {
				throw new InvalidAccessTokenException ( $e );
			}
		}
		return $next ( $request );
	}
}
