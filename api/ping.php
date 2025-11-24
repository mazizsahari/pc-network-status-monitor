<?php
header('Content-Type: application/json');

// Get IP
$ip = $_GET['ip'] ?? '';

// Validate IP
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    echo json_encode(['status' => 'DOWN', 'error' => 'Invalid IP']);
    exit;
}

// Determine OS
$isWindows = stripos(PHP_OS, 'WIN') === 0;

// Build ping command
if ($isWindows) {
    // Windows
    $command = "ping -n 1 -w 1000 " . escapeshellarg($ip);
} else {
    // Linux
    $command = "ping -c 1 -W 1 " . escapeshellarg($ip);
}

// Execute ping using shell_exec()
$output = shell_exec($command);

// If shell_exec returns null, something failed
if ($output === null) {
    echo json_encode(['status' => 'DOWN']);
    exit;
}

// Detect "alive" based on output
if ($isWindows) {
    // Windows: successful ping contains "TTL="
    $isUp = strpos($output, "TTL=") !== false;
} else {
    // Linux: successful ping contains "1 received"
    $isUp = preg_match('/1 received|1 packets received/', $output);
}

// Return result
echo json_encode([
    'status' => $isUp ? 'UP' : 'DOWN'
]);
