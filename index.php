<?php

/**
 * =====================
 * CONFIGURATION OPTIONS
 * =====================
 *
 * Config data can be passed directly below, or by creating file(s) containing a php array in the "/data/config/" folder.
 *
 * Open the "/vendor/Codi0/Prototypr/App.php" file for a full list of config and other options that can be set.
 *
**/


//load lib
require_once(__DIR__ . '/vendor/Codi0/Prototypr/App.php');

//init app
return prototypr([

	'config' => [
	
		//App name
		'name' => 'Demo App',
		
		//If not set, $_SERVER vars will be used to construct the base url
		'baseUrl' => '',

		//If not set, HTTP_HOST will be scanned for potential matches (E.g. ^dev.)
		//Supported envs are dev, qa, staging, prod
		'env' => 'dev',

		//To use a server cronjob instead, set this to false and then call this file from your cronjob
		//E.g. "/path/to/index.php -cron -baseUrl={url here}" 
		'webCron' => true,
		
		//Designates a module to function as a theme (set to null to disable theme usage)
		'theme' => 'theme',

	],

	'config.dev' => [
		//overriding config for "dev" environment
	],

	'config.qa' => [
		//overriding config for "qa" environment
	],

	'config.staging' => [
		//overriding config for "staging" environment
	],

	'config.prod' => [
		//overriding config for "prod" environment
	],

]);