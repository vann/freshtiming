<?php

class Freshbooks {
	
	
	var $clientID = 'c0386f435a5d1b85dca1c34575ef61cf747f3525c690f87156271fd4f4c9b19e';
	var $redirectURL = 'https://freshtiming.combicombi.com';

	function encodeArrayForPost($fields) {
		$fields_string = "{";
		
		foreach ($fields as $key=>$value) { 
			$fields_string .= "\n\t" 
							  . '"' . $key . '"'
							  . ': '
							  . '"' . $value . '",'; 
		}
		$fields_string = rtrim($fields_string, ',') . "\n}";
		return $fields_string;
	}

	/*
		oAuthFreshbooks
		
		Called after a user clicks the oAuth link to my Freshbook application FreshTiming.
		
		Expects 'code' as GET param.
		
		Returns json of the form:
		
		{
		  "access_token" => "lots_of_letters_and_numbers", // lasts 12 hours
		  "token_type" => "bearer",
		  "expires_in" => 43200,
		  "refresh_token" => "same_as_the_bearer_token", //  lives forever
		  "created_at" => 1472471407
		}
		
		These tokens should be stored to reauth the user automatically... if we were actually tracking users.
	*/
		
	function oAuthFreshbooks($clientSecret, $sRefreshToken = null) {	
		$curl = curl_init();
		
		if (!$clientSecret)
			return json_decode(array('err' => "Must pass freshbooks_client_secret (from Freshbooks TimingApp developer page)"));

		$clientID = 'c0386f435a5d1b85dca1c34575ef61cf747f3525c690f87156271fd4f4c9b19e';


		$authCode = array_key_exists('code',$_GET) ? $_GET['code'] : null;
		if ($sRefreshToken && !$authCode) {
			return json_decode(array('err' => "Did not receive auth code from Freshbooks."));
		}
		
		$fields = array( 
					'client_secret'	=> $clientSecret
					, 'client_id'		=> $this->clientID
					, 'redirect_uri'	=> $this->redirectURL

		);

		if ($sRefreshToken ) {
			// if we're refreshing auth:
			$fields['grant_type'] = 'refresh_token';
			$fields['refresh_token'] = $sRefreshToken;
		} else {
			$fields['grant_type'] = 'authorization_code';
			$fields['code']	= $authCode;
		}
		

		//url-ify the data for the POST
		$postFields = $this->encodeArrayForPost($fields);
		 
		$aCurlOpts = array(
		  
		  CURLOPT_URL => "https://api.freshbooks.com/auth/oauth/token",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $postFields,
		  
		  CURLOPT_HTTPHEADER => array(
		    "Api-version: alpha",
		    "Cache-Control: no-cache",
		    "Content-Type: application/json"
		  ));

		//logthis('oAuthFreshbooks():aCurlOpts',$aCurlOpts);
		
		curl_setopt_array($curl, $aCurlOpts);

		
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
			
		if ($err) {
			$jsonerr =  json_decode ( $err ,true);
			return $jsonerr;
		} 
		else {
			//$jsonResponse =  json_decode ( $response );
			//return $jsonResponse;
			return json_decode($response,true);
		}
	
	}
	

