<?php
/*
Plugin Name: TwitterUpdater
Description: WordPress plugin to update Twitter status when you create or publish a post.
Version: 2.11
Author: Ingo "Ingoal" Hildebrandt, Jordan Trask "@comm", Patrick Fenner
Author URI: http://www.geektank.net/twitter-updater/
Based on Version 1.0 by Victoria Chan: http://blog.victoriac.net/?p=87

*/


function vc_doTwitterAPIPost($twit, $twitterURI) {
	$host = 'twitter.com';
	$port = 80;
	$fp = fsockopen($host, $port, $err_num, $err_msg, 10);

	//check if user login details have been entered on admin page
	$thisLoginDetails = get_option('twitterlogin_encrypted');

	if($thisLoginDetails != '')
	{
		if (!$fp) {
			echo "$err_msg ($err_num)<br>\n";
		} else {	
			echo $string;
			fputs($fp, "POST $twitterURI HTTP/1.1\r\n");
			fputs($fp, "Authorization: Basic ".$thisLoginDetails."\r\n");
			fputs($fp, "User-Agent: ".$agent."\n");
                        fputs($fp, "Host: $host\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
			fputs($fp, "Content-length: ".strlen($twit)."\n");
			fputs($fp, "Connection: close\n\n");
			fputs($fp, $twit);
			for ($i = 1; $i < 10; $i++){$reply = fgets($fp, 256);}
			fclose($fp);
		}
		return $response;
	} else {
		//user has not entered details.. Do nothing? Don't wanna mess up the post saving..
		return '';
	}
}

function vc_twit($post_ID)  {
   $twitterURI = "/statuses/update.xml?source=web";
   $getthisposttitle = get_post($post_ID);	// edited by Marco Luthe
   $thisposttitle = $getthisposttitle->post_title; // edited by Marco Luthe
   $thispostlink = get_permalink($post_ID);
   $sentence = "";
 
   if(wp_is_post_revision($post_ID)) {
     return $post_ID;
   }

   else if (wp_is_post_autosave($post_ID)) {
     return $post_ID;
   }

   else {
	//is new post
	if($_POST['original_post_status'] == 'draft') {  // edited by Eric Lee - 'prev_status' now 'original_post_status' in 2.7
		if($_POST['publish'] == 'Publish'){
			// publish new post
			if(get_option('newpost-published-update') == '1'){
                                $sentence = get_option('newpost-published-text');
	                        $tinyurl = get_tinyurl(get_option('short-method'),get_option('url-method'),$thispostlink,$post_ID);
				$sentence = str_replace ( '#title#', $thisposttitle, $sentence);
				$sentence = str_replace ( '#url#', $tinyurl, $sentence);
			}
		}
	} else if ($_POST['original_post_status'] == 'publish') {  // edited by Eric Lee - 'prev_status' now 'original_post_status' in 2.7
		// is old post
		if(get_option('oldpost-edited-update') == '1') {
			$sentence = get_option('oldpost-edited-text');
			// new fix for scheduled posts (thanks uniqueculture)
			if (strlen(trim($thisposttitle)) == 0) {
				$post = get_post($post_ID);
				if ($post) {
					$thisposttitle = $post->post_title;
				}
			}

			$tinyurl = get_tinyurl(get_option('short-method'),get_option('url-method'),$thispostlink,$post_ID);
			$sentence = str_replace ( '#title#', $thisposttitle, $sentence);
			$sentence = str_replace ( '#url#', $tinyurl, $sentence);
		}
	} else {}
      

   } //else  


	if($sentence != ""){
    		$urlstatus = 'status='.$sentence;
		$status = utf8_encode($urlstatus);
		$sendToTwitter = vc_doTwitterAPIPost($status, $twitterURI);
	}
   return $post_ID;
}

// vc_twit2
// copied from vc_twit and  adjusted to have a function that works with the future_to_publish hook
// added by Marco Luthe
function vc_twit2($post_ID)  {
   $twitterURI = "/statuses/update.xml?source=web";
   $getthisposttitle = get_post($post_ID);	// edited by Marco Luthe
   $thisposttitle = $getthisposttitle->post_title;	//edited by Marco Luthe
   $thispostlink = get_permalink($post_ID);
   $sentence = "";
 
	if(get_option('newpost-published-update') == '1'){
		$sentence = get_option('newpost-published-text');
                $tinyurl = get_tinyurl(get_option('short-method'),get_option('url-method'),$thispostlink,$post_ID);
		$sentence = str_replace ( '#title#', $thisposttitle, $sentence);
		$sentence = str_replace ( '#url#', $tinyurl, $sentence);
	}

	if($sentence != ""){
		$urlstatus = 'status='.$sentence;
		$status = utf8_encode($urlstatus);
		$sendToTwitter = vc_doTwitterAPIPost($status, $twitterURI);
	}
   return $post_ID;
}


function get_tinyurl($shortmethod,$urlmethod,$link,$post_ID) {
      update_option('tu_last',$link);
      if ($urlmethod == '1') { 
              $url = "http://zz.gd/api-create.php?url=".$link;
              if ($shortmethod == '1') {
                      $turl = file_get_contents_curl($url);
              } else {
                      $turl = file_get_contents($url);            
              }
      }
      else if ($urlmethod == '2') {
              $url = "http://tinyurl.com/api-create.php?url=".$link;
              if ($shortmethod == '1') {
                      $turl = file_get_contents_curl($url);
              } else {      
                      $turl = file_get_contents($url);
              }
      }
      else if ($urlmethod == '3') {
              $url = $link;
              if ($shortmethod == '1') {
                      $turl = make_bitly_url($url,get_option('tu_bitly_username'),get_option('tu_bitly_appkey'),'1');
              } else {
                      $turl = make_bitly_url($url,get_option('tu_bitly_username'),get_option('tu_bitly_appkey'),'0');
              }
      }
      else if ($urlmethod == '4') { // Added support for la_petite_url shortener plugin
              if(function_exists('get_la_petite_url_permalink')) {
                      $turl = get_la_petite_url_permalink($post_ID); // return short link from la_petite_url
              } else {
                      // Don't want things to fail completely if la_petite_url gets deactivated, so reset to default and continue
                      update_option('url-method', '2');
                      $url = "http://tinyurl.com/api-create.php?url=".$link;
                      if ($shortmethod == '1') {
                             $turl = file_get_contents_curl($url);
                      } else {      
                             $turl = file_get_contents($url);
                      }
              }
      }
      else if ($urlmethod == '5') { // Added selection of full permalink
              $turl = get_permalink($post_ID); 
      }
      return($turl);
}
                                        


function file_get_contents_curl($url) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_URL, $url);
      $data = curl_exec($ch);
      curl_close($ch);
      return $data;
}

