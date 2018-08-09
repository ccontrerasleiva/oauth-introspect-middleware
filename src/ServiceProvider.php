<?php
namespace Tiandgi\OAuth2\Middleware;

class ServiceProvider extends \Illuminate\Support\ServiceProvider{
	
	public function boot() {
		$this->publishes([
				__DIR__ .'/../config/authorizationserver.php' => config_path('authorizationserver.php'),
		]);	
		$this->loadRoutesFrom(__DIR__ .'/../routes/routes.php');
		
	}
	public function register() {
		
	}
	
}
