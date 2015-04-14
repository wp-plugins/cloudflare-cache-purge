<?php
/*

Description:  API Integration with CloudFlare to purge your cache
Author:       Bryan Shanaver @ fiftyandfifty.org
Author URI:   https://www.fiftyandfifty.org/
Contributors: shanaver

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
Neither the name of Alex Moss or pleer nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

class CCPURGE_API {

	var $ccpurge_endpoint 		  	= "https://www.cloudflare.com/api_json.html";
	var $ccpurge_methods 			= array();
	var $ccpurge_options 		    = array();
	var $ccpurge_url     			= '';
	var $ccpurge_suppress_debug 	= false;

	function __construct() {
		$this->ccpurge_options = get_option('ccpurge_options');
		isset($this->ccpurge_options['auto_purge']) || $this->ccpurge_options['auto_purge'] = false;
		if( empty($this->ccpurge_options['account']) ){
			$this->ccpurge_options['account'] = $this->get_wordpress_domain();
			update_option('ccpurge_options', $this->ccpurge_options);
		}
		$this->build_api_calls();
		$this->wordpress_upload_dir = wp_upload_dir();
	}

	function build_api_calls(){
			$tkn 		= isset($this->ccpurge_options['token']) ? $this->ccpurge_options['token'] : null;
			$email	= isset($this->ccpurge_options['email']) ? $this->ccpurge_options['email'] : null;
			$z 			= isset($this->ccpurge_options['account']) ? $this->ccpurge_options['account'] : null;
			$this->api_methods = array(
				"purge_all"		=>  array(
					'a' 		=> 'fpurge_ts',
					'tkn' 	=> $tkn,
					'email' => $email,
					'z' 		=> $z,
					'v' 		=> '1'
				),
				"purge_url"		=>  array(
					'a' 		=> 'zone_file_purge',
					'tkn' 	=> $tkn,
					'email' => $email,
					'z' 		=> $z
				)
			);
	}

	function return_json_success($data='') {
		print json_encode( array("success" => 'true', "data" => $data) );
	}

	function return_json_error($error='') {
		print json_encode( array("success" => 'false', 'error' => array("message" => $error)) );
	}

	function make_api_request($api_method, $extra_post_variables = null){
		$headers = '';

		if( $this->ccpurge_options['token'] == '' || $this->ccpurge_options['email'] == '' || $this->ccpurge_options['account'] == ''){
			ccpurge_transaction_logging('Purge call failed due to missing config options: email=' . $this->ccpurge_options['email'] . ' & token=' . substr($this->ccpurge_options['token'], 0, 10) . '[...]' . ' & domain=' . ( isset($this->ccpurge_options['account']) ? $this->ccpurge_options['account'] : '')  );
			return;
		}

		if( is_array($extra_post_variables) ){
			$post_variables =  array_merge( $this->api_methods[$api_method], $extra_post_variables );
		}
		else{
			$post_variables = $this->api_methods[$api_method];
		}

		if( isset($this->ccpurge_options['console_calls']) && !$this->ccpurge_suppress_debug ){
			ccpurge_transaction_logging("\n" . "api url: " . $this->ccpurge_endpoint . "\n" . "api post args: " . print_r($post_variables, true) . "\n", 'print_debug');
		}

		$results = wp_remote_post($this->ccpurge_endpoint, array( 'headers' => $headers, 'body' => $post_variables) );

		if( isset($this->ccpurge_options['console_details']) && !$this->ccpurge_suppress_debug ){
			print_r($results);
		}

		if( is_wp_error( $results ) ){
			ccpurge_transaction_logging(print_r($results->get_error_message(), true), 'Wordpress Error');
		}

		if($results['response']['code'] != '200'){
			ccpurge_transaction_logging(print_r($results, true), 'error');
		}

		return json_decode($results['body']);
	}

	function purge_entire_cache(){
		$results = $this->make_api_request('purge_all');
		if( $results->result == 'success' ){
			ccpurge_transaction_logging("Purged Entire Cache for domain: " . $this->ccpurge_options['account'] . " | purge timestamp: " . $results->response->fpurge_ts . " | results: " . $results->result . " | msg: " . $results->msg . " | attributes: " . print_r($results->attributes, true) );
			print($results->result);
		}
		else{
			ccpurge_transaction_logging("Purge Entire Cache for domain: " . $this->ccpurge_options['account'] . " Error: " . $results->msg, 'error');
			print($results->msg);
		}
		die();
	}

	function purge_url($url){
		$this->purge_url_after_post_save($url, true);
	}

	function purge_url_after_post_save($url, $ajax=false){
		$results = $this->make_api_request('purge_url', array('url' => $url));
		if($ajax){$auto="Manual";}
		else{$auto="Automatic";}
		if( $results->result == 'success' ){
			ccpurge_transaction_logging("{$auto} Purge URL Cache for: " . $url . " | domain: " . $this->ccpurge_options['account'] . " | purge timestamp: " . $results->response->fpurge_ts . " | results: " . $results->result . " | msg: " . $results->msg . " | attributes: " . print_r($results->attributes, true) );
			if($ajax){print($results->result);}
		}
		else{
			ccpurge_transaction_logging("{$auto} Purge URL Cache for: " . $url . " Error: " . $results->msg, 'error');
			if($ajax){print($results->msg);}
		}
		if($ajax){die();}
	}

	function get_wordpress_domain(){
		$domain = preg_replace('/http:\/\//', '', get_home_url() );
		$domain = preg_replace('/www./', '', $domain  );
		return $domain;
	}


}
