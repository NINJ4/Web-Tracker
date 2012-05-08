<?php
/********************************************************************************************************************************
 *
 * Player/Tracker Web Interface and Lookups
 * Author: NINJ4
 * More Info:	http://dev.bukkit.org/server-mods/player-tracker/
 					AND/OR
 				https://github.com/NINJ4/Player-Tracker	
 				
 * Description: Visualize and perform lookups on your mySQL Player/Tracker database from the web.
 * License for this file: Public Domain
 *
 * Configuration:
 *		Fill out up to the words "END config" with the appropriate data (comments provided)
*********************************************************************************************************************************/
global $server,$database,$user,$password,$table,$untraceable;
$server = "localhost";			// server where the mySQL database is located.  If you aren't sure, don't change this.
$database = "minecraft";		// database containing the player-tracker table.
$user = "root";					// mysql user with permissions to access/modify this database.
$password = "root";				// password for the mysql user
$table = "player-tracker";		// if you changed the name of the player-tracker table, make sure you set your custom name here as well.

// END config
///////////////////////////////////////////////////
// Functions

function Connect() {
	global $server,$database,$user,$password;
	// Connect
	if ( $connection = mysql_connect( $server, $user, $password ) ) {
		if ( mysql_select_db( $database ) ) 
			return $connection;
		else
			return false;
	}
	return false;
}
function expandMap( $map, $spent, $checknames ) {
	global $table;
	$alreadyNodes['name'] = array();
	$alreadyNodes['ip'] = $spent['ip'];
	$primaryLinks = $spent['ip'];
	$primaryNodes = $checknames;
	
	if ( ( !$checknames ) || ( !$spent ) ) 
		return $map;
	
	while ( count($checknames) > 0 ) {
		$thisName = array_pop( $checknames );
		if ( in_array( $thisName, $spent['name'] ) )
			continue;
		
		$spent['name'][] = $thisName;
		
		$sql = "SELECT `ip`
				FROM `$table`
				WHERE `accountname` LIKE '$thisName'";
		$result = mysql_query( $sql );
		
		$thisNameIPs = array();
		while ( $row = mysql_fetch_assoc( $result ) ) {
			$thisNameIPs[] = $row['ip'];
			if ( in_array( $row['ip'], $spent['ip'] ) )
				continue;
			
			$thisIP = $row['ip'];
			$spent['ip'][] = $thisIP;
			
				// add an IP map node?
			if ( !in_array( $thisIP, $alreadyNodes['ip'] ) ) {
				$map[] = array(
					'name'		=>	$thisIP,
					'id'		=>	$thisIP,
					'data'		=>	array(
										'$dim'		=>	7,
										'$type'		=>	"circle",
										'$color'	=>	"#71A87F",
									),
				);
				$alreadyNodes['ip'][] = $thisIP;
			}
			
				// find secondary linked accounts:
			$sql = "SELECT `accountname`
					FROM `$table`
					WHERE `ip` LIKE '$thisIP'";
			$result2 = mysql_query( $sql );		
			while ( $row2 = mysql_fetch_assoc( $result2 ) ) {
				if ( !in_array( $row2['accountname'], $spent['name'] ) )
					$checknames[] = $row2['accountname'];
			}
		}
			// add a NAME map node?
		if ( !in_array( $thisName, $alreadyNodes['name'] ) ) {
			$temp = array(
				'name'			=>	$thisName,
				'id'			=>	$thisName,
				'data'			=>	array(
										'$dim'		=>	( ( in_array( $thisName, $primaryNodes) ) ? 9 : 7 ),
										'$type'		=>	"square",
										'$color'	=>	( ( in_array( $thisName, $primaryNodes) ) ? "#0800FF" : "#858EC7" ),
									),
			);
			foreach ( $thisNameIPs as $ip ) {
				$temp['adjacencies'][] = array(
					'nodeTo'	=>	$ip,
					'nodeFrom'	=>	$thisName,
					'data'		=>	array(
										'$color'	=> ( ( in_array($ip, $primaryLinks) ) ? "#000000" : "#777777" ),
									),
				);
			}
			$alreadyNodes['name'][] = $thisName;
			$map[] = $temp;
		}
	}
	return $map;
}

// END functions
///////////////////////////////////////////////////
// Script
$path = substr( __FILE__, 0, strrpos( __FILE__, "/" ) );

