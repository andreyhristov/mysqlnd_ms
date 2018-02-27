--TEST--
Lazy connect, slave failure, user
--SKIPIF--
<?php
require_once('skipif.inc');
require_once("connect.inc");

_skipif_check_extensions(array("mysqli"));
_skipif_connect($master_host_only, $user, $passwd, $db, $master_port, $master_socket);
_skipif_connect($slave_host_only, $user, $passwd, $db, $slave_port, $slave_socket);

if (($master_host == $slave_host)) {
	die("SKIP master and slave seem to the the same, see tests/README");
}

$settings = array(
	"myapp" => array(
		'master' => array($master_host),
		'slave' => array("unreachable:6033"),
		'pick' 	=> array('user' => array("callback" => "pick_server")),
		'lazy_connections' => 1
	),
);
if ($error = mst_create_config("test_mysqlnd_ms_lazy_slave_failure_user.ini", $settings))
	die(sprintf("SKIP %s\n", $error));
?>
--INI--
mysqlnd_ms.enable=1
mysqlnd_ms.config_file=test_mysqlnd_ms_lazy_slave_failure_user.ini
--FILE--
<?php
	require_once("connect.inc");
	require_once("util.inc");

	$mst_ignore_errors = array(
		/* depends on test machine network configuration */
		'[E_WARNING] mysqli::query(): php_network_getaddresses: getaddrinfo failed: Name or service not known',
	);
	set_error_handler('mst_error_handler');

	function pick_server($connected_host, $query, $master, $slaves, $last_used_connection, $in_transaction) {

		$where = mysqlnd_ms_query_is_select($query);
		$server = '';
		switch ($where) {
			case MYSQLND_MS_QUERY_USE_LAST_USED:
			  $ret = $last_used_connection;
			  $server = 'last used';
			  break;
			case MYSQLND_MS_QUERY_USE_MASTER:
			  $ret = $master[0];
			  $server = 'master';
			  break;
			case MYSQLND_MS_QUERY_USE_SLAVE:
 			  $ret = $slaves[0];
			  $server = 'slave';
			  break;
			default:
			  printf("Unknown return value from mysqlnd_ms_query_is_select, where = %s .\n", $where);
			  $ret = $master[0];
			  $server = 'unknown';
			  break;
		}
		printf("pick_server('%s', '%s') => %s\n", $connected_host, $query, $server);
		return $ret;
	}

	if (!($link = mst_mysqli_connect("myapp", $user, $passwd, $db, $port, $socket)))
		printf("[001] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());

	$connections = array();

	mst_mysqli_query(2, $link, "SET @myrole='master'", MYSQLND_MS_MASTER_SWITCH);
	$connections[$link->thread_id] = array('master');

	mst_mysqli_query(3, $link, "SET @myrole='slave'", MYSQLND_MS_SLAVE_SWITCH, true, false, false, version_compare(PHP_VERSION, '5.3.99', ">"));
	$connections[$link->thread_id][] = 'slave (no fallback)';

	mst_mysqli_fech_role(mst_mysqli_query(4, $link, "SELECT CONCAT(@myrole, ' ', CONNECTION_ID()) AS _role", NULL, true, false, false, version_compare(PHP_VERSION, '5.3.99', ">")));
	$connections[$link->thread_id][] = 'slave (no fallback)';

	mst_mysqli_fech_role(mst_mysqli_query(5, $link, "SELECT CONCAT(@myrole, ' ', CONNECTION_ID()) AS _role", NULL, true, false, false, version_compare(PHP_VERSION, '5.3.99', ">")));
	$connections[$link->thread_id][] = 'slave (no fallback)';

	foreach ($connections as $thread_id => $details) {
		printf("Connection %d -\n", $thread_id);
		foreach ($details as $msg)
		  printf("... %s\n", $msg);
	}

	print "done!";
?>
--CLEAN--
<?php
	if (!unlink("test_mysqlnd_ms_lazy_slave_failure_user.ini"))
	  printf("[clean] Cannot unlink ini file 'test_mysqlnd_ms_lazy_slave_failure_user.ini'.\n");
?>
--EXPECTF--
pick_server('myapp', '/*ms=master*//*2*/SET @myrole='master'') => master
Connect error, [003] [2002] %s
Connect error, [004] [2002] %s
Connect error, [005] [2002] %s
Connection %d -
... master
Connection 0 -
... slave (no fallback)
... slave (no fallback)
... slave (no fallback)
done!