<?php

# ============================================================================
# This program is part of Percona Monitoring Plugins
# License: GPL License (see COPYING)
# Copyright 2008-2015 Baron Schwartz, 2012-2015 Percona
# Authors:
#  Baron Schwartz, Roman Vynar
# ============================================================================

# ============================================================================
# To make this code testable, we need to prevent code from running when it is
# included from the test script.  The test script and this file have different
# filenames, so we can compare them.  In some cases $_SERVER['SCRIPT_FILENAME']
# seems not to be defined, so we skip the check -- this check should certainly
# pass in the test environment.
# ============================================================================
if ( !array_key_exists('SCRIPT_FILENAME', $_SERVER)
   || basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) ) {

# ============================================================================
# CONFIGURATION
# ============================================================================
# Define MySQL connection constants in config.php.  Arguments explicitly passed
# in from Cacti will override these.  However, if you leave them blank in Cacti
# and set them here, you can make life easier.  Instead of defining parameters
# here, you can define them in another file named the same as this file, with a
# .cnf extension.
# ============================================================================
$mysql_user = 'zabbix';
$mysql_pass = 'wJ44mR6a59r3duDr';
$mysql_port = 3306;
$mysql_ssl  = false;   # Whether to use SSL to connect to MySQL.
$mysql_ssl_key  = '/etc/pki/tls/certs/mysql/client-key.pem';
$mysql_ssl_cert = '/etc/pki/tls/certs/mysql/client-cert.pem';
$mysql_ssl_ca   = '/etc/pki/tls/certs/mysql/ca-cert.pem';

$heartbeat = false;        # Whether to use pt-heartbeat table for repl. delay calculation.
$heartbeat_utc = false;    # Whether pt-heartbeat is run with --utc option.
$heartbeat_server_id = 0;  # Server id to associate with a heartbeat. Leave 0 if no preference.
$heartbeat_table = 'percona.heartbeat'; # db.tbl.

$cache_dir  = '/tmp';  # If set, this uses caching to avoid multiple calls.
$poll_time  = 300;     # Adjust to match your polling interval.
$timezone   = null;    # If not set, uses the system default.  Example: "UTC"
$chk_options = array (
   'innodb'  => true,    # Do you want to check InnoDB statistics?
   'master'  => true,    # Do you want to check binary logging?
   'slave'   => true,    # Do you want to check slave status?
   'procs'   => true,    # Do you want to check SHOW PROCESSLIST?
   'get_qrt' => true,    # Get query response times from Percona Server or MariaDB?
);

$use_ss    = false; # Whether to use the script server or not
$debug     = false; # Define whether you want debugging behavior.
$debug_log = false; # If $debug_log is a filename, it'll be used.

# ============================================================================
# You should not need to change anything below this line.
# ============================================================================
$version = '1.1.5';

# ============================================================================
# Include settings from an external config file.
# ============================================================================
if ( file_exists('/etc/cacti/' . basename(__FILE__) . '.cnf' ) ) {
   require('/etc/cacti/' . basename(__FILE__) . '.cnf');
   debug('Found configuration file /etc/cacti/' . basename(__FILE__) . '.cnf');
}
elseif ( file_exists(__FILE__ . '.cnf' ) ) {
   require(__FILE__ . '.cnf');
   debug('Found configuration file ' . __FILE__ . '.cnf');
}

# Make this a happy little script even when there are errors.
$no_http_headers = true;
ini_set('implicit_flush', false); # No output, ever.
if ( $debug ) {
   ini_set('display_errors', true);
   ini_set('display_startup_errors', true);
   ini_set('error_reporting', 2147483647);
}
else {
   ini_set('error_reporting', E_ERROR);
}
ob_start(); # Catch all output such as notices of undefined array indexes.
function error_handler($errno, $errstr, $errfile, $errline) {
   print("$errstr at $errfile line $errline\n");
   debug("$errstr at $errfile line $errline");
}
# ============================================================================
# Set up the stuff we need to be called by the script server.
# ============================================================================
if ( $use_ss ) {
   if ( file_exists( dirname(__FILE__) . "/../include/global.php") ) {
      # See issue 5 for the reasoning behind this.
      debug("including " . dirname(__FILE__) . "/../include/global.php");
      include_once(dirname(__FILE__) . "/../include/global.php");
   }
   elseif ( file_exists( dirname(__FILE__) . "/../include/config.php" ) ) {
      # Some Cacti installations don't have global.php.
      debug("including " . dirname(__FILE__) . "/../include/config.php");
      include_once(dirname(__FILE__) . "/../include/config.php");
   }
}

# ============================================================================
# Set the default timezone either to the configured, system timezone, or the
# default set above in the script.
# ============================================================================
if ( function_exists("date_default_timezone_set")
   && function_exists("date_default_timezone_get") ) {
   $tz = ($timezone ? $timezone : @date_default_timezone_get());
   if ( $tz ) {
      @date_default_timezone_set($tz);
   }
}

# ============================================================================
# Make sure we can also be called as a script.
# ============================================================================
if (!isset($called_by_script_server)) {
   debug($_SERVER["argv"]);
   array_shift($_SERVER["argv"]); # Strip off this script's filename
   $options = parse_cmdline($_SERVER["argv"]);
   validate_options($options);
   $result = ss_get_mysql_stats($options);
   debug($result);
   if ( !$debug ) {
      # Throw away the buffer, which ought to contain only errors.
      ob_end_clean();
   }
   else {
      ob_end_flush(); # In debugging mode, print out the errors.
   }

   # Split the result up and extract only the desired parts of it.
   $wanted = explode(',', $options['items']);
   $output = array();
   foreach ( explode(' ', $result) as $item ) {
      if ( in_array(substr($item, 0, 2), $wanted) ) {
         $output[] = $item;
      }
   }
   debug(array("Final result", $output));
   print(implode(' ', $output));
}

# ============================================================================
# End "if file was not included" section.
# ============================================================================
}

# ============================================================================
# Work around the lack of array_change_key_case in older PHP.
# ============================================================================
if ( !function_exists('array_change_key_case') ) {
   function array_change_key_case($arr) {
      $res = array();
      foreach ( $arr as $key => $val ) {
         $res[strtolower($key)] = $val;
      }
      return $res;
   }
}

# ============================================================================
# Validate that the command-line options are here and correct
# ============================================================================
function validate_options($options) {
   $opts = array('host', 'items', 'user', 'pass', 'nocache', 'port', 'server-id');
   # Show help
   if ( array_key_exists('help', $options) ) {
      usage('');
   }

   # Required command-line options
   foreach ( array('host', 'items') as $option ) {
      if ( !isset($options[$option]) || !$options[$option] ) {
         usage("Required option --$option is missing");
      }
   }
   foreach ( $options as $key => $val ) {
      if ( !in_array($key, $opts) ) {
         usage("Unknown option --$key");
      }
   }
}

