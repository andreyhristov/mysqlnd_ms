<?PHP
$runs   = array(1, 10, 100, 1000, 5000); // equals #rows
$types = array(
              127   => 'varchar(127)',
              255   => 'varchar(255)',
              512   => 'varchar(512)',
              1024  => 'varchar(1024)',
              2048  => 'varchar(2048)',
              4096  => 'varchar(4096)',
              8192  => 'varchar(8192)',
              16384 => 'varchar(16384)',
              32768 => 'varchar(32768)',
          );
foreach ($runs as $k => $run) {
  foreach ($types as $len => $type) {
    $times['INSERT ' . $type . ' ' . $run . 'x = #rows query()'] = 0;
    $times['INSERT ' . $type . ' ' . $run . 'x = #rows overall'] = 0;
  }
}
$description = 'Connect, n-times INSERT varchar of size m, close. n and m vary.';
$errors = array();

function mysqli_query_insert($type, $len, $runs, $host, $user, $passwd, $db, $port, $socket) {

  $errors = $times = array();

  foreach ($runs as $k => $run) {
     $times['INSERT ' . $type . ' ' . $run . 'x = #rows overall'] = microtime(true);
    do {
      if (!$link = @mysqli_connect($host, $user, $passwd, $db, $port, $socket)) {
        $errors[] = sprintf("INSERT %s %dx = #rows  connect failure (original code = %s)", $type, $run, ($flag_original_code) ? 'yes' : 'no');
        break 2;
      }
      if (!mysqli_query($link, "DROP TABLE IF EXISTS test")) {
        $errors[] = sprintf("INSERT %s %dx = #rows drop table failure (original code = %s): [%d] %s", $type, $run, ($flag_original_code) ? 'yes' : 'no', mysqli_errno($link), mysqli_error($link));
        break 2;
      }

      if (!mysqli_query($link, sprintf("CREATE TABLE test(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, label %s)", $type))) {
        $errors[] = sprintf("INSERT %s %dx = #rows create table failure (original code = %s): [%d] %s", $type, $run, ($flag_original_code) ? 'yes' : 'no', mysqli_errno($link), mysqli_error($link));
        break 2;
      }

      $label = '';
      for ($i = 0; $i < $len; $i++)
        $label .= chr(mt_rand(65, 90));
      $label = mysqli_real_escape_string($link, $label);      

      for ($i = 1; $i <= $run; $i++) {
          $start = microtime(true);
          $ret = mysqli_query($link, "INSERT INTO test(id, label) VALUES ($i, '$label')");
          $times['INSERT ' . $type . ' ' . $run . 'x = #rows query()'] += microtime(true) - $start;
          if (!$ret) {
          $errors[] = sprintf("INSERT %s %dx = #rows insert failure (original code = %s): [%d] %s", $type, $run, ($flag_original_code) ? 'yes' : 'no', mysqli_errno($link), mysqli_error($link));
          break 3;
        }
      }
      mysqli_close($link); 
    } while (false);
    $times['INSERT ' . $type . ' ' . $run . 'x = #rows overall'] = microtime(true) - $times['INSERT ' . $type . ' ' . $run . 'x = #rows overall'];
  }
  
  return array($errors, $times);
}


ksort($types);
foreach ($types as $len => $type) {
  list ($errors, $tmp_times) = mysqli_query_insert($type,  $len, $runs, $host, $user, $passwd, $db, $port, $socket);
  $times = array_merge($times, $tmp_times);
  if (!empty($errors))  { 
    break;
  }
}
?>