--TEST--
Lazy connections and charsets
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
		'slave' => array($slave_host, $slave_host),
		'pick' => 'roundrobin',
		'lazy_connections' => 1,
	),
);
if ($error = mst_create_config("test_mysqlnd_ms_lazy_charsets.ini", $settings))
	die(sprintf("SKIP %s\n", $error));

function test_for_charset($host, $user, $passwd, $db, $port, $socket) {
	if (!$link = mst_mysqli_connect($host, $user, $passwd, $db, $port, $socket))
		die(sprintf("skip Cannot connect, [%d] %s", mysqli_connect_errno(), mysqli_connect_error()));

	if (!($res = mysqli_query($link, 'SELECT version() AS server_version')) ||
			!($tmp = mysqli_fetch_assoc($res))) {
		mysqli_close($link);
		die(sprintf("skip Cannot check server version, [%d] %s\n",
		mysqli_errno($link), mysqli_error($link)));
	}
	mysqli_free_result($res);
	$version = explode('.', $tmp['server_version']);
	if (empty($version)) {
		mysqli_close($link);
		die(sprintf("skip Cannot check server version, based on '%s'",
			$tmp['server_version']));
	}

	if ($version[0] <= 4 && $version[1] < 1) {
		mysqli_close($link);
		die(sprintf("skip Requires MySQL Server 4.1+\n"));
	}

	if ((($res = mysqli_query($link, "SHOW CHARACTER SET LIKE 'latin1'", MYSQLI_STORE_RESULT)) &&
			(mysqli_num_rows($res) == 1)) ||
			(($res = mysqli_query($link, "SHOW CHARACTER SET LIKE 'latin2'", MYSQLI_STORE_RESULT)) &&
			(mysqli_num_rows($res) == 1))
			) {
		// ok, required latin1 or latin2 are available
	} else {
		die(sprintf("skip Requires character set latin1 or latin2\n"));
	}

	if (!$res = mysqli_query($link, 'SELECT @@character_set_connection AS charset'))
		die(sprintf("skip Cannot select current charset, [%d] %s\n", $link->errno, $link->error));

	if (!$row = mysqli_fetch_assoc($res))
		die(sprintf("skip Cannot detect current charset, [%d] %s\n", $link->errno, $link->error));

	return $row['charset'];
}

$master_charset = test_for_charset($master_host_only, $user, $passwd, $db, $master_port, $master_socket);
$slave_charset = test_for_charset($slave_host_only, $user, $passwd, $db, $slave_port, $slave_socket);

if ($master_charset != $slave_charset) {
	die(sprintf("skip Master (%s) and slave (%s) must use the same default charset.", $master_charset, $slave_charset));
}
?>
--INI--
mysqlnd_ms.enable=1
mysqlnd_ms.config_file=test_mysqlnd_ms_lazy_charsets.ini
--FILE--
<?php
	require_once("connect.inc");
	require_once("util.inc");

	if (!($link = mst_mysqli_connect("myapp", $user, $passwd, $db, $port, $socket)))
		printf("[001] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());

	/* slave 1 */
	if (!$res = mst_mysqli_query(2, $link, "SELECT @@character_set_connection AS charset"))
		printf("[003] [%d] %s\n", $link->errno, $link->error);

	if (!$row = $res->fetch_assoc())
		printf("[004] [%d] %s\n", $link->errno, $link->error);

	$current_charset = $row['charset'];
	$new_charset = ('latin1' == $current_charset) ? 'latin2' : 'latin1';

	/* shall be run on *all* configured machines - all masters, all slaves */
	if (!$link->set_charset($new_charset))
		printf("[005] [%d] %s\n", $link->errno, $link->error);

	/* slave 1 */
	if (!$res = mst_mysqli_query(6, $link, "SELECT @@character_set_connection AS charset", MYSQLND_MS_LAST_USED_SWITCH))
		printf("[007] [%d] %s\n", $link->errno, $link->error);

	if (!$row = $res->fetch_assoc())
		printf("[008] [%d] %s\n", $link->errno, $link->error);

	$current_charset = $row['charset'];
	if ($current_charset != $new_charset)
		printf("[009] Expecting charset '%s' got '%s'\n", $new_charset, $current_charset);

	/* master */
	if (!$res = mst_mysqli_query(10, $link, "SELECT @@character_set_connection AS charset", MYSQLND_MS_MASTER_SWITCH))
		printf("[011] [%d] %s\n", $link->errno, $link->error);

	if (!$row = $res->fetch_assoc())
		printf("[012] [%d] %s\n", $link->errno, $link->error);

	$current_charset = $row['charset'];
	if ($current_charset != $new_charset)
		printf("[013] Expecting charset '%s' got '%s'\n", $new_charset, $current_charset);

	if ($link->character_set_name() != $new_charset)
		printf("[014] Expecting charset '%s' got '%s'\n", $new_charset, $link->character_set_name());

	/* slave 2 */
	if (!$res = mst_mysqli_query(15, $link, "SELECT @@character_set_connection AS charset"))
		printf("[016] [%d] %s\n", $link->errno, $link->error);

	if (!$row = $res->fetch_assoc())
		printf("[017] [%d] %s\n", $link->errno, $link->error);

	$current_charset = $row['charset'];
	printf("Slave 2 reports charset '%s', charset set was '%s'\n", $current_charset, $new_charset);

	print "done!";
?>
--CLEAN--
<?php
	if (!unlink("test_mysqlnd_ms_lazy_charsets.ini"))
	  printf("[clean] Cannot unlink ini file 'test_mysqlnd_ms_lazy_charsets.ini'.\n");
?>
--EXPECTF--

Slave 2 reports charset '%s', charset set was '%s'
done!