# ============================================================================
# Print out a brief usage summary
# ============================================================================
function usage($message) {
   global $mysql_user, $mysql_pass, $mysql_port;

   $usage = <<<EOF
$message
Usage: php ss_get_mysql_stats.php --host <host> --items <item,...> [OPTION]

   --host      MySQL host
   --items     Comma-separated list of the items whose data you want
   --user      MySQL username
   --pass      MySQL password
   --port      MySQL port
   --server-id Server id to associate with a heartbeat if heartbeat usage is enabled
   --nocache   Do not cache results in a file
   --help      Show usage

EOF;
   die($usage);
}

# ============================================================================
# Parse command-line arguments, in the format --arg value --arg value, and
# return them as an array ( arg => value )
# ============================================================================
function parse_cmdline( $args ) {
   $options = array();
   while (list($tmp, $p) = each($args)) {
      if (strpos($p, '--') === 0) {
         $param = substr($p, 2);
         $value = null;
         $nextparam = current($args);
         if ($nextparam !== false && strpos($nextparam, '--') !==0) {
            list($tmp, $value) = each($args);
         }
         $options[$param] = $value;
      }
   }
   if ( array_key_exists('host', $options) ) {
      $options['host'] = substr($options['host'], 0, 4) == 'tcp:' ? substr($options['host'], 4) : $options['host'];
   }
   debug($options);
   return $options;
}

