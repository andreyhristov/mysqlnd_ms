--TEST--
GTID and report errors off
--SKIPIF--
<?php
if (version_compare(PHP_VERSION, '5.3.99-dev', '<'))
	die(sprintf("SKIP Requires PHP >= 5.3.99, using " . PHP_VERSION));

require_once('skipif.inc');
  require_once("connect.inc");

_skipif_check_extensions(array("mysqli"));
_skipif_connect($emulated_master_host_only, $user, $passwd, $db, $emulated_master_port, $emulated_master_socket);
_skipif_connect($emulated_slave_host_only, $user, $passwd, $db, $emulated_slave_port, $emulated_slave_socket);

include_once("util.inc");
$sql = mst_get_gtid_sql($db);
if ($error = mst_mysqli_drop_gtid_table($emulated_master_host_only, $user, $passwd, $db, $emulated_master_port, $emulated_master_socket))
  die(sprintf("SKIP Failed to drop GTID on master, %s\n", $error));

if ($error = mst_mysqli_drop_gtid_table($emulated_slave_host_only, $user, $passwd, $db, $emulated_slave_port, $emulated_slave_socket))
  die(sprintf("SKIP Failed to drop GTID on master, %s\n", $error));

$settings = array(
	"myapp" => array(
		'master' => array(
			"master1" => array(
				'host' 		=> $emulated_master_host_only,
				'port' 		=> (int)$emulated_master_port,
				'socket' 	=> $emulated_master_socket,
			),
		),
		'slave' => array(
			"slave1" => array(
				'host' 	=> $emulated_slave_host_only,
				'port' 	=> (int)$emulated_slave_port,
				'socket' => $emulated_slave_socket,
			),
		),

		'global_transaction_id_injection' => array(
			'on_commit'	 				=> $sql['update'],
			'report_error'				=> false,
		),

		'lazy_connections' => 1,
		'trx_stickiness' => 'disabled',
		'filters' => array(
			"roundrobin" => array(),
		),
	),

);
if ($error = mst_create_config("test_mysqlnd_ms_gtid_report_errors_off.ini", $settings))
	die(sprintf("SKIP %s\n", $error));