//bitly support

function make_bitly_url($url,$login,$appkey,$curl) {
      $bitly = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&format=json&history=1&longUrl='.urlencode($url);
      //get the url
      if ($curl == '1') { $response = file_get_contents_curl($bitly); }
      else { $response = file_get_contents($bitly); }
	
      $json = @json_decode($response,true);
      $shorturl = $json['data']['url'];
      return $shorturl;
}


// ADMIN PANEL - under Manage menu
function vc_addTwitterAdminPages() {
      if (function_exists('add_options_page')) {
              add_options_page('Twitter Updater', 'Twitter Updater', 8, __FILE__, 'vc_Twitter_manage_page');
      }
 }

function vc_Twitter_manage_page() {
    include(dirname(__FILE__).'/twitter_updater_manage.php');
}

//HOOKIES
// add_action ('save_post', 'vc_twit');	would be on every save, even if it is a future scheduled post?
//added "1,1" parameters to the end. in 2.7 this was no longer firing without these priority & no. of parameter parameters - edited by Eric Lee
add_action('publish_post', 'vc_twit',1,1);	// should be fired only if a post is actually published/edited, but not just saved - edited by Marco Luthe
add_action('future_to_publish', 'vc_twit2',1,1);	// should be fired only if a future post is actually published - edited by Marco Luthe
add_action('admin_menu', 'vc_addTwitterAdminPages');