# ============================================================================
# This is the main function.  Some parameters are filled in from defaults at the
# top of this file.
# ============================================================================
function ss_get_mysql_stats( $options ) {
   # Process connection options.
   global $debug, $mysql_user, $mysql_pass, $cache_dir, $poll_time, $chk_options,
          $mysql_port, $mysql_ssl, $mysql_ssl_key, $mysql_ssl_cert, $mysql_ssl_ca,
          $heartbeat, $heartbeat_table, $heartbeat_server_id, $heartbeat_utc;

   $user = isset($options['user']) ? $options['user'] : $mysql_user;
   $pass = isset($options['pass']) ? $options['pass'] : $mysql_pass;
   $host = $options['host'];
   $port = isset($options['port']) ? $options['port'] : $mysql_port;
   $heartbeat_server_id = isset($options['server-id']) ? $options['server-id'] : $heartbeat_server_id;

   $sanitized_host = str_replace(array(":", "/"), array("", "_"), $host);
   $cache_file = "$cache_dir/$sanitized_host-mysql_cacti_stats.txt" . ($port != 3306 ? ":$port" : '');
   debug("Cache file is $cache_file");

   # First, check the cache.
   $fp = null;
   if ( $cache_dir && !array_key_exists('nocache', $options) ) {
      if ( $fp = fopen($cache_file, 'a+') ) {
         $locked = flock($fp, 1); # LOCK_SH
         if ( $locked ) {
            if ( filesize($cache_file) > 0
               && filectime($cache_file) + ($poll_time/2) > time()
               && ($arr = file($cache_file))
            ) {# The cache file is good to use.
               debug("Using the cache file");
               fclose($fp);
               return $arr[0];
            }
            else {
               debug("The cache file seems too small or stale");
               # Escalate the lock to exclusive, so we can write to it.
               if ( flock($fp, 2) ) { # LOCK_EX
                  # We might have blocked while waiting for that LOCK_EX, and
                  # another process ran and updated it.  Let's see if we can just
                  # return the data now:
                  if ( filesize($cache_file) > 0
                     && filectime($cache_file) + ($poll_time/2) > time()
                     && ($arr = file($cache_file))
                  ) {# The cache file is good to use.
                     debug("Using the cache file");
                     fclose($fp);
                     return $arr[0];
                  }
                  ftruncate($fp, 0); # Now it's ready for writing later.
               }
            }
         }
         else {
            $fp = null;
            debug("Couldn't lock the cache file, ignoring it");
         }
      }
      else {
         $fp = null;
         debug("Couldn't open the cache file");
      }
   }
   else {
      debug("Caching is disabled.");
   }

   # Connect to MySQL.
   debug(array('Connecting to', $host, $port, $user, $pass));
   if ( !extension_loaded('mysqli') ) {
      debug("PHP MySQLi extension is not loaded");
      die("PHP MySQLi extension is not loaded");
   }
   if ( $mysql_ssl ) {
      $conn = mysqli_init();
      mysqli_ssl_set($conn, $mysql_ssl_key, $mysql_ssl_cert, $mysql_ssl_ca, null, null);
      mysqli_real_connect($conn, $host, $user, $pass, null, $port);
   }
   else {
      $conn = mysqli_connect($host, $user, $pass, null, $port);
   }
   if ( mysqli_connect_errno() ) {
      debug("MySQL connection failed: " . mysqli_connect_error());
      die("ERROR: " . mysqli_connect_error());
   }

   # Set up variables.
   $status = array( # Holds the result of SHOW STATUS, SHOW INNODB STATUS, etc
      # Define some indexes so they don't cause errors with += operations.
      'relay_log_space'          => null,
      'binary_log_space'         => null,
      'current_transactions'     => 0,
      'locked_transactions'      => 0,
      'active_transactions'      => 0,
      'innodb_locked_tables'     => 0,
      'innodb_tables_in_use'     => 0,
      'innodb_lock_structs'      => 0,
      'innodb_lock_wait_secs'    => 0,
      'innodb_sem_waits'         => 0,
      'innodb_sem_wait_time_ms'  => 0,
      # Values for the 'state' column from SHOW PROCESSLIST (converted to
      # lowercase, with spaces replaced by underscores)
      'State_closing_tables'       => 0,
      'State_copying_to_tmp_table' => 0,
      'State_end'                  => 0,
      'State_freeing_items'        => 0,
      'State_init'                 => 0,
      'State_locked'               => 0,
      'State_login'                => 0,
      'State_preparing'            => 0,
      'State_reading_from_net'     => 0,
      'State_sending_data'         => 0,
      'State_sorting_result'       => 0,
      'State_statistics'           => 0,
      'State_updating'             => 0,
      'State_writing_to_net'       => 0,
      'State_none'                 => 0,
      'State_other'                => 0, # Everything not listed above
   );

   # Get SHOW STATUS and convert the name-value array into a simple
   # associative array.
   $result = run_query("SHOW /*!50002 GLOBAL */ STATUS", $conn);
   foreach ( $result as $row ) {
      $status[$row[0]] = $row[1];
   }

   # Get SHOW VARIABLES and do the same thing, adding it to the $status array.
   $result = run_query("SHOW VARIABLES", $conn);
   foreach ( $result as $row ) {
      $status[$row[0]] = $row[1];
   }

   # Get SHOW SLAVE STATUS, and add it to the $status array.
   if ( $chk_options['slave'] ) {
      # Leverage lock-free SHOW SLAVE STATUS if available
      $result = run_query("SHOW SLAVE STATUS NONBLOCKING", $conn);
      if ( !$result ) {
         $result = run_query("SHOW SLAVE STATUS NOLOCK", $conn);
         if ( !$result ) {
            $result = run_query("SHOW SLAVE STATUS", $conn);
         }
      }
      $slave_status_rows_gotten = 0;
      foreach ( $result as $row ) {
         $slave_status_rows_gotten++;
         # Must lowercase keys because different MySQL versions have different
         # lettercase.
         $row = array_change_key_case($row, CASE_LOWER);
         $status['relay_log_space']  = $row['relay_log_space'];
         $status['slave_lag']        = $row['seconds_behind_master'];

         # Check replication heartbeat, if present.
         if ( $heartbeat ) {
            if ( $heartbeat_utc ) {
               $now_func = 'UNIX_TIMESTAMP(UTC_TIMESTAMP)';
            }
            else {
               $now_func = 'UNIX_TIMESTAMP()';
            }
            $result2 = run_query(
               "SELECT MAX($now_func - ROUND(UNIX_TIMESTAMP(ts)))"
               . " AS delay FROM $heartbeat_table"
               . " WHERE $heartbeat_server_id = 0 OR server_id = $heartbeat_server_id", $conn);
            $slave_delay_rows_gotten = 0;
            foreach ( $result2 as $row2 ) {
               $slave_delay_rows_gotten++;
               if ( $row2 && is_array($row2)
                  && array_key_exists('delay', $row2) )
               {
                  $status['slave_lag'] = $row2['delay'];
               }
               else {
                  debug("Couldn't get slave lag from $heartbeat_table");
               }
            }
            if ( $slave_delay_rows_gotten == 0 ) {
               debug("Got nothing from heartbeat query");
            }
         }

         # Scale slave_running and slave_stopped relative to the slave lag.
         $status['slave_running'] = ($row['slave_sql_running'] == 'Yes')
            ? $status['slave_lag'] : 0;
         $status['slave_stopped'] = ($row['slave_sql_running'] == 'Yes')
            ? 0 : $status['slave_lag'];
      }
      if ( $slave_status_rows_gotten == 0 ) {
         debug("Got nothing from SHOW SLAVE STATUS");
      }
   }

   # Get SHOW MASTER STATUS, and add it to the $status array.
   if ( $chk_options['master']
         && array_key_exists('log_bin', $status)
         && $status['log_bin'] == 'ON'
   ) { # See issue #8
      $binlogs = array(0);
      $result = run_query("SHOW MASTER LOGS", $conn);
      foreach ( $result as $row ) {
         $row = array_change_key_case($row, CASE_LOWER);
         # Older versions of MySQL may not have the File_size column in the
         # results of the command.  Zero-size files indicate the user is
         # deleting binlogs manually from disk (bad user! bad!).
         if ( array_key_exists('file_size', $row) && $row['file_size'] > 0 ) {
            $binlogs[] = $row['file_size'];
         }
      }
      if (count($binlogs)) {
         $status['binary_log_space'] = to_int(array_sum($binlogs));
      }
   }

   # Get SHOW PROCESSLIST and aggregate it by state, then add it to the array
   # too.
   if ( $chk_options['procs'] ) {
      $result = run_query('SHOW PROCESSLIST', $conn);
      foreach ( $result as $row ) {
         $state = $row['State'];
         if ( is_null($state) ) {
            $state = 'NULL';
         }
         if ( $state == '' ) {
            $state = 'none';
         }
         # MySQL 5.5 replaces the 'Locked' state with a variety of "Waiting for
         # X lock" types of statuses.  Wrap these all back into "Locked" because
         # we don't really care about the type of locking it is.
         $state = preg_replace('/^(Table lock|Waiting for .*lock)$/', 'Locked', $state);
         $state = str_replace(' ', '_', strtolower($state));
         if ( array_key_exists("State_$state", $status) ) {
            increment($status, "State_$state", 1);
         }
         else {
            increment($status, "State_other", 1);
         }
      }
   }

   # Get SHOW ENGINES to be able to determine whether InnoDB is present.
   $engines = array();
   $result = run_query("SHOW ENGINES", $conn);
   foreach ( $result as $row ) {
      $engines[$row[0]] = $row[1];
   }

   # Get SHOW INNODB STATUS and extract the desired metrics from it, then add
   # those to the array too.
   if ( $chk_options['innodb']
         && array_key_exists('InnoDB', $engines)
         && $engines['InnoDB'] == 'YES'
         || $engines['InnoDB'] == 'DEFAULT'
   ) {
      $result        = run_query("SHOW /*!50000 ENGINE*/ INNODB STATUS", $conn);
      $istatus_text = $result[0]['Status'];
      $istatus_vals = get_innodb_array($istatus_text);

      # Get response time histogram from Percona Server or MariaDB if enabled.
      if ( $chk_options['get_qrt']
           && (( isset($status['have_response_time_distribution'])
           && $status['have_response_time_distribution'] == 'YES')
           || (isset($status['query_response_time_stats'])
           && $status['query_response_time_stats'] == 'ON')) )
      {
         debug('Getting query time histogram');
         $i = 0;
         $result = run_query(
            "SELECT `count`, ROUND(total * 1000000) AS total "
               . "FROM INFORMATION_SCHEMA.QUERY_RESPONSE_TIME "
               . "WHERE `time` <> 'TOO LONG'",
            $conn);
         foreach ( $result as $row ) {
            if ( $i > 13 ) {
               # It's possible that the number of rows returned isn't 14.
               # Don't add extra status counters.
               break;
            }
            $count_key = sprintf("Query_time_count_%02d", $i);
            $total_key = sprintf("Query_time_total_%02d", $i);
            $status[$count_key] = $row['count'];
            $status[$total_key] = $row['total'];
            $i++;
         }
         # It's also possible that the number of rows returned is too few.
         # Don't leave any status counters unassigned; it will break graphs.
         while ( $i <= 13 ) {
            $count_key = sprintf("Query_time_count_%02d", $i);
            $total_key = sprintf("Query_time_total_%02d", $i);
            $status[$count_key] = 0;
            $status[$total_key] = 0;
            $i++;
         }
      }
      else {
         debug('Not getting time histogram because it is not enabled');
      }

      # Override values from InnoDB parsing with values from SHOW STATUS,
      # because InnoDB status might not have everything and the SHOW STATUS is
      # to be preferred where possible.
      $overrides = array(
         'Innodb_buffer_pool_pages_data'  => 'database_pages',
         'Innodb_buffer_pool_pages_dirty' => 'modified_pages',
         'Innodb_buffer_pool_pages_free'  => 'free_pages',
         'Innodb_buffer_pool_pages_total' => 'pool_size',
         'Innodb_data_fsyncs'             => 'file_fsyncs',
         'Innodb_data_pending_reads'      => 'pending_normal_aio_reads',
         'Innodb_data_pending_writes'     => 'pending_normal_aio_writes',
         'Innodb_os_log_pending_fsyncs'   => 'pending_log_flushes',
         'Innodb_pages_created'           => 'pages_created',
         'Innodb_pages_read'              => 'pages_read',
         'Innodb_pages_written'           => 'pages_written',
         'Innodb_rows_deleted'            => 'rows_deleted',
         'Innodb_rows_inserted'           => 'rows_inserted',
         'Innodb_rows_read'               => 'rows_read',
         'Innodb_rows_updated'            => 'rows_updated',
         'Innodb_buffer_pool_reads'       => 'pool_reads',
         'Innodb_buffer_pool_read_requests' => 'pool_read_requests',
      );

      # If the SHOW STATUS value exists, override...
      foreach ( $overrides as $key => $val ) {
         if ( array_key_exists($key, $status) ) {
            debug("Override $key");
            $istatus_vals[$val] = $status[$key];
         }
      }

      # Now copy the values into $status.
      foreach ( $istatus_vals as $key => $val ) {
         $status[$key] = $istatus_vals[$key];
      }
   }

   # Make table_open_cache backwards-compatible (issue 63).
   if ( array_key_exists('table_open_cache', $status) ) {
      $status['table_cache'] = $status['table_open_cache'];
   }

   # Compute how much of the key buffer is used and unflushed (issue 127).
   $status['Key_buf_bytes_used']
      = big_sub($status['key_buffer_size'],
         big_multiply($status['Key_blocks_unused'],
         $status['key_cache_block_size']));
   $status['Key_buf_bytes_unflushed']
      = big_multiply($status['Key_blocks_not_flushed'],
         $status['key_cache_block_size']);

   if ( array_key_exists('unflushed_log', $status)
         && $status['unflushed_log']
   ) {
      # TODO: I'm not sure what the deal is here; need to debug this.  But the
      # unflushed log bytes spikes a lot sometimes and it's impossible for it to
      # be more than the log buffer.
      debug("Unflushed log: $status[unflushed_log]");
      $status['unflushed_log']
         = max($status['unflushed_log'], $status['innodb_log_buffer_size']);
   }

   # Define the variables to output.  I use shortened variable names so maybe
   # it'll all fit in 1024 bytes for Cactid and Spine's benefit.  Strings must
   # have some non-hex characters (non a-f0-9) to avoid a Cacti bug.  This list
   # must come right after the word MAGIC_VARS_DEFINITIONS.  The Perl script
   # parses it and uses it as a Perl variable.
   $keys = array(
      'Key_read_requests'           =>  'gg',
      'Key_reads'                   =>  'gh',
      'Key_write_requests'          =>  'gi',
      'Key_writes'                  =>  'gj',
      'history_list'                =>  'gk',
      'innodb_transactions'         =>  'gl',
      'read_views'                  =>  'gm',
      'current_transactions'        =>  'gn',
      'locked_transactions'         =>  'go',
      'active_transactions'         =>  'gp',
      'pool_size'                   =>  'gq',
      'free_pages'                  =>  'gr',
      'database_pages'              =>  'gs',
      'modified_pages'              =>  'gt',
      'pages_read'                  =>  'gu',
      'pages_created'               =>  'gv',
      'pages_written'               =>  'gw',
      'file_fsyncs'                 =>  'gx',
      'file_reads'                  =>  'gy',
      'file_writes'                 =>  'gz',
      'log_writes'                  =>  'hg',
      'pending_aio_log_ios'         =>  'hh',
      'pending_aio_sync_ios'        =>  'hi',
      'pending_buf_pool_flushes'    =>  'hj',
      'pending_chkp_writes'         =>  'hk',
      'pending_ibuf_aio_reads'      =>  'hl',
      'pending_log_flushes'         =>  'hm',
      'pending_log_writes'          =>  'hn',
      'pending_normal_aio_reads'    =>  'ho',
      'pending_normal_aio_writes'   =>  'hp',
      'ibuf_inserts'                =>  'hq',
      'ibuf_merged'                 =>  'hr',
      'ibuf_merges'                 =>  'hs',
      'spin_waits'                  =>  'ht',
      'spin_rounds'                 =>  'hu',
      'os_waits'                    =>  'hv',
      'rows_inserted'               =>  'hw',
      'rows_updated'                =>  'hx',
      'rows_deleted'                =>  'hy',
      'rows_read'                   =>  'hz',
      'Table_locks_waited'          =>  'ig',
      'Table_locks_immediate'       =>  'ih',
      'Slow_queries'                =>  'ii',
      'Open_files'                  =>  'ij',
      'Open_tables'                 =>  'ik',
      'Opened_tables'               =>  'il',
      'innodb_open_files'           =>  'im',
      'open_files_limit'            =>  'in',
      'table_cache'                 =>  'io',
      'Aborted_clients'             =>  'ip',
      'Aborted_connects'            =>  'iq',
      'Max_used_connections'        =>  'ir',
      'Slow_launch_threads'         =>  'is',
      'Threads_cached'              =>  'it',
      'Threads_connected'           =>  'iu',
      'Threads_created'             =>  'iv',
      'Threads_running'             =>  'iw',
      'max_connections'             =>  'ix',
      'thread_cache_size'           =>  'iy',
      'Connections'                 =>  'iz',
      'slave_running'               =>  'jg',
      'slave_stopped'               =>  'jh',
      'Slave_retried_transactions'  =>  'ji',
      'slave_lag'                   =>  'jj',
      'Slave_open_temp_tables'      =>  'jk',
      'Qcache_free_blocks'          =>  'jl',
      'Qcache_free_memory'          =>  'jm',
      'Qcache_hits'                 =>  'jn',
      'Qcache_inserts'              =>  'jo',
      'Qcache_lowmem_prunes'        =>  'jp',
      'Qcache_not_cached'           =>  'jq',
      'Qcache_queries_in_cache'     =>  'jr',
      'Qcache_total_blocks'         =>  'js',
      'query_cache_size'            =>  'jt',
      'Questions'                   =>  'ju',
      'Com_update'                  =>  'jv',
      'Com_insert'                  =>  'jw',
      'Com_select'                  =>  'jx',
      'Com_delete'                  =>  'jy',
      'Com_replace'                 =>  'jz',
      'Com_load'                    =>  'kg',
      'Com_update_multi'            =>  'kh',
      'Com_insert_select'           =>  'ki',
      'Com_delete_multi'            =>  'kj',
      'Com_replace_select'          =>  'kk',
      'Select_full_join'            =>  'kl',
      'Select_full_range_join'      =>  'km',
      'Select_range'                =>  'kn',
      'Select_range_check'          =>  'ko',
      'Select_scan'                 =>  'kp',
      'Sort_merge_passes'           =>  'kq',
      'Sort_range'                  =>  'kr',
      'Sort_rows'                   =>  'ks',
      'Sort_scan'                   =>  'kt',
      'Created_tmp_tables'          =>  'ku',
      'Created_tmp_disk_tables'     =>  'kv',
      'Created_tmp_files'           =>  'kw',
      'Bytes_sent'                  =>  'kx',
      'Bytes_received'              =>  'ky',
      'innodb_log_buffer_size'      =>  'kz',
      'unflushed_log'               =>  'lg',
      'log_bytes_flushed'           =>  'lh',
      'log_bytes_written'           =>  'li',
      'relay_log_space'             =>  'lj',
      'binlog_cache_size'           =>  'lk',
      'Binlog_cache_disk_use'       =>  'll',
      'Binlog_cache_use'            =>  'lm',
      'binary_log_space'            =>  'ln',
      'innodb_locked_tables'        =>  'lo',
      'innodb_lock_structs'         =>  'lp',
      'State_closing_tables'        =>  'lq',
      'State_copying_to_tmp_table'  =>  'lr',
      'State_end'                   =>  'ls',
      'State_freeing_items'         =>  'lt',
      'State_init'                  =>  'lu',
      'State_locked'                =>  'lv',
      'State_login'                 =>  'lw',
      'State_preparing'             =>  'lx',
      'State_reading_from_net'      =>  'ly',
      'State_sending_data'          =>  'lz',
      'State_sorting_result'        =>  'mg',
      'State_statistics'            =>  'mh',
      'State_updating'              =>  'mi',
      'State_writing_to_net'        =>  'mj',
      'State_none'                  =>  'mk',
      'State_other'                 =>  'ml',
      'Handler_commit'              =>  'mm',
      'Handler_delete'              =>  'mn',
      'Handler_discover'            =>  'mo',
      'Handler_prepare'             =>  'mp',
      'Handler_read_first'          =>  'mq',
      'Handler_read_key'            =>  'mr',
      'Handler_read_next'           =>  'ms',
      'Handler_read_prev'           =>  'mt',
      'Handler_read_rnd'            =>  'mu',
      'Handler_read_rnd_next'       =>  'mv',
      'Handler_rollback'            =>  'mw',
      'Handler_savepoint'           =>  'mx',
      'Handler_savepoint_rollback'  =>  'my',
      'Handler_update'              =>  'mz',
      'Handler_write'               =>  'ng',
      'innodb_tables_in_use'        =>  'nh',
      'innodb_lock_wait_secs'       =>  'ni',
      'hash_index_cells_total'      =>  'nj',
      'hash_index_cells_used'       =>  'nk',
      'total_mem_alloc'             =>  'nl',
      'additional_pool_alloc'       =>  'nm',
      'uncheckpointed_bytes'        =>  'nn',
      'ibuf_used_cells'             =>  'no',
      'ibuf_free_cells'             =>  'np',
      'ibuf_cell_count'             =>  'nq',
      'adaptive_hash_memory'        =>  'nr',
      'page_hash_memory'            =>  'ns',
      'dictionary_cache_memory'     =>  'nt',
      'file_system_memory'          =>  'nu',
      'lock_system_memory'          =>  'nv',
      'recovery_system_memory'      =>  'nw',
      'thread_hash_memory'          =>  'nx',
      'innodb_sem_waits'            =>  'ny',
      'innodb_sem_wait_time_ms'     =>  'nz',
      'Key_buf_bytes_unflushed'     =>  'og',
      'Key_buf_bytes_used'          =>  'oh',
      'key_buffer_size'             =>  'oi',
      'Innodb_row_lock_time'        =>  'oj',
      'Innodb_row_lock_waits'       =>  'ok',
      'Query_time_count_00'         =>  'ol',
      'Query_time_count_01'         =>  'om',
      'Query_time_count_02'         =>  'on',
      'Query_time_count_03'         =>  'oo',
      'Query_time_count_04'         =>  'op',
      'Query_time_count_05'         =>  'oq',
      'Query_time_count_06'         =>  'or',
      'Query_time_count_07'         =>  'os',
      'Query_time_count_08'         =>  'ot',
      'Query_time_count_09'         =>  'ou',
      'Query_time_count_10'         =>  'ov',
      'Query_time_count_11'         =>  'ow',
      'Query_time_count_12'         =>  'ox',
      'Query_time_count_13'         =>  'oy',
      'Query_time_total_00'         =>  'oz',
      'Query_time_total_01'         =>  'pg',
      'Query_time_total_02'         =>  'ph',
      'Query_time_total_03'         =>  'pi',
      'Query_time_total_04'         =>  'pj',
      'Query_time_total_05'         =>  'pk',
      'Query_time_total_06'         =>  'pl',
      'Query_time_total_07'         =>  'pm',
      'Query_time_total_08'         =>  'pn',
      'Query_time_total_09'         =>  'po',
      'Query_time_total_10'         =>  'pp',
      'Query_time_total_11'         =>  'pq',
      'Query_time_total_12'         =>  'pr',
      'Query_time_total_13'         =>  'ps',
      'wsrep_replicated_bytes'      =>  'pt',
      'wsrep_received_bytes'        =>  'pu',
      'wsrep_replicated'            =>  'pv',
      'wsrep_received'              =>  'pw',
      'wsrep_local_cert_failures'   =>  'px',
      'wsrep_local_bf_aborts'       =>  'py',
      'wsrep_local_send_queue'      =>  'pz',
      'wsrep_local_recv_queue'      =>  'qg',
      'wsrep_cluster_size'          =>  'qh',
      'wsrep_cert_deps_distance'    =>  'qi',
      'wsrep_apply_window'          =>  'qj',
      'wsrep_commit_window'         =>  'qk',
      'wsrep_flow_control_paused'   =>  'ql',
      'wsrep_flow_control_sent'     =>  'qm',
      'wsrep_flow_control_recv'     =>  'qn',
      'pool_reads'                  =>  'qo',
      'pool_read_requests'          =>  'qp',
   );

   # Return the output.
   $output = array();
   foreach ($keys as $key => $short ) {
      # If the value isn't defined, return -1 which is lower than (most graphs')
      # minimum value of 0, so it'll be regarded as a missing value.
      $val      = isset($status[$key]) ? $status[$key] : -1;
      $output[] = "$short:$val";
   }
   $result = implode(' ', $output);
   if ( $fp ) {
      if ( fwrite($fp, $result) === false ) {
         die("Can't write '$cache_file'");
      }
      fclose($fp);
   }
   return $result;
}

