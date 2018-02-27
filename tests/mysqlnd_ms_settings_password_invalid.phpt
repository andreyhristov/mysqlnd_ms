--TEST--
Invalid password (number and array)
--SKIPIF--
<?php
require_once('skipif.inc');
require_once("connect.inc");

if (!$passwd)
	die("SKIP No password given, can't test for user");

_skipif_check_extensions(array("mysqli"));
_skipif_connect($emulated_master_host_only, $user, $passwd, $db, $emulated_master_port, $emulated_master_socket);
_skipif_connect($emulated_slave_host_only, $user, $passwd, $db, $emulated_slave_port, $emulated_slave_socket);


$settings = array(
	"myapp" => array(
		'master' => array(
			  array(
				'host' 		=> $emulated_master_host_only,
				'port' 		=> $emulated_master_port,
				'socket' 	=> $emulated_master_socket,
				'db'		=> $db,
				'user'		=> $user,
				'password'	=> array(-1),
			  ),
		),
		'slave' => array(
			array(
			  'host' 	=> $emulated_slave_host_only,
			  'port' 	=> $emulated_slave_port,
			  'socket' 	=> $emulated_slave_socket,
			  'db'		=> $db,
			  'user'	=> $user,
			  'password'=> array(1, 2, 3),
			),
		),
		'pick' => 'roundrobin',
		'lazy_connections' => 0,
	),
);
if ($error = mst_create_config("test_mysqlnd_ms_settings_password_invalid.ini", $settings))
	die(sprintf("SKIP %s\n", $error));

?>
--INI--
mysqlnd_ms.enable=1
mysqlnd_ms.config_file=test_mysqlnd_ms_settings_password_invalid.ini
--FILE--
<?php
	require_once("connect.inc");
	require_once("util.inc");

	/* note that user etc are to be taken from the config! */
	if (!($link = mysqli_connect("myapp")))
		printf("[001] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());

	$res = mst_mysqli_query(2, $link, "SELECT 1 AS _one FROM DUAL");
	var_dump($res->fetch_assoc());

	print "done!";
?>
--CLEAN--
<?php
	if (!unlink("test_mysqlnd_ms_settings_password_invalid.ini"))
	  printf("[clean] Cannot unlink ini file 'test_mysqlnd_ms_settings_password_invalid.ini'.\n");
?>
--EXPECTF--
Catchable fatal error: mysqli_connect(): (mysqlnd_ms) Invalid value for password. Cannot be a list/hash' . Stopping in %s on line %d