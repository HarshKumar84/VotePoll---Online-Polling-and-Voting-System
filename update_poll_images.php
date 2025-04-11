<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "poll_system");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Category images mapping
$categoryImages = [
    'technology' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600',
    'food' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?q=80&w=600',
    'entertainment' => 'https://images.unsplash.com/photo-1603190287605-e6ade32fa852?q=80&w=600',
    'sports' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?q=80&w=600',
    'politics' => 'https://images.unsplash.com/photo-1575320181282-9afab399332c?q=80&w=600',
    'education' => 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?q=80&w=600',
    'health' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?q=80&w=600',
    'business' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?q=80&w=600',
    'other' => 'https://images.unsplash.com/photo-1523961131990-5ea7c61b2107?q=80&w=600'
];

// Default image for categories not in the mapping
$defaultImage = 'https://images.unsplash.com/photo-1523961131990-5ea7c61b2107?q=80&w=600';

// Get all polls that don't have an image_url yet
$sql = "SELECT id, category FROM polls WHERE image_url IS NULL OR image_url = ''";
$result = $conn->query($sql);

$updatedCount = 0;
if ($result->num_rows > 0) {
    echo "Updating " . $result->num_rows . " polls with image URLs...<br>";
    
    while($row = $result->fetch_assoc()) {
        $pollId = $row['id'];
        $category = $row['category'];
        
        // Get appropriate image URL based on category
        $imageUrl = isset($categoryImages[$category]) ? $categoryImages[$category] : $defaultImage;
        
        // Update the poll with the image URL
        $updateSql = "UPDATE polls SET image_url = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $imageUrl, $pollId);
        
        if ($stmt->execute()) {
            $updatedCount++;
        } else {
            echo "Error updating poll ID $pollId: " . $stmt->error . "<br>";
        }
        
        $stmt->close();
    }
    
    echo "Successfully updated $updatedCount polls.";
} else {
    echo "No polls need image URL updates.";
}

mysqli_close($conn);
?> 