	function doFreshbooksScript($resp) {
		
		if (!$resp["token_type"] || !$resp["access_token"]) { 
			echo 'missing token_type or access_token from resp.';
			return false;
		}
	 	
	 	echo "<h3>Successfully oAuth'd into Freshbooks app FreshTiming</h3>";
	 		
 		/*echo '<h3>Attempting  OAuth2 Token Refresh</h3>';
		echo '<p>Using refresh_token: ' . $resp["refresh_token"] . '</p>';				
		$resp2 = oAuthFreshbooks($resp["refresh_token"]);
		echo print_r($resp2,true);
		*/
		
		$aIdentity = $this->placeFreshbooksCall($resp["access_token"],"auth/api/v1/users/me");
		//echo "<h4>identity</h4><pre>" . print_r($aIdentity,true) . "</pre>";
		
		$email = $aIdentity['response']['email'];
		$account_id = $aIdentity['response']['roles'][0]['id'];
		$biz_id = $aIdentity['response']['business_memberships'][0]['business']['id'];
		echo "<p>Freshbooks info for $email</p><p>Account id: $account_id, Biz id: $biz_id</p>";
		
		//$aSystem = placeFreshbooksCall($resp["access_token"],"accounting/account/$account_id/systems/systems/");
		//echo "<h4>system</h4>" . print_r($aSystem,true);
		
		// get projects
		$aProjects = $this->placeFreshbooksCall($resp["access_token"],"timetracking/business/$biz_id/projects");
		echo "<h4>projects</h4>";// . print_r($aProjects,true);
		$aProjectList  = $aProjects['projects'];
		echo "<ul>\n";
		for ($i = 0; $i < count($aProjectList); $i++) {
			$p = $aProjectList[$i];
			echo "<li>" . $p['title'] . "</li>\n";
		}
		echo "</ul>\n";
	}
	
	
	/*
	
		See Freshbooks API docs here for paths: https://www.freshbooks.com/api/start
		
		Currently only does GETs
	*/
	function placeFreshbooksCall($bearerToken, $path) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  
		  CURLOPT_URL => "https://api.freshbooks.com/" . $path,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  
		  CURLOPT_HTTPHEADER => array(
		    "Api-version: alpha",
		    "Authorization: Bearer $bearerToken",
		    "Content-Type: application/json"
		  ),
		));
		
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
			
		if ($err) {
			$jsonerr = json_decode ( $err,true );
			return $jsonerr;
		} 
		else {
			return json_decode($response,true);
		}
	}
	
	
	function logthis($s, $obj = '') {
		
		$msg = $s;
		if (is_array($obj)) {
			$obj = print_r($obj,true);
		}
		
		$msg .=  $obj ? ': ' . $obj : '';
		echo "<pre>$msg</pre>\n";
	}
		
	function getConfig() {
	
		$config = json_decode(file_get_contents("../config.json"), true);
		//logthis ('config',$config);
		return $config;
	}
	
	function getClientSecret() {
		
		$config = $this->getConfig();
		$freshbooks_client_secret = array_key_exists('freshbooks_client_secret',$config) ? $config['freshbooks_client_secret'] : 'none';
		$freshbooks_client_secret = isset($_POST['freshbooks_client_secret']) ? $_POST['freshbooks_client_secret'] : $freshbooks_client_secret;
		return $freshbooks_client_secret;
	}
	
	public function entryPoint() {
	
		if ($_GET) {
			//logthis("_GET",$_GET);
			$freshbooks_client_secret = $this->getClientSecret();
			//logthis( "entryPoint():client secret" , $freshbooks_client_secret);
			
			if (!$freshbooks_client_secret) {
				$this->logthis("entryPoint():No client secret set in 'config.json' file.");
				return false;
			}
			$resp = $this->oAuthFreshbooks($freshbooks_client_secret);
			if (array_key_exists('error',$resp)) {
				$this->logthis ('entryPoint():Got Freshbooks Auth error. $response', $resp);
				return false;
			}
			return $this->doFreshbooksScript($resp);
		} else {
			// nothing submitted. show  link to oAuth to freshbooks app:
			

			echo "<a href='https://my.freshbooks.com/service/auth/oauth/authorize?"
				. "client_id=" . $this->clientID 
				. "&response_type=code&"
				. "redirect_uri=" . $this->redirectURL
				. "'>oAuth Freshbooks Cloud</a>";
		}
	}
}

?>
<!DOCTYPE html> 
<html>	
	<head>
		<title>FreshTiming</title>
		<script data-main="app" src="lib/requirejs/require.js"></script>
	</head>
	<body>

		<h2>FreshTiming</h2>
		<p>Tool to take an export of TimingApp.com tasks and import them into Freshbooks Cloud Accounting. </p>
		

		<?php 
			$fb = new Freshbooks();
			
			$fb->entryPoint();
		?>
	</body>
</html>