# ============================================================================
# Given INNODB STATUS text, returns a key-value array of the parsed text.  Each
# line shows a sample of the input for both standard InnoDB as you would find in
# MySQL 5.0, and XtraDB or enhanced InnoDB from Percona if applicable.  Note
# that extra leading spaces are ignored due to trim().
# ============================================================================
function get_innodb_array($text) {
   $results  = array(
      'spin_waits'  => array(),
      'spin_rounds' => array(),
      'os_waits'    => array(),
      'pending_normal_aio_reads'  => null,
      'pending_normal_aio_writes' => null,
      'pending_ibuf_aio_reads'    => null,
      'pending_aio_log_ios'       => null,
      'pending_aio_sync_ios'      => null,
      'pending_log_flushes'       => null,
      'pending_buf_pool_flushes'  => null,
      'file_reads'                => null,
      'file_writes'               => null,
      'file_fsyncs'               => null,
      'ibuf_inserts'              => null,
      'ibuf_merged'               => null,
      'ibuf_merges'               => null,
      'log_bytes_written'         => null,
      'unflushed_log'             => null,
      'log_bytes_flushed'         => null,
      'pending_log_writes'        => null,
      'pending_chkp_writes'       => null,
      'log_writes'                => null,
      'pool_size'                 => null,
      'free_pages'                => null,
      'database_pages'            => null,
      'modified_pages'            => null,
      'pages_read'                => null,
      'pages_created'             => null,
      'pages_written'             => null,
      'queries_inside'            => null,
      'queries_queued'            => null,
      'read_views'                => null,
      'rows_inserted'             => null,
      'rows_updated'              => null,
      'rows_deleted'              => null,
      'rows_read'                 => null,
      'innodb_transactions'       => null,
      'unpurged_txns'             => null,
      'history_list'              => null,
      'current_transactions'      => null,
      'hash_index_cells_total'    => null,
      'hash_index_cells_used'     => null,
      'total_mem_alloc'           => null,
      'additional_pool_alloc'     => null,
      'last_checkpoint'           => null,
      'uncheckpointed_bytes'      => null,
      'ibuf_used_cells'           => null,
      'ibuf_free_cells'           => null,
      'ibuf_cell_count'           => null,
      'adaptive_hash_memory'      => null,
      'page_hash_memory'          => null,
      'dictionary_cache_memory'   => null,
      'file_system_memory'        => null,
      'lock_system_memory'        => null,
      'recovery_system_memory'    => null,
      'thread_hash_memory'        => null,
      'innodb_sem_waits'          => null,
      'innodb_sem_wait_time_ms'   => null,
   );
   $txn_seen = false;
   foreach ( explode("\n", $text) as $line ) {
      $line = trim($line);
      $row = preg_split('/ +/', $line);

      # SEMAPHORES
      if (strpos($line, 'Mutex spin waits') === 0 ) {
         # Mutex spin waits 79626940, rounds 157459864, OS waits 698719
         # Mutex spin waits 0, rounds 247280272495, OS waits 316513438
         $results['spin_waits'][]  = to_int($row[3]);
         $results['spin_rounds'][] = to_int($row[5]);
         $results['os_waits'][]    = to_int($row[8]);
      }
      elseif (strpos($line, 'RW-shared spins') === 0
            && strpos($line, ';') > 0 ) {
         # RW-shared spins 3859028, OS waits 2100750; RW-excl spins 4641946, OS waits 1530310
         $results['spin_waits'][] = to_int($row[2]);
         $results['spin_waits'][] = to_int($row[8]);
         $results['os_waits'][]   = to_int($row[5]);
         $results['os_waits'][]   = to_int($row[11]);
      }
      elseif (strpos($line, 'RW-shared spins') === 0 && strpos($line, '; RW-excl spins') === false) {
         # Post 5.5.17 SHOW ENGINE INNODB STATUS syntax
         # RW-shared spins 604733, rounds 8107431, OS waits 241268
         $results['spin_waits'][] = to_int($row[2]);
         $results['os_waits'][]   = to_int($row[7]);
      }
      elseif (strpos($line, 'RW-excl spins') === 0) {
         # Post 5.5.17 SHOW ENGINE INNODB STATUS syntax
         # RW-excl spins 604733, rounds 8107431, OS waits 241268
         $results['spin_waits'][] = to_int($row[2]);
         $results['os_waits'][]   = to_int($row[7]);
      }
      elseif (strpos($line, 'seconds the semaphore:') > 0) {
         # --Thread 907205 has waited at handler/ha_innodb.cc line 7156 for 1.00 seconds the semaphore:
         increment($results, 'innodb_sem_waits', 1);
         increment($results,
            'innodb_sem_wait_time_ms', to_int($row[9]) * 1000);
      }

      # TRANSACTIONS
      elseif ( strpos($line, 'Trx id counter') === 0 ) {
         # The beginning of the TRANSACTIONS section: start counting
         # transactions
         # Trx id counter 0 1170664159
         # Trx id counter 861B144C
         $results['innodb_transactions'] = make_bigint(
            $row[3], (isset($row[4]) ? $row[4] : null));
         $txn_seen = true;
      }
      elseif ( strpos($line, 'Purge done for trx') === 0 ) {
         # Purge done for trx's n:o < 0 1170663853 undo n:o < 0 0
         # Purge done for trx's n:o < 861B135D undo n:o < 0
         $purged_to = make_bigint($row[6], $row[7] == 'undo' ? null : $row[7]);
         $results['unpurged_txns']
            = big_sub($results['innodb_transactions'], $purged_to);
      }
      elseif (strpos($line, 'History list length') === 0 ) {
         # History list length 132
         $results['history_list'] = to_int($row[3]);
      }
      elseif ( $txn_seen && strpos($line, '---TRANSACTION') === 0 ) {
         # ---TRANSACTION 0, not started, process no 13510, OS thread id 1170446656
         increment($results, 'current_transactions', 1);
         if ( strpos($line, 'ACTIVE') > 0 ) {
            increment($results, 'active_transactions', 1);
         }
      }
      elseif ( $txn_seen && strpos($line, '------- TRX HAS BEEN') === 0 ) {
         # ------- TRX HAS BEEN WAITING 32 SEC FOR THIS LOCK TO BE GRANTED:
         increment($results, 'innodb_lock_wait_secs', to_int($row[5]));
      }
      elseif ( strpos($line, 'read views open inside InnoDB') > 0 ) {
         # 1 read views open inside InnoDB
         $results['read_views'] = to_int($row[0]);
      }
      elseif ( strpos($line, 'mysql tables in use') === 0 ) {
         # mysql tables in use 2, locked 2
         increment($results, 'innodb_tables_in_use', to_int($row[4]));
         increment($results, 'innodb_locked_tables', to_int($row[6]));
      }
      elseif ( $txn_seen && strpos($line, 'lock struct(s)') > 0 ) {
         # 23 lock struct(s), heap size 3024, undo log entries 27
         # LOCK WAIT 12 lock struct(s), heap size 3024, undo log entries 5
         # LOCK WAIT 2 lock struct(s), heap size 368
         if ( strpos($line, 'LOCK WAIT') === 0 ) {
            increment($results, 'innodb_lock_structs', to_int($row[2]));
            increment($results, 'locked_transactions', 1);
         }
         else {
            increment($results, 'innodb_lock_structs', to_int($row[0]));
         }
      }

      # FILE I/O
      elseif (strpos($line, ' OS file reads, ') > 0 ) {
         # 8782182 OS file reads, 15635445 OS file writes, 947800 OS fsyncs
         $results['file_reads']  = to_int($row[0]);
         $results['file_writes'] = to_int($row[4]);
         $results['file_fsyncs'] = to_int($row[8]);
      }
      elseif (strpos($line, 'Pending normal aio reads:') === 0 ) {
         # Pending normal aio reads: 0, aio writes: 0,
         $results['pending_normal_aio_reads']  = to_int($row[4]);
         $results['pending_normal_aio_writes'] = to_int($row[7]);
      }
      elseif (strpos($line, 'ibuf aio reads') === 0 ) {
         #  ibuf aio reads: 0, log i/o's: 0, sync i/o's: 0
         $results['pending_ibuf_aio_reads'] = to_int($row[3]);
         $results['pending_aio_log_ios']    = to_int($row[6]);
         $results['pending_aio_sync_ios']   = to_int($row[9]);
      }
      elseif ( strpos($line, 'Pending flushes (fsync)') === 0 ) {
         # Pending flushes (fsync) log: 0; buffer pool: 0
         $results['pending_log_flushes']      = to_int($row[4]);
         $results['pending_buf_pool_flushes'] = to_int($row[7]);
      }

      # INSERT BUFFER AND ADAPTIVE HASH INDEX
      elseif (strpos($line, 'Ibuf for space 0: size ') === 0 ) {
         # Older InnoDB code seemed to be ready for an ibuf per tablespace.  It
         # had two lines in the output.  Newer has just one line, see below.
         # Ibuf for space 0: size 1, free list len 887, seg size 889, is not empty
         # Ibuf for space 0: size 1, free list len 887, seg size 889,
         $results['ibuf_used_cells']  = to_int($row[5]);
         $results['ibuf_free_cells']  = to_int($row[9]);
         $results['ibuf_cell_count']  = to_int($row[12]);
      }
      elseif (strpos($line, 'Ibuf: size ') === 0 ) {
         # Ibuf: size 1, free list len 4634, seg size 4636,
         $results['ibuf_used_cells']  = to_int($row[2]);
         $results['ibuf_free_cells']  = to_int($row[6]);
         $results['ibuf_cell_count']  = to_int($row[9]);
         if (strpos($line, 'merges')) {
            $results['ibuf_merges']  = to_int($row[10]);
         }
      }
      elseif (strpos($line, ', delete mark ') > 0 && strpos($prev_line, 'merged operations:') === 0 ) {
         # Output of show engine innodb status has changed in 5.5
         # merged operations:
         # insert 593983, delete mark 387006, delete 73092
         $results['ibuf_inserts'] = to_int($row[1]);
         $results['ibuf_merged']  = to_int($row[1]) + to_int($row[4]) + to_int($row[6]);
      }
      elseif (strpos($line, ' merged recs, ') > 0 ) {
         # 19817685 inserts, 19817684 merged recs, 3552620 merges
         $results['ibuf_inserts'] = to_int($row[0]);
         $results['ibuf_merged']  = to_int($row[2]);
         $results['ibuf_merges']  = to_int($row[5]);
      }
      elseif (strpos($line, 'Hash table size ') === 0 ) {
         # In some versions of InnoDB, the used cells is omitted.
         # Hash table size 4425293, used cells 4229064, ....
         # Hash table size 57374437, node heap has 72964 buffer(s) <-- no used cells
         $results['hash_index_cells_total'] = to_int($row[3]);
         $results['hash_index_cells_used']
            = strpos($line, 'used cells') > 0 ? to_int($row[6]) : '0';
      }

      # LOG
      elseif (strpos($line, " log i/o's done, ") > 0 ) {
         # 3430041 log i/o's done, 17.44 log i/o's/second
         # 520835887 log i/o's done, 17.28 log i/o's/second, 518724686 syncs, 2980893 checkpoints
         # TODO: graph syncs and checkpoints
         $results['log_writes'] = to_int($row[0]);
      }
      elseif (strpos($line, " pending log writes, ") > 0 ) {
         # 0 pending log writes, 0 pending chkp writes
         $results['pending_log_writes']  = to_int($row[0]);
         $results['pending_chkp_writes'] = to_int($row[4]);
      }
      elseif (strpos($line, "Log sequence number") === 0 ) {
         # This number is NOT printed in hex in InnoDB plugin.
         # Log sequence number 13093949495856 //plugin
         # Log sequence number 125 3934414864 //normal
         $results['log_bytes_written']
            = isset($row[4])
            ? make_bigint($row[3], $row[4])
            : to_int($row[3]);
      }
      elseif (strpos($line, "Log flushed up to") === 0 ) {
         # This number is NOT printed in hex in InnoDB plugin.
         # Log flushed up to   13093948219327
         # Log flushed up to   125 3934414864
         $results['log_bytes_flushed']
            = isset($row[5])
            ? make_bigint($row[4], $row[5])
            : to_int($row[4]);
      }
      elseif (strpos($line, "Last checkpoint at") === 0 ) {
         # Last checkpoint at  125 3934293461
         $results['last_checkpoint']
            = isset($row[4])
            ? make_bigint($row[3], $row[4])
            : to_int($row[3]);
      }

      # BUFFER POOL AND MEMORY
      elseif (strpos($line, "Total memory allocated") === 0 && strpos($line, "in additional pool allocated") > 0 ) {
         # Total memory allocated 29642194944; in additional pool allocated 0
         # Total memory allocated by read views 96
         $results['total_mem_alloc']       = to_int($row[3]);
         $results['additional_pool_alloc'] = to_int($row[8]);
      }
      elseif(strpos($line, 'Adaptive hash index ') === 0 ) {
         #   Adaptive hash index 1538240664 	(186998824 + 1351241840)
         $results['adaptive_hash_memory'] = to_int($row[3]);
      }
      elseif(strpos($line, 'Page hash           ') === 0 ) {
         #   Page hash           11688584
         $results['page_hash_memory'] = to_int($row[2]);
      }
      elseif(strpos($line, 'Dictionary cache    ') === 0 ) {
         #   Dictionary cache    145525560 	(140250984 + 5274576)
         $results['dictionary_cache_memory'] = to_int($row[2]);
      }
      elseif(strpos($line, 'File system         ') === 0 ) {
         #   File system         313848 	(82672 + 231176)
         $results['file_system_memory'] = to_int($row[2]);
      }
      elseif(strpos($line, 'Lock system         ') === 0 ) {
         #   Lock system         29232616 	(29219368 + 13248)
         $results['lock_system_memory'] = to_int($row[2]);
      }
      elseif(strpos($line, 'Recovery system     ') === 0 ) {
         #   Recovery system     0 	(0 + 0)
         $results['recovery_system_memory'] = to_int($row[2]);
      }
      elseif(strpos($line, 'Threads             ') === 0 ) {
         #   Threads             409336 	(406936 + 2400)
         $results['thread_hash_memory'] = to_int($row[1]);
      }
      elseif(strpos($line, 'innodb_io_pattern   ') === 0 ) {
         #   innodb_io_pattern   0 	(0 + 0)
         $results['innodb_io_pattern_memory'] = to_int($row[1]);
      }
      elseif (strpos($line, "Buffer pool size ") === 0 ) {
         # The " " after size is necessary to avoid matching the wrong line:
         # Buffer pool size        1769471
         # Buffer pool size, bytes 28991012864
         $results['pool_size'] = to_int($row[3]);
      }
      elseif (strpos($line, "Free buffers") === 0 ) {
         # Free buffers            0
         $results['free_pages'] = to_int($row[2]);
      }
      elseif (strpos($line, "Database pages") === 0 ) {
         # Database pages          1696503
         $results['database_pages'] = to_int($row[2]);
      }
      elseif (strpos($line, "Modified db pages") === 0 ) {
         # Modified db pages       160602
         $results['modified_pages'] = to_int($row[3]);
      }
      elseif (strpos($line, "Pages read ahead") === 0 ) {
         # Must do this BEFORE the next test, otherwise it'll get fooled by this
         # line from the new plugin (see samples/innodb-015.txt):
         # Pages read ahead 0.00/s, evicted without access 0.06/s
         # TODO: No-op for now, see issue 134.
      }
      elseif (strpos($line, "Pages read") === 0 ) {
         # Pages read 15240822, created 1770238, written 21705836
         $results['pages_read']    = to_int($row[2]);
         $results['pages_created'] = to_int($row[4]);
         $results['pages_written'] = to_int($row[6]);
      }

      # ROW OPERATIONS
      elseif (strpos($line, 'Number of rows inserted') === 0 ) {
         # Number of rows inserted 50678311, updated 66425915, deleted 20605903, read 454561562
         $results['rows_inserted'] = to_int($row[4]);
         $results['rows_updated']  = to_int($row[6]);
         $results['rows_deleted']  = to_int($row[8]);
         $results['rows_read']     = to_int($row[10]);
      }
      elseif (strpos($line, " queries inside InnoDB, ") > 0 ) {
         # 0 queries inside InnoDB, 0 queries in queue
         $results['queries_inside'] = to_int($row[0]);
         $results['queries_queued'] = to_int($row[4]);
      }
      $prev_line = $line;
   }

   foreach ( array('spin_waits', 'spin_rounds', 'os_waits') as $key ) {
      $results[$key] = to_int(array_sum($results[$key]));
   }
   $results['unflushed_log']
      = big_sub($results['log_bytes_written'], $results['log_bytes_flushed']);
   $results['uncheckpointed_bytes']
      = big_sub($results['log_bytes_written'], $results['last_checkpoint']);

   return $results;
}


