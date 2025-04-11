<?php
$conn = new mysqli("localhost", "root", "", "poll_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if image_url column exists
$columnCheck = $conn->query("SHOW COLUMNS FROM polls LIKE 'image_url'");
if($columnCheck->num_rows == 0) {
    // Column doesn't exist, create it
    if($conn->query("ALTER TABLE polls ADD COLUMN image_url LONGTEXT")) {
        echo "Successfully added image_url column to polls table.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'image_url' already exists in polls table.";
}

$conn->close();
?> 