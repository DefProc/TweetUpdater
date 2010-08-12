<?php
/*
Plugin Name: TweetUpdater
Description: WordPress plugin to update Twitter status when you create or publish a post.
Version: 3.x
Author: Patrick Fenner, Jordan Trask "@comm"
Author URI: http://def-proc.co.uk/Projects/TweetUpdater
Based on TwitterUpdater, version 1.0 by Victoria Chan: http://blog.victoriac.net/?p=87

*/

require_once('twitteroauth.php');
require_once('tweetupdater_manager.php');



	/*** Twitter OAuth Functions ***/



/* Get Request Tokens */

function tweet_updater_register($tokens)
{
	// Build TwitterOAuth object with client credentials. 
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret']);

	// Get the request tokens
	$request = $connection->getRequestToken();
	
	// Retrive tokens from request and store in array
	$tokens['request_key'] = $request["oauth_token"];
	$tokens['request_secret'] = $request["oauth_token_secret"];
	
	// Generate a request link and output it
	$tokens['request_link'] = $connection->getAuthorizeURL($request);
	
	return $tokens;
}

/* Get Access Tokens */

function tweet_updater_authorise($tokens)
{	
	// Initiate a new TwitterOAuth object. Provide the request token and request token secret
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret'], $tokens['request_key'], $tokens['request_secret']);
	
	// Get the access tokens
	$request = $connection->getAccessToken();
	
	/*
	 ------------------------------------------------------------------------------------------
	 *** A failed request (e.g. not authorised by user in twitter) is not handled at all  	***
	 *** and outputs an error array from getAccessToken() 					***
	 ------------------------------------------------------------------------------------------
	 */
	 
	// Retrieve access token from request:
	$tokens['access_key'] = $request['oauth_token'];
	$tokens['access_secret'] = $request['oauth_token_secret'];

	return $tokens;
}

/* Validate Access */

function tweet_updater_verify($tokens)
{
	// Initiate TwitterOAuth using access tokens
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret'], $tokens['access_key'], $tokens['access_secret']);
	
	$result = $connection->get('account/verify_credentials');
	
	$verify = array(
		'exit_code' => '',
		'user_name' => '',
			);
	
	if ($result->id) 
	{ 
		$verify['exit_code'] = "1"; 
		$verify['user_name'] = $result->screen_name;
	}
	else 		 
	{ 
		$verify['exit_code'] = "3"; 
	}

	return $verify;
}

/* Send a Tweet */

function tweet_updater_update_status($tokens, $tweet)
{
	// Initiate a TwitterOAuth with access tokens
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret'], $tokens['access_key'], $tokens['access_secret']);
	
	// Post an update to Twitter via your application:
	$result = $connection->post('statuses/update', array('status' => $tweet));

	return $result;
}



	/*** WordPress Hooks ***/


/* Action for when a post is published/edited (but not just saved) */
//	add_action( 'publish_post', 'vc_twit_current', 1, 1 );

/* Action for when a future post is published */
//	add_action( 'future_to_publish', 'vc_twit_future', 1, 1 );

/* Add the admin options page */
	add_action( 'admin_menu', 'tweet_updater_admin_add_page' );

/* Intialise on first activation */
	register_activation_hook( __FILE__, 'tweet_updater_activate' );

?>
