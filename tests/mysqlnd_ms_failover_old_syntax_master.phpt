--TEST--
Failover=master, old syntax
--SKIPIF--
<?php
require_once('skipif.inc');
require_once("connect.inc");

if (($emulated_master_host == $emulated_slave_host)) {
	die("SKIP master and slave seem to the the same, see tests/README");
}

_skipif_check_extensions(array("mysqli"));

$settings = array(
	"myapp" => array(
		'master' => array($emulated_master_host),
		'slave' => array("unreachable:6033"),
		'lazy_connections' => 1,
		'failover' => array('master'),
	),
);
if ($error = mst_create_config("test_mysqlnd_ms_failover_old_syntax_master.ini", $settings))
	die(sprintf("SKIP %s\n", $error));

_skipif_connect($emulated_master_host_only, $user, $passwd, $db, $emulated_master_port, $emulated_master_socket);
?>
--INI--
mysqlnd_ms.enable=1
mysqlnd_ms.config_file=test_mysqlnd_ms_failover_old_syntax_master.ini
mysqlnd_ms.collect_statistics=1
--FILE--
<?php
	require_once("connect.inc");
	require_once("util.inc");

	if (!($link = mst_mysqli_connect("myapp", $user, $passwd, $db, $port, $socket)))
		printf("[001] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());

	mst_mysqli_query(2, $link, "SET @myrole='master'", MYSQLND_MS_MASTER_SWITCH);

	mst_mysqli_fech_role(mst_mysqli_query(5, $link, "SELECT CONCAT(@myrole, ' ', CONNECTION_ID()) AS _role", NULL, true, true, false, version_compare(PHP_VERSION, '5.3.99', ">")));

	print "done!";
?>
--CLEAN--
<?php
	if (!unlink("test_mysqlnd_ms_failover_old_syntax_master.ini"))
	  printf("[clean] Cannot unlink ini file 'test_mysqlnd_ms_failover_old_syntax_master.ini'.\n");
?>
--EXPECTF--
This is 'master %d' speaking
done!