# ============================================================================
# Returns a bigint from two ulint or a single hex number.  This is tested in
# t/mysql_stats.php and copied, without tests, to ss_get_by_ssh.php.
# ============================================================================
function make_bigint ($hi, $lo = null) {
   debug(array($hi, $lo));
   if ( is_null($lo) ) {
      # Assume it is a hex string representation.
      return base_convert($hi, 16, 10);
   }
   else {
      $hi = $hi ? $hi : '0'; # Handle empty-string or whatnot
      $lo = $lo ? $lo : '0';
      return big_add(big_multiply($hi, 4294967296), $lo);
   }
}

# ============================================================================
# Extracts the numbers from a string.  You can't reliably do this by casting to
# an int, because numbers that are bigger than PHP's int (varies by platform)
# will be truncated.  And you can't use sprintf(%u) either, because the maximum
# value that will return on some platforms is 4022289582.  So this just handles
# them as a string instead.  It extracts digits until it finds a non-digit and
# quits.  This is tested in t/mysql_stats.php and copied, without tests, to
# ss_get_by_ssh.php.
# ============================================================================
function to_int ( $str ) {
   debug($str);
   global $debug;
   preg_match('{(\d+)}', $str, $m);
   if ( isset($m[1]) ) {
      return $m[1];
   }
   elseif ( $debug ) {
      print_r(debug_backtrace());
   }
   else {
      return 0;
   }
}

# ============================================================================
# Wrap mysqli_query in error-handling, and instead of returning the result,
# return an array of arrays in the result.
# ============================================================================
function run_query($sql, $conn) {
   global $debug;
   debug($sql);
   $result = @mysqli_query($conn, $sql);
   if ( $debug && strpos($sql, 'SHOW SLAVE STATUS ') === false ) {
      $error = @mysqli_error($conn);
      if ( $error ) {
         debug(array($sql, $error));
         die("SQLERR $error in $sql");
      }
   }
   $array = array();
   $count = @mysqli_num_rows($result);
   if ( $count > 10000 ) {
      debug('Abnormal number of rows returned: ' . $count);
   }
   else {
      while ( $row = @mysqli_fetch_array($result) ) {
         $array[] = $row;
      }
   }
   debug(array($sql, $array));
   return $array;
}

# ============================================================================
# Safely increments a value that might be null.
# ============================================================================
function increment(&$arr, $key, $howmuch) {
   debug(array($key, $howmuch));
   if ( array_key_exists($key, $arr) && isset($arr[$key]) ) {
      $arr[$key] = big_add($arr[$key], $howmuch);
   }
   else {
      $arr[$key] = $howmuch;
   }
}

# ============================================================================
# Multiply two big integers together as accurately as possible with reasonable
# effort.  This is tested in t/mysql_stats.php and copied, without tests, to
# ss_get_by_ssh.php.  $force is for testability.
# ============================================================================
function big_multiply ($left, $right, $force = null) {
   if ( function_exists("gmp_mul") && (is_null($force) || $force == 'gmp') ) {
      debug(array('gmp_mul', $left, $right));
      return gmp_strval( gmp_mul( $left, $right ));
   }
   elseif ( function_exists("bcmul") && (is_null($force) || $force == 'bc') ) {
      debug(array('bcmul', $left, $right));
      return bcmul( $left, $right );
   }
   else { # Or $force == 'something else'
      debug(array('sprintf', $left, $right));
      return sprintf("%.0f", $left * $right);
   }
}

