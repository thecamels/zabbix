<?php

define('RABBITMQCTL_BIN', '/usr/sbin/rabbitmqctl 2>/dev/null');

$results = array();
$cleanStats = array();
$matches = array();

$stats = array();
$stats['queues'] = shell_exec(RABBITMQCTL_BIN . ' list_queues name durable auto_delete messages_ready messages_unacknowledged messages consumers memory');
$stats['exchanges'] = shell_exec(RABBITMQCTL_BIN . ' list_exchanges type durable auto_delete');
$stats['bindings'] = shell_exec(RABBITMQCTL_BIN . ' list_bindings source_kind destination_kind');
$stats['connections'] = shell_exec(RABBITMQCTL_BIN . ' list_connections state channels protocol recv_oct send_oct');
$stats['channels'] = shell_exec(RABBITMQCTL_BIN . ' list_channels transactional confirm consumer_count messages_unacknowledged messages_uncommitted acks_uncommitted messages_unconfirmed');

foreach ($stats as $name => $statusString) {
    $statusString = str_replace("\t", " ", $statusString);
    $statusString = trim(str_replace("\r", " ", $statusString));
    $stats[$name] = preg_replace('/  +/', ' ', $statusString);
    $stats[$name] = explode("\n", $statusString);

    foreach ($stats[$name] as $index => $value) {
        if (strpos($value, '...') === false && !empty($value)) {
            $cleanStats[$name][$index] = $value;
        }
    }
}

// QUEUES
if (isset($cleanStats['queues'])) {
    foreach ($cleanStats['queues'] as $line) {
        $columns = explode(' ', $line);
        if (count($columns) == 8) {
            $row = array(
                'queues_count' => 1,
                'queues_durable_count' => $columns[1] == 'true' ? 1 : 0,
                'queues_auto_delete_count' => $columns[2] == 'true' ? 1 : 0,
                'messages_ready_count' => $columns[3],
                'messages_unacknowledged_count' => $columns[4],
                'messages_count' => $columns[5],
                'queue_consumers_count' => $columns[6],
                'queues_memory_allocated' => $columns[7],
            );
            $results[] = $row;
        }
    }
}
$results[] = array(
    'queues_count' => 0,
    'queues_durable_count' => 0,
    'queues_auto_delete_count' => 0,
    'messages_ready_count' => 0,
    'messages_unacknowledged_count' => 0,
    'messages_count' => 0,
    'queue_consumers_count' => 0,
    'queues_memory_allocated' => 0,
);


// EXCHANGES
if (isset($cleanStats['exchanges'])) {
    foreach ($cleanStats['exchanges'] as $line) {
        $columns = explode(' ', $line);
        if (count($columns) == 3) {
            $row = array(
                'exchanges_count' => 1,
                'exchanges_durable_count' => $columns[1] == 'true' ? 1 : 0,
                'exchanges_auto_delete_count' => $columns[2] == 'true' ? 1 : 0,
                'exchanges_direct_count' => $columns[0] == 'direct' ? 1 : 0,
                'exchanges_topic_count' => $columns[0] == 'topic' ? 1 : 0,
                'exchanges_fanout_count' => $columns[0] == 'fanout' ? 1 : 0,
                'exchanges_headers_count' => $columns[0] == 'headers' ? 1 : 0,
            );
            $results[] = $row;
        }
    }
}
$results[] = array(
    'exchanges_count' => 0,
    'exchanges_durable_count' => 0,
    'exchanges_auto_delete_count' => 0,
    'exchanges_direct_count' => 0,
    'exchanges_topic_count' => 0,
    'exchanges_fanout_count' => 0,
    'exchanges_headers_count' => 0,
);

// CONNECTIONS
if (isset($cleanStats['connections'])) {
    foreach ($cleanStats['connections'] as $line) {
        $columns = explode(' ', $line);
        if (count($columns) == 5) {
            $row = array(
                'connections_count' => 1,
                'connections_starting' => $columns[0] == 'starting' ? 1 : 0,
                'connections_tuning' => $columns[0] == 'tuning' ? 1 : 0,
                'connections_opening' => $columns[0] == 'opening' ? 1 : 0,
                'connections_running' => $columns[0] == 'running' ? 1 : 0,
                'connections_blocking' => $columns[0] == 'blocking' ? 1 : 0,
                'connections_blocked' => $columns[0] == 'blocked' ? 1 : 0,
                'connections_closing' => $columns[0] == 'closing' ? 1 : 0,
                'connections_closed' => $columns[0] == 'closed' ? 1 : 0,
            );
            $results[] = $row;
        }
    }
}
$results[] = array(
    'connections_count' => 0,
    'connections_starting' => 0,
    'connections_tuning' => 0,
    'connections_opening' => 0,
    'connections_running' => 0,
    'connections_blocking' => 0,
    'connections_blocked' => 0,
    'connections_closing' => 0,
    'connections_closed' => 0,
);

// CHANNELS
if (isset($cleanStats['channels'])) {
    foreach ($cleanStats['channels'] as $line) {
        $columns = explode(' ', $line);
        if (count($columns) == 2) {
            $row = array(
                'channels_count' => 1,
                'channels_transactional_count' => $columns[0] == 'true' ? 1 : 0,
                'channels_confirm_count' => $columns[1] == 'true' ? 1 : 0,
            );
            $results[] = $row;
        }
    }
}
$results[] = array(
    'channels_count' => 0,
    'channels_transactional_count' => 0,
    'channels_confirm_count' => 0,
);

// BINDINGS
$results[] = array('bindings_count' => count($cleanStats['exchanges']));

//SUMMARU
$summary = array();
foreach ($results as $index => $values) {
    foreach ($values as $name => $value) {
        if (!isset($summary[$name])) {
            $summary[$name] = 0;
        }
        $summary[$name] += $value;
    }
}

// PRINT
foreach ($summary as $name => $value) {
    echo $name . ':' . $value . "\n";
}