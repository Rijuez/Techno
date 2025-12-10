<?php
/**
 * DoughMain Bakery Setup Diagnostic Tool
 * Run this file to check if everything is configured correctly
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>DoughMain Bakery Setup Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #FF6B35; }
        .check { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table td, table th { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #FF6B35; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #FF6B35; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .btn:hover { background: #FF5722; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍞 DoughMain Bakery Setup Checker</h1>
        
        <?php
        // Database connection
        require_once 'config/database.php';
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            echo '<div class="check success">✅ <strong>Database Connection:</strong> Success</div>';
            
            // Check if bakeries table has the new columns
            echo '<h2>1. Checking Database Schema</h2>';
            
            $query = "SHOW COLUMNS FROM bakeries LIKE 'username'";
            $stmt = $db->query($query);
            if ($stmt->rowCount() > 0) {
                echo '<div class="check success">✅ Column <code>username</code> exists in bakeries table</div>';
            } else {
                echo '<div class="check error">❌ Column <code>username</code> NOT FOUND. You need to import bakery_user_system.sql</div>';
            }
            
            $query = "SHOW COLUMNS FROM bakeries LIKE 'password'";
            $stmt = $db->query($query);
            if ($stmt->rowCount() > 0) {
                echo '<div class="check success">✅ Column <code>password</code> exists in bakeries table</div>';
            } else {
                echo '<div class="check error">❌ Column <code>password</code> NOT FOUND. You need to import bakery_user_system.sql</div>';
            }
            
            $query = "SHOW COLUMNS FROM bakeries LIKE 'is_verified'";
            $stmt = $db->query($query);
            if ($stmt->rowCount() > 0) {
                echo '<div class="check success">✅ Column <code>is_verified</code> exists in bakeries table</div>';
            } else {
                echo '<div class="check error">❌ Column <code>is_verified</code> NOT FOUND. You need to import bakery_user_system.sql</div>';
            }
            
            // Check if stored procedure exists
            echo '<h2>2. Checking Stored Procedures</h2>';
            $query = "SHOW PROCEDURE STATUS WHERE Name = 'sp_bakery_login'";
            $stmt = $db->query($query);
            if ($stmt->rowCount() > 0) {
                echo '<div class="check success">✅ Stored procedure <code>sp_bakery_login</code> exists</div>';
            } else {
                echo '<div class="check error">❌ Stored procedure <code>sp_bakery_login</code> NOT FOUND</div>';
            }
            
            // Check if bakery accounts exist
            echo '<h2>3. Checking Bakery Accounts</h2>';
            $query = "SELECT bakery_id, name, username, is_active, is_verified FROM bakeries WHERE username IS NOT NULL";
            $stmt = $db->query($query);
            
            if ($stmt->rowCount() > 0) {
                echo '<div class="check success">✅ Found ' . $stmt->rowCount() . ' bakery account(s)</div>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Username</th><th>Active</th><th>Verified</th></tr>';
                while ($row = $stmt->fetch()) {
                    $active = $row['is_active'] ? '✅' : '❌';
                    $verified = $row['is_verified'] ? '✅' : '❌';
                    echo "<tr>";
                    echo "<td>{$row['bakery_id']}</td>";
                    echo "<td>{$row['name']}</td>";
                    echo "<td><code>{$row['username']}</code></td>";
                    echo "<td>{$active}</td>";
                    echo "<td>{$verified}</td>";
                    echo "</tr>";
                }
                echo '</table>';
            } else {
                echo '<div class="check error">❌ No bakery accounts found with usernames</div>';
                echo '<div class="check warning">⚠️ Run this SQL to create test accounts:<br><br>';
                echo '<code style="display:block; padding:10px; background:#f8f9fa; white-space: pre-wrap;">';
                echo "UPDATE bakeries SET username = 'golden_bakery', password = 'password123', is_verified = 1 WHERE bakery_id = 1;\n";
                echo "UPDATE bakeries SET username = 'sunrise_bakery', password = 'password123', is_verified = 1 WHERE bakery_id = 2;";
                echo '</code></div>';
            }
            
            // Check if bakery_activity_log table exists
            echo '<h2>4. Checking Additional Tables</h2>';
            $query = "SHOW TABLES LIKE 'bakery_activity_log'";
            $stmt = $db->query($query);
            if ($stmt->rowCount() > 0) {
                echo '<div class="check success">✅ Table <code>bakery_activity_log</code> exists</div>';
            } else {
                echo '<div class="check error">❌ Table <code>bakery_activity_log</code> NOT FOUND</div>';
            }
            
            // Check file structure
            echo '<h2>5. Checking File Structure</h2>';
            
            $files = [
                'api/bakery.php' => 'Bakery API Router',
                'Controllers/BakeryAuthController.php' => 'Bakery Auth Controller',
                'Controllers/BakeryProductController.php' => 'Bakery Product Controller',
                'js/bakery-api.js' => 'Bakery API JavaScript',
                'js/bakery-app.js' => 'Bakery App JavaScript',
                'bakery.html' => 'Bakery Interface'
            ];
            
            foreach ($files as $file => $description) {
                if (file_exists($file)) {
                    echo "<div class='check success'>✅ <strong>{$description}:</strong> {$file}</div>";
                } else {
                    echo "<div class='check error'>❌ <strong>{$description}:</strong> {$file} NOT FOUND</div>";
                }
            }
            
            // Check uploads directory
            echo '<h2>6. Checking Upload Directory</h2>';
            if (file_exists('uploads/products')) {
                if (is_writable('uploads/products')) {
                    echo '<div class="check success">✅ Directory <code>uploads/products</code> exists and is writable</div>';
                } else {
                    echo '<div class="check warning">⚠️ Directory <code>uploads/products</code> exists but is NOT writable<br>';
                    echo 'Run: <code>chmod 777 uploads/products</code></div>';
                }
            } else {
                echo '<div class="check error">❌ Directory <code>uploads/products</code> NOT FOUND<br>';
                echo 'Run: <code>mkdir -p uploads/products && chmod 777 uploads/products</code></div>';
            }
            
            // Test bakery login
            echo '<h2>7. Testing Bakery Login</h2>';
            echo '<div class="check info">ℹ️ Testing login with golden_bakery...</div>';
            
            $query = "SELECT bakery_id, name, username, password, is_active, is_verified FROM bakeries WHERE username = 'golden_bakery'";
            $stmt = $db->query($query);
            
            if ($stmt->rowCount() > 0) {
                $bakery = $stmt->fetch();
                
                echo '<table>';
                echo '<tr><th>Field</th><th>Value</th></tr>';
                echo "<tr><td>Bakery ID</td><td>{$bakery['bakery_id']}</td></tr>";
                echo "<tr><td>Name</td><td>{$bakery['name']}</td></tr>";
                echo "<tr><td>Username</td><td>{$bakery['username']}</td></tr>";
                echo "<tr><td>Password</td><td>{$bakery['password']}</td></tr>";
                echo "<tr><td>Active</td><td>" . ($bakery['is_active'] ? 'Yes' : 'No') . "</td></tr>";
                echo "<tr><td>Verified</td><td>" . ($bakery['is_verified'] ? 'Yes' : 'No') . "</td></tr>";
                echo '</table>';
                
                if (!$bakery['is_active']) {
                    echo '<div class="check error">❌ Bakery is INACTIVE. Run:<br><code>UPDATE bakeries SET is_active = 1 WHERE username = \'golden_bakery\';</code></div>';
                }
                if (!$bakery['is_verified']) {
                    echo '<div class="check error">❌ Bakery is NOT VERIFIED. Run:<br><code>UPDATE bakeries SET is_verified = 1 WHERE username = \'golden_bakery\';</code></div>';
                }
                if ($bakery['password'] !== 'password123') {
                    echo '<div class="check warning">⚠️ Password does not match expected value. Run:<br><code>UPDATE bakeries SET password = \'password123\' WHERE username = \'golden_bakery\';</code></div>';
                }
                
                if ($bakery['is_active'] && $bakery['is_verified'] && $bakery['password'] === 'password123') {
                    echo '<div class="check success">✅ golden_bakery account is properly configured!</div>';
                }
            } else {
                echo '<div class="check error">❌ golden_bakery account NOT FOUND</div>';
            }
            
            echo '<h2>Summary</h2>';
            echo '<div class="check info">';
            echo '<strong>If you see any errors above:</strong><br><br>';
            echo '1. Make sure you imported <code>bakery_user_system.sql</code><br>';
            echo '2. Make sure all files are in the correct locations<br>';
            echo '3. Create the <code>uploads/products</code> directory<br>';
            echo '4. Run the SQL commands shown above to fix accounts<br><br>';
            echo '<a href="bakery.html" class="btn">Go to Bakery Login</a>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="check error">❌ <strong>Database Error:</strong> ' . $e->getMessage() . '</div>';
            echo '<div class="check warning">⚠️ Make sure your database credentials are correct in <code>config/database.php</code></div>';
        }
        ?>
        
        <hr style="margin: 30px 0;">
        <p style="text-align: center; color: #666;">
            DoughMain Bakery Management System - Setup Checker<br>
            <small>If issues persist, check the BAKERY_README.md file</small>
        </p>
    </div>
</body>
</html>