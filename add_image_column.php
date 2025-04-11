<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "poll_system");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// SQL to add image_url column if it doesn't exist
$sql = "SHOW COLUMNS FROM polls LIKE 'image_url'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, so create it
    $sql = "ALTER TABLE polls ADD COLUMN image_url VARCHAR(255)";
    if ($conn->query($sql) === TRUE) {
        echo "Column image_url added successfully";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column image_url already exists";
}

// Close connection
mysqli_close($conn);
?> 