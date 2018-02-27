--TEST--
Unreachable slave, pick = user, lazy = 0
--SKIPIF--
<?php
require_once('skipif.inc');
require_once("connect.inc");

_skipif_check_extensions(array("mysqli"));
_skipif_connect($master_host_only, $user, $passwd, $db, $master_port, $master_socket);
_skipif_connect($slave_host_only, $user, $passwd, $db, $slave_port, $slave_socket);

$settings = array(
	"myapp" => array(
		'master' => array($master_host),
		'slave' => array($slave_host, "unknown_i_really_hope", $slave_host),
		'pick' 	=> array('user' => array('callback' => 'pick_server')),
		'lazy_connections' => 0,
	),

);
if ($error = mst_create_config("test_mysqlnd_ms_slave_unreachable_rr.ini", $settings))
	die(sprintf("SKIP %s\n", $error));
?>
--INI--
error_reporting=E_ALL
mysqlnd_ms.enable=1
mysqlnd_ms.config_file=test_mysqlnd_ms_slave_unreachable_rr.ini
mysqlnd_ms.collect_statistics=1
--FILE--
<?php
	require_once("connect.inc");
	require_once("util.inc");

	function pick_server($connected_host, $query, $master, $slaves, $last_used_connection, $in_transaction) {
		static $pick_server_last_used = "";
		printf("pick_server(%s)\n", $query);
		if ($in_transaction)
			return $master[0];

		$ret = "";
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
			  printf("[012] Unknown return value from mysqlnd_ms_query_is_select, where = %s .\n", $where);
			  $ret = $master[0];
			  $server = 'unknown';
			  break;
		}

		$pick_server_last_used = $ret;
		return $ret;
	}

	mst_compare_stats();

	/* error messages (warnings can vary a bit, let's not bother about it */
	ob_start();
	$link = mst_mysqli_connect("myapp", $user, $passwd, $db, $port, $socket);
	$tmp = ob_get_contents();
	ob_end_clean();
	if ('' == $tmp)
		printf("[001] Expecting some warnings/errors but nothing has been printed, check manually");

	mst_compare_stats();

	if (!$link) {
		if (0 == mysqli_connect_errno()) {
			printf("[002] Plugin has failed to connect but connect error is not set, [%d] '%s'\n",
				mysqli_connect_errno(), mysqli_connect_error());
		} else {
			if (isset($mst_connect_errno_codes[mysqli_connect_errno()])) {
				die(sprintf("[003] OK, plugin reports connect error, [%d] '%s'\n",
					mysqli_connect_errno(), mysqli_connect_error()));
			} else {
				die(sprintf("[003] Plugin reports unknown connect error, [%d] '%s'\n",
					mysqli_connect_errno(), mysqli_connect_error()));
			}
		}
	} else {
		printf("[004] Plugin returns valid handle, no API to fetch error codes, connect error: [%d] '%s', error: [%d] '%s'\n",
			mysqli_connect_errno(), mysqli_connect_error(),
			mysqli_errno($link), mysqli_error($link));
	}

	print "done!";
?>
--CLEAN--
<?php
	if (!unlink("test_mysqlnd_ms_slave_unreachable_rr.ini"))
	  printf("[clean] Cannot unlink ini file 'test_mysqlnd_ms_slave_unreachable_rr.ini'.\n");
?>
--EXPECTF--
Stats non_lazy_connections_slave_success: 2
Stats non_lazy_connections_slave_failure: 1
Stats non_lazy_connections_master_success: 1
Stats pool_masters_total: 1
Stats pool_slaves_total: 3
Stats pool_masters_active: 1
Stats pool_slaves_active: 3
[003] OK, plugin reports connect error, [%d] %s