?>
--INI--
mysqlnd_ms.enable=1
mysqlnd_ms.config_file=test_mysqlnd_ms_gtid_report_errors_off.ini
mysqlnd_ms.collect_statistics=1
--FILE--
<?php
	require_once("connect.inc");
	require_once("util.inc");

	function compare_stats($offset, $stats, $expected) {
		foreach ($stats as $name => $value) {
			if (isset($expected[$name])) {
				if ($value != $expected[$name]) {
					printf("[%03d] Expecting %s = %d got %d\n", $offset, $name, $expected[$name], $value);
				}
				unset($expected[$name]);
			}
		}
		if (!empty($expected)) {
			printf("[%03d] Dumping list of missing stats\n", $offset);
			var_dump($expected);
		}
	}

	$link = mst_mysqli_connect("myapp", $user, $passwd, $db, $port, $socket);
	if (mysqli_connect_errno()) {
		printf("[002] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());
	}

	$expected = array(
		"gtid_autocommit_injections_success" => 0,
		"gtid_autocommit_injections_failure" => 0,
		"gtid_commit_injections_success" => 0,
		"gtid_commit_injections_failure" => 0,
	);
	$stats = mysqlnd_ms_get_stats();
	compare_stats(4, $stats, $expected);

	/* auto commit on (default) */
	mst_mysqli_query(5, $link, "SET @myrole='master'");
	$expected['gtid_autocommit_injections_failure']++;

	mst_mysqli_query(7, $link, "SET @myrole='slave'", MYSQLND_MS_SLAVE_SWITCH);
	$stats = mysqlnd_ms_get_stats();
	compare_stats(9, $stats, $expected);

	$link->autocommit(false);

	/* SET should have not been executed */
	if (!$res = mst_mysqli_query(10, $link, "SELECT @myrole AS _role"))
		printf("[012] %d %s\n", $link->errno, $link->error);
	$row = $res->fetch_assoc();
	printf("[013] Slave says '%s'\n", $row['_role']);

	if (!$res = mst_mysqli_query(14, $link, "SELECT @myrole AS _role", MYSQLND_MS_MASTER_SWITCH))
		printf("[016] %d %s\n", $link->errno, $link->error);
	$row = $res->fetch_assoc();
	printf("[017] Master says '%s'\n", $row['_role']);

	if (!$res = mst_mysqli_query(18, $link, "SELECT @myrole AS _role", MYSQLND_MS_LAST_USED_SWITCH))
		printf("[020] %d %s\n", $link->errno, $link->error);
	$row = $res->fetch_assoc();
	printf("[021] Master says again '%s'\n", $row['_role']);

	mst_mysqli_query(22, $link, "SET @myrole='master'");
	if (!$link->commit())
		printf("[025] [%d] %s\n", $link->errno, $link->error);
	$expected['gtid_commit_injections_failure']++;

	$res = mst_mysqli_query(26, $link, "SELECT 1 AS _one FROM DUAL");
	$row = $res->fetch_assoc();
	printf("Slave says '%d'\n", $row['_one']);

	if (!$link->commit())
		printf("[029] [%d] %s\n", $link->errno, $link->error);

	$res = mst_mysqli_query(30, $link, "SELECT 2 AS _two FROM DUAL", MYSQLND_MS_MASTER_SWITCH);
	$row = $res->fetch_assoc();
	printf("Master says '%d'\n", $row['_two']);

	if (!$link->commit())
		printf("[033] [%d] %s\n", $link->errno, $link->error);
	$expected['gtid_commit_injections_failure']++;

	$stats = mysqlnd_ms_get_stats();
	compare_stats(34, $stats, $expected);

	$link->autocommit(true);

	/* Note: we inject before the original query, thus we see the inection error */
	mst_mysqli_query(36, $link, "SET MY LIFE ON FIRE");
	mst_mysqli_query(38, $link, "SET MY LIFE ON FIRE", MYSQLND_MS_MASTER_SWITCH);
	$expected['gtid_autocommit_injections_failure'] += 2;
	$stats = mysqlnd_ms_get_stats();
	compare_stats(40, $stats, $expected);

	$link->autocommit(false);

	mst_mysqli_query(42, $link, "SET MY LIFE ON FIRE");
	mst_mysqli_query(44, $link, "SET MY LIFE ON FIRE", MYSQLND_MS_MASTER_SWITCH);

	/* commit on master connection only */
	$link->commit();
	$stats = mysqlnd_ms_get_stats();
	$expected['gtid_commit_injections_failure']++;
	compare_stats(46, $stats, $expected);

	printf("[047] [%d] %s\n", $link->errno, $link->error);

	if ($error = mst_mysqli_setup_gtid_table($emulated_master_host_only, $user, $passwd, $db, $emulated_master_port, $emulated_master_socket))
		printf("[048] %s\n", $error);

	if (!$link->query("DROP TABLE IF EXISTS test") ||
		!$link->query("CREATE TABLE test(id INT) ENGINE=InnoDB") ||
		!$link->query("INSERT INTO test(id) VALUES (1)"))
		printf("[049] [%d] %s\n", $link->errno, $link->error);

	$link->autocommit(true);

	print "done!";
?>
--CLEAN--
<?php
	if (!unlink("test_mysqlnd_ms_gtid_report_errors_off.ini"))
		printf("[clean] Cannot unlink ini file 'test_mysqlnd_ms_gtid_report_errors_off.ini'.\n");
?>
--EXPECTF--
[013] Slave says 'slave'
[017] Master says 'master'
[021] Master says again 'master'
Slave says '1'
Master says '2'
[036] [1193] %s
[038] [1193] %s
[042] [1193] %s
[044] [1193] %s
[047] [0%A
done!