# ============================================================================
# Subtract two big integers as accurately as possible with reasonable effort.
# This is tested in t/mysql_stats.php and copied, without tests, to
# ss_get_by_ssh.php.  $force is for testability.
# ============================================================================
function big_sub ($left, $right, $force = null) {
   debug(array($left, $right));
   if ( is_null($left)  ) { $left = 0; }
   if ( is_null($right) ) { $right = 0; }
   if ( function_exists("gmp_sub") && (is_null($force) || $force == 'gmp')) {
      debug(array('gmp_sub', $left, $right));
      return gmp_strval( gmp_sub( $left, $right ));
   }
   elseif ( function_exists("bcsub") && (is_null($force) || $force == 'bc')) {
      debug(array('bcsub', $left, $right));
      return bcsub( $left, $right );
   }
   else { # Or $force == 'something else'
      debug(array('to_int', $left, $right));
      return to_int($left - $right);
   }
}

# ============================================================================
# Add two big integers together as accurately as possible with reasonable
# effort.  This is tested in t/mysql_stats.php and copied, without tests, to
# ss_get_by_ssh.php.  $force is for testability.
# ============================================================================
function big_add ($left, $right, $force = null) {
   if ( is_null($left)  ) { $left = 0; }
   if ( is_null($right) ) { $right = 0; }
   if ( function_exists("gmp_add") && (is_null($force) || $force == 'gmp')) {
      debug(array('gmp_add', $left, $right));
      return gmp_strval( gmp_add( $left, $right ));
   }
   elseif ( function_exists("bcadd") && (is_null($force) || $force == 'bc')) {
      debug(array('bcadd', $left, $right));
      return bcadd( $left, $right );
   }
   else { # Or $force == 'something else'
      debug(array('to_int', $left, $right));
      return to_int($left + $right);
   }
}

# ============================================================================
# Writes to a debugging log.
# ============================================================================
function debug($val) {
   global $debug_log;
   if ( !$debug_log ) {
      return;
   }
   if ( $fp = fopen($debug_log, 'a+') ) {
      $trace = debug_backtrace();
      $calls = array();
      $i    = 0;
      $line = 0;
      $file = '';
      foreach ( debug_backtrace() as $arr ) {
         if ( $i++ ) {
            $calls[] = "$arr[function]() at $file:$line";
         }
         $line = array_key_exists('line', $arr) ? $arr['line'] : '?';
         $file = array_key_exists('file', $arr) ? $arr['file'] : '?';
      }
      if ( !count($calls) ) {
         $calls[] = "at $file:$line";
      }
      fwrite($fp, date('Y-m-d H:i:s') . ' ' . implode(' <- ', $calls));
      fwrite($fp, "\n" . var_export($val, true) . "\n");
      fclose($fp);
   }
   else { # Disable logging
      print("Warning: disabling debug logging to $debug_log\n");
      $debug_log = false;
   }
}

