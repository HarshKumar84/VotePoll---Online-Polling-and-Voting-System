<?php
// Include database configuration
require_once 'includes/config.php';

// Default values for pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; // Polls per page
$offset = ($page - 1) * $limit;
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';

// Build query based on filters
$whereClause = '';
$orderClause = '';

if (!empty($category) && $category != 'all') {
    $whereClause .= " WHERE category = '$category'";
} else {
    $whereClause .= " WHERE 1=1";
}

if (!empty($search)) {
    $whereClause .= " AND (title LIKE '%$search%' OR description LIKE '%$search%')";
}

// Determine sort order
switch ($sort) {
    case 'popular':
        $orderClause = " ORDER BY (SELECT COUNT(*) FROM votes WHERE votes.poll_id = polls.id) DESC";
        break;
    case 'active':
        $orderClause = " ORDER BY (SELECT MAX(created_at) FROM votes WHERE votes.poll_id = polls.id) DESC";
        break;
    case 'newest':
    default:
        $orderClause = " ORDER BY created_at DESC";
        break;
}

// Count total polls for pagination
$countQuery = "SELECT COUNT(*) as total FROM polls" . $whereClause;
$countResult = mysqli_query($conn, $countQuery);
$totalPolls = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalPolls / $limit);

// Get polls for current page
$query = "SELECT p.*, 
        (SELECT COUNT(*) FROM votes WHERE poll_id = p.id) as vote_count,
        u.first_name, u.last_name
    FROM polls p
    JOIN users u ON p.user_id = u.id
    $whereClause
    $orderClause
    LIMIT $offset, $limit";

$result = mysqli_query($conn, $query);

// Store polls in array
$polls = [];
while ($row = mysqli_fetch_assoc($result)) {
    $polls[] = $row;
}

// Get all categories for filter dropdown
$categoryQuery = "SELECT DISTINCT category FROM polls";
$categoryResult = mysqli_query($conn, $categoryQuery);
$categories = [];
while ($row = mysqli_fetch_assoc($categoryResult)) {
    $categories[] = $row['category'];
}

// Pass data to the view
$data = [
    'polls' => $polls,
    'categories' => $categories,
    'currentCategory' => $category,
    'currentSearch' => $search,
    'currentSort' => $sort,
    'currentPage' => $page,
    'totalPages' => $totalPages
];

// Load the polls page HTML with data
include 'polls.html';
?> 