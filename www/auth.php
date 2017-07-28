<?php

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
		handleAuth
		
		Called after a user clicks the oAuth link to my Freshbook application FreshTiming.
		
		Expects 'code' and 'freshbooks_client_secret' as GET params.
		
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
		
	function handleAuth($clientSecret, $sRefreshToken = null) {	
		$curl = curl_init();
		
		if (!$clientSecret)
			return json_decode(array('err' => "Must pass freshbooks_client_secret (from Freshbooks TimingApp developer page)"));

		$clientID = 'c0386f435a5d1b85dca1c34575ef61cf747f3525c690f87156271fd4f4c9b19e';


		$authCode = array_key_exists('code',$_POST) ? $_POST['code'] : null;
		if ($sRefreshToken && !$authCode) {
			return json_decode(array('err' => "Did not receive auth code from Freshbooks."));
		}
		
		$fields = array( 
					'client_secret'	=> $clientSecret
					, 'client_id'		=> $clientID
					, 'redirect_uri'	=> 'https://freshtiming.combicombi.com/auth.php'

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
		$postFields = encodeArrayForPost($fields);
		//echo "Will post: \n$postFields\n\n"; 
		 
		curl_setopt_array($curl, array(
		  
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
		  ),
		));
		
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
?>
<!DOCTYPE html> 
<html>	
	<head>
		<title>FreshTiming</title>
		<script data-main="app" src="lib/requirejs/require.js"></script>
	</head>
	<body>
		<h2>FreshTiming Authorization</h2>

		<?php 
				if( isset($_POST['freshbooks_client_secret']) ) {
					$resp = handleAuth($_POST['freshbooks_client_secret']);
					//echo print_r($resp,true);
	
					if ($resp["token_type"]) {
		 				echo "<h3>Successful auth.</h3>";
		 				
		 				/*echo '<h3>Attempting  OAuth2 Token Refresh</h3>';
						echo '<p>Using refresh_token: ' . $resp["refresh_token"] . '</p>';				
						$resp2 = handleAuth($resp["refresh_token"]);
						echo print_r($resp2,true);
						*/
						
						$aIdentity = placeFreshbooksCall($resp["access_token"],"auth/api/v1/users/me");
						//echo "<h4>identity</h4>" . print_r($aIdentity,true);
						
						$account_id = $aIdentity['response']['roles'][0]['id'];
						$biz_id = $aIdentity['response']['business_memberships'][0]['business']['id'];
						echo "<p>Freshbooks: Account id: $account_id, Biz id: $biz_id";
						
						//$aSystem = placeFreshbooksCall($resp["access_token"],"accounting/account/$account_id/systems/systems/");
						//echo "<h4>system</h4>" . print_r($aSystem,true);
						
						// get projects
						$aProjects = placeFreshbooksCall($resp["access_token"],"timetracking/business/$biz_id/projects");
						echo "<h4>projects</h4>";// . print_r($aProjects,true);
						$aProjectList  = $aProjects['projects'];
						echo "<ul>\n";
						for ($i = 0; $i < count($aProjectList); $i++) {
							$p = $aProjectList[$i];
							echo "<li>" . $p['title'] . "</li>\n";
						}
						echo "</ul>\n";
					}
				} else {
			?>
				<p>Enter the 'Client Secret' (see <a href = 'https://my.freshbooks.com/#/developer' target="_blank">FreshTiming app</a>)</p>

				<form action='auth.php' method="POST">
					<input type='hidden' name = 'code' value='<?php echo $_GET['code'] ?>' />
					<input type='text' name = 'freshbooks_client_secret' />
					<input type='submit' />
				</form>
			<?php } ?>
				
	</body>
</html>