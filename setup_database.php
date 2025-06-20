<?php
/**
 * HireWay Database Setup Script
 * Run this script to setup the complete database
 */

echo "=== HireWay Database Setup ===\n\n";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hireway';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to MySQL server\n";
    
    // Read and execute the SQL file
    $sql_file = __DIR__ . '/database/hireway_complete.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    echo "✓ Found database file\n";
    
    $sql_content = file_get_contents($sql_file);
      // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)), 
        'strlen'
    );
    
    echo "✓ Parsed SQL statements (" . count($statements) . " statements)\n";
    echo "⏳ Executing database setup...\n\n";
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Skip USE database statements and comments
            if (stripos($statement, 'USE ') === 0 || 
                stripos($statement, '--') === 0 || 
                stripos($statement, '/*') === 0) {
                continue;
            }
            echo "⚠️  Warning: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Database setup completed successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🎯 Database: $database\n";
    echo "📧 Admin Email: admin@hireway.com\n";
    echo "🔑 Admin Password: password\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "⚠️  IMPORTANT: Change admin password after first login!\n";
    echo "🗑️  You can delete this setup file after installation.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