////////////// Requested data:
if ( $_POST['target'] != null ) {
	if ( ( $conn = Connect() ) == false ) {
		echo json_encode( array( "success" => false, "head" => "Error", "body" => "Unable to connect to mySQL database!" ) );
		exit();
	}
	
	$target = mysql_real_escape_string( $_POST['target'] );
	if ( strlen( $target ) < 1 ) {
		echo json_encode( array( "success" => false, "head" => "Error", "body" => "Invalid target!" ) );
		exit();
	}
	$output = array( 
			'success'		=> true,
			'wildcard'		=> false,
			'target'		=> $target,
	);
		
	$sql = "SELECT `accountname`,`ip`,`time`
			FROM `$table`
			WHERE LOWER(`accountname`) LIKE LOWER('$target')
			ORDER BY `time` DESC";
	$result = mysql_query( $sql );
	
	if ( !mysql_num_rows( $result ) ) {
			// try again, this time with wildcards...
		
		$sql = "SELECT DISTINCT `accountname`
				FROM `$table`
				WHERE LOWER(`accountname`) LIKE LOWER('%$target%')";
		$result = mysql_query( $sql );
		
		if ( !mysql_num_rows( $result ) ) {
		
				// ok, this is a really bad search, no matches...
			echo json_encode( array( "success" => false, "head" => "No Matches Found", "body" => "Unable to find a player matching <b>$target</b>!" ) );
			exit();
		}
		else if ( mysql_num_rows( $result ) > 1 ) {
				// we found some wildcards, but more than we can handle.
			$output = array(
				'success'	=> false,
				'head'		=> "Ambiguous Search",
				'body'		=> "Your search has matched too many players! Please select the one you intended:<br />\n",
			);
			while ( $row = mysql_fetch_assoc( $result ) ) {
				$output['body'] .=	"<a href='#newreport' class='GUL_gen' value='". 
									$row['accountname'] ."'>". $row['accountname'] ."<br />\n";
			}
			echo json_encode( $output );
			exit();
		}
		else {
			$target_old = $target;
			$row = mysql_fetch_assoc( $result );
			$target = $row['accountname'];
			$output['wildcard'] = true;
			$output['target'] = $target;
			
			
			$sql = "SELECT `ip`,`time`
					FROM `$table`
					WHERE LOWER(`accountname`) LIKE LOWER('$target')
					ORDER BY `time` DESC";
			$result = mysql_query( $sql );
		}
	}
		
	$i = 0;
	$mainpoint = array(
		'name'			=>	$target,
		'id'			=>	$target,
		'data'			=>	array(
								'$dim'		=>	12,
								'$type'		=>	"star",
								'$color'	=>	"#FF0000",
							),
	);
	
		// now that we've got the proper accountname, let's start scanning IPs.
	while ( $row = mysql_fetch_assoc( $result ) ) {					
		$ip = $row['ip'];
		
			// generate some data for the map
		$mainpoint['adjacencies'][] = array(
				'nodeTo'	=>	$ip,
				'nodeFrom'	=>	$target,
				'data'		=>	array(
									'$color'	=> "#000000",
								),
		);
		$output['map'][] = array(
			'name'		=>	$ip,
			'id'		=>	$ip,
			'data'		=>	array(
								'$dim'		=>	9,
								'$type'		=>	"circle",
								'$color'	=>	"#00FF40",
							),
		);
		
		if ( $i == 0 ) {
			$output['lastip'] = $ip;
			$spent['name'][] = $row['accountname'];
			$spent['name'][] = $target;
			$i++;
			
		}
		
		$output['body'][$ip]['total'] = 0;
		$output['body'][$ip]['time'] = $row['time'];
		
		
		$sql = "SELECT `accountname`
				FROM `$table`
				WHERE `ip` LIKE '$ip'
				AND LOWER(`accountname`) NOT LIKE LOWER('$target')";
		$result2 = mysql_query( $sql );
		
		
		if ( mysql_num_rows( $result2 ) > 0 ) {
			$accountsArray = array();
			while ( $row2 = mysql_fetch_assoc( $result2 ) ) {
				$output['body'][$ip]['total']++;
				$accountsArray[] = $row2['accountname'];
				$checknames[] = $row2['accountname'];
			}
		
			$output['body'][$ip]['matches'] = $accountsArray;
		}
	}
	$output['map'] = expandMap( $output['map'], $spent, $checknames );
	$output['map'][] = $mainpoint;
	echo json_encode( $output );
	exit();
}
////////////// Loaded page:
else {
	?>
	<html>
		<head>
			<title>Player/Tracker Web Lookup</title>
			<link href="style.css" rel="stylesheet" type="text/css" />
			<script type="text/JavaScript" src="lib/jit.js"></script> 
			<script type="text/JavaScript" src="lib/jquery.js"></script> 
			<script type="text/JavaScript" src="tracker.js"></script> 
		</head>
		<body>
			<div class="panel">
			
				<center>
				<h1>Player/Tracker</h1>
				<form name="GULform" id="GULform" method="post">
					Alias: <input id="GULtarget" name="target" maxlength="32" type="text"> <input value="Lookup Player" class="button" type="submit">
				</form><p></p>
				<p></p>
				<div id="GULarea">
						<dl class="codebox">
						<dt>Command Output: <a href="javascript:void(0)" onclick="selectCode(this); return false;">Select all</a>
						<span id="GULloading" style="display: none;">Loading...</span>
						<a href="javascript:void(0)" id="GULclear" style="float: right;">Clear</a>
						</dt><dd><code id="GULscroll">
						<span id="GULout"></span>
						</code>
				</center>
				<div id="playerweb" style="display: none;"></div>
				<span id="log" style="display: none;"></span>
			</div>
		</body>
	</html>
	<?php
}

?>
