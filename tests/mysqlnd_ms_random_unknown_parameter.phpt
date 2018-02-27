--TEST--
LB random: unknown parameter
--SKIPIF--
<?php
require_once('skipif.inc');
require_once("connect.inc");

_skipif_check_extensions(array("mysqli"));
_skipif_connect($master_host_only, $user, $passwd, $db, $master_port, $master_socket);
_skipif_connect($slave_host_only, $user, $passwd, $db, $slave_port, $slave_socket);

$settings = array(
	"myapp" => array(
		'master' => array(
			"master1" => array(
				'host' 		=> $master_host_only,
				'port' 		=> (int)$master_port,
				'socket' 	=> $master_socket,
			),
		),
		'slave' => array(
			"slave1" => array(
				'host' 	=> $slave_host_only,
				'port' 	=> (int)$slave_port,
				'socket' => $slave_socket,
			),
		 ),
		'lazy_connections' => 1,
		'filters' => array(
			"random" => array("please" => "warn me", "" => "please", "\n" => "SOS"),
		),
	),

);
if ($error = mst_create_config("test_mysqlnd_ms_random_unknown_parameter.ini", $settings))
	die(sprintf("SKIP %s\n", $error));
?>
--INI--
mysqlnd_ms.enable=1
mysqlnd_ms.config_file=test_mysqlnd_ms_random_unknown_parameter.ini
--FILE--
<?php
	require_once("connect.inc");
	require_once("util.inc");

	$link = mst_mysqli_connect("myapp", $user, $passwd, $db, $port, $socket);
	if (mysqli_connect_errno()) {
		printf("[001] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());
	}

	mst_mysqli_query(2, $link, "DROP TABLE IF EXISTS test");
	if ($link->thread_id == 0)
		printf("[003] Which server has run this?");

	for ($i = 0; $i < 10; $i++)
		mst_mysqli_query(5, $link, "SELECT 1 FROM DUAL");

	if ($link->thread_id == 0)
		printf("[006] Which server has run this?");

	print "done!";
?>
--CLEAN--
<?php
	if (!unlink("test_mysqlnd_ms_random_unknown_parameter.ini"))
	  printf("[clean] Cannot unlink ini file 'test_mysqlnd_ms_random_unknown_parameter.ini'.\n");
?>
--EXPECTF--
done!