<?php

if( isset( $_GET[ 'Submit' ] ) ) {
	// Get input
	$id = $_GET[ 'id' ];

	$link = mysqli_connect($_DVWA[ 'db_server' ], $_DVWA[ 'db_user' ], $_DVWA[ 'db_password' ], $_DVWA[ 'db_database' ]);
	// Check database
	$getid  = "SELECT first_name, last_name FROM users WHERE user_id = '$id';";
	$result = mysqli_query($link, $getid ); // Removed 'or die' to suppress mysql errors
//自己新改的
	$row = mysqli_fetch_array($result, MYSQLI_NUM);
	$num_rows = mysqli_num_rows($result);
//自己新改的

	// Get results
	$num = $num_rows; // The '@' character suppresses errors
	if( $num > 0 ) {
		// Feedback for end user
		$html .= '<pre>User ID exists in the database.</pre>';
	}
	else {
		// User wasn't found, so the page wasn't!
		header( $_SERVER[ 'SERVER_PROTOCOL' ] . ' 404 Not Found' );

		// Feedback for end user
		$html .= '<pre>User ID is MISSING from the database.</pre>';
	}

	mysqli_close($link);
}

?>