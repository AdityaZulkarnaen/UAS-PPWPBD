<?php
/**
 * Cron Job Script for Job Alerts
 * Run this script periodically to send job alerts to users
 * 
 * Usage: php cron_job_alerts.php
 * Add to crontab: 0 9 * * * php /path/to/your/project/cron_job_alerts.php
 */

require_once 'config.php';
require_once 'src/includes/profile_helper.php';

// Set time limit for long running script
set_time_limit(300); // 5 minutes

echo "[" . date('Y-m-d H:i:s') . "] Starting Job Alerts Cron Job...\n";

try {
    // Process job alerts
    $processed = process_job_alerts();
    
    echo "[" . date('Y-m-d H:i:s') . "] Job Alerts processed: $processed\n";
    
    // Log to file
    $log_message = "[" . date('Y-m-d H:i:s') . "] Job Alerts Cron Job completed. Processed: $processed alerts\n";
    file_put_contents('logs/job_alerts_cron.log', $log_message, FILE_APPEND | LOCK_EX);
    
    echo "[" . date('Y-m-d H:i:s') . "] Job Alerts Cron Job completed successfully!\n";
    
} catch (Exception $e) {
    $error_message = "[" . date('Y-m-d H:i:s') . "] ERROR in Job Alerts Cron Job: " . $e->getMessage() . "\n";
    echo $error_message;
    
    // Log error
    file_put_contents('logs/job_alerts_error.log', $error_message, FILE_APPEND | LOCK_EX);
    
    exit(1);
}

/**
 * Additional function to clean up old notifications
 */
function cleanup_old_notifications() {
    global $pdo;
    
    // Delete notifications older than 3 months
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 MONTH)");
    $deleted = $stmt->execute();
    
    $count = $stmt->rowCount();
    echo "[" . date('Y-m-d H:i:s') . "] Cleaned up $count old notifications\n";
    
    return $count;
}

// Optional: Clean up old notifications
if (isset($argv[1]) && $argv[1] === '--cleanup') {
    cleanup_old_notifications();
}
?>
