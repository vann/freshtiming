<?php

    $msg = '';
    
    error_log('upload.php');
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	   	$target_dir = "uploads/";
		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
		$uploadOk = true;
		$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
		// Check if file already exists
		if (file_exists($target_file)) {
		    $msg = "Sorry, file already exists.";
		    $uploadOk = false;
		    $http_code = 400;
		}
		// Check file size
		if ($_FILES["fileToUpload"]["size"] > 500000) {
		    $msg = "Sorry, your file is too large.";
		    $uploadOk = false;
		    $http_code = 413;

		}
		// Allow certain file formats
		if($imageFileType != "csv" ) {
		    $msg = "Sorry, only CSV files are allowed.";
		    $uploadOk = false;
		    $http_code = 415;

		}
		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk !== false) {
    		// if everything is ok, try to upload file
		    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
		        $msg = "'". basename( $_FILES["fileToUpload"]["name"]). "' uploaded";
		        $http_code = 201;
		        
		    } else {
		        $msg = "Sorry, there was an error uploading your file.";
		        $http_code = 500;
		    }
		}
	} else {
	    $msg = "Must POST .csv file. This script returns an HTTP response code and text/json body";    
        $http_code = 405;
	}
	
	$resp = '{}';
	$len = 0;
	if ($msg) {
        $resp =  json_encode(array('message'=>$msg));
        $len = strlen($resp);
    }
    
	if (!headers_sent()) {
    	if (!http_response_code($http_code))
    	    error_log("freshtimgin:upload.php: couldn't set http response code to $http_code");
    	header("Content-Length: $len");
        header("Content-Type: text/json");
	}
	else {
	    error_log("freshtiming:upload.php: headers already sent?");
	}
    echo $resp;
?>