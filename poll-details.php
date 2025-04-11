<?php
session_start();
$conn = new mysqli("localhost", "root", "", "poll_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT title, category, options, description, created_at, anonymous_voting, require_login, image_url FROM polls WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $poll_id);
$stmt->execute();
$result = $stmt->get_result();
$poll = $result->fetch_assoc();

if (!$poll) {
    die("Poll not found!");
}

$options = json_decode($poll['options'], true);
$vote_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM votes WHERE poll_id = $poll_id"))['total'];

// Fetch username if user is logged in
$username = "";
$user_logged_in = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_logged_in = true;
    $sql_user = "SELECT first_name FROM CA WHERE id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($row_user = $result_user->fetch_assoc()) {
        $username = htmlspecialchars($row_user['first_name']);
    }
    $stmt_user->close();
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['poll-option'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // User is not logged in, set error message
        $login_error = "Please login to vote in this poll.";
    } else {
        $option_index = (int)$_POST['poll-option'];
        $user_id = $_SESSION['user_id'];
        
        // Check if user has already voted on this poll
        $check_vote_sql = "SELECT * FROM votes WHERE poll_id = ? AND user_id = ?";
        $check_vote_stmt = $conn->prepare($check_vote_sql);
        $check_vote_stmt->bind_param("ii", $poll_id, $user_id);
        $check_vote_stmt->execute();
        $check_result = $check_vote_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // User has already voted
            $vote_error = "You have already voted in this poll.";
        } else {
            // Insert vote (without voter_name as it doesn't exist in the table)
            $vote_sql = "INSERT INTO votes (poll_id, option_index, user_id) VALUES (?, ?, ?)";
            $vote_stmt = $conn->prepare($vote_sql);
            $vote_stmt->bind_param("iii", $poll_id, $option_index, $user_id);
            $vote_stmt->execute();
            $vote_stmt->close();
            header("Location: poll-details.php?id=$poll_id&voted=1");
            exit();
        }
        $check_vote_stmt->close();
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
    // Use the logged in username if available, otherwise use 'Anonymous'
    $comment_username = $user_logged_in ? $username : 'Anonymous';
    $comment = $conn->real_escape_string($_POST['comment']);
    $comment_sql = "INSERT INTO poll_comments (poll_id, username, comment) VALUES (?, ?, ?)";
    $comment_stmt = $conn->prepare($comment_sql);
    $comment_stmt->bind_param("iss", $poll_id, $comment_username, $comment);
    $comment_stmt->execute();
    $comment_stmt->close();
    header("Location: poll-details.php?id=$poll_id#comments-section");
    exit();
}

// Fetch comments
$comments_sql = "SELECT username, comment, created_at FROM poll_comments WHERE poll_id = ? ORDER BY created_at DESC LIMIT 3";
$comments_stmt = $conn->prepare($comments_sql);
$comments_stmt->bind_param("i", $poll_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $comments[] = $row;
}
$comment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM poll_comments WHERE poll_id = $poll_id"))['total'];

$voted = isset($_GET['voted']) ? true : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($poll['title']); ?> - VotePoll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="animations.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Add metadata for social sharing -->
    <meta property="og:title" content="<?php echo htmlspecialchars($poll['title']); ?> - VotePoll">
    <meta property="og:description" content="<?php echo htmlspecialchars($poll['description'] ?? 'Vote in this poll and share your opinion!'); ?>">
    <meta property="og:url" content="<?php echo "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"; ?>">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-black text-white shadow-lg fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold flex items-center wobble">
                <i class="fas fa-poll mr-2"></i> VotePoll
            </a>
            <div class="flex space-x-4 items-center">
                <a href="index.php" class="hover:text-gray-300 text-underline">Home</a>
                <a href="pollresult.php" class="hover:text-gray-300 text-underline">Browse Polls</a>
                <a href="create-poll.php" class="hover:text-gray-300 text-underline">Create Poll</a>
                <?php if (empty($username)): ?>
                    <a href="loginh.php" class="bg-white text-black hover:bg-gray-100 px-4 py-2 rounded-lg font-semibold transition duration-300 button-hover">Login</a>
                    <a href="signuph.php" class="bg-gray-800 hover:bg-gray-900 px-4 py-2 rounded-lg font-semibold transition duration-300 button-hover">Signup</a>
                <?php else: ?>
                    <span class="text-white font-semibold">Welcome, <?php echo $username; ?></span>
                    <a href="logout.php" class="bg-white text-black hover:bg-gray-100 px-4 py-2 rounded-lg font-semibold transition duration-300 button-hover">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8 mt-20">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 fade-in">
                <div class="bg-indigo-700 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="bg-indigo-500 text-xs font-medium px-2.5 py-0.5 rounded"><?php echo htmlspecialchars($poll['category']); ?></span>
                            <h1 class="text-2xl md:text-3xl font-bold mt-2"><?php echo htmlspecialchars($poll['title']); ?></h1>
                        </div>
                        <div class="text-sm">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-alt mr-1"></i> <?php echo date("M d, Y", strtotime($poll['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 mb-6"><?php echo htmlspecialchars($poll['description'] ?? 'No description provided.'); ?></p>
                    <div class="flex flex-wrap gap-2 text-sm text-gray-500 mb-3">
                        <div class="flex items-center">
                            <i class="fas fa-vote-yea mr-1"></i> <?php echo $vote_count; ?> votes
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-comment-alt mr-1"></i> <?php echo $comment_count; ?> comments
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button id="share-btn" class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center icon-hover"><i class="fas fa-share-alt mr-1"></i> Share Poll</button>
                        <button class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center icon-hover"><i class="fas fa-flag mr-1"></i> Report</button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 fade-in-delay-1">
                <div class="border-b border-gray-200">
                    <nav class="flex">
                        <button id="vote-tab" class="px-6 py-3 text-indigo-600 border-b-2 border-indigo-600 font-medium">Vote</button>
                        <button id="results-tab" class="px-6 py-3 text-gray-500 hover:text-gray-700">Results</button>
                        <button id="comments-tab" class="px-6 py-3 text-gray-500 hover:text-gray-700">Comments (<?php echo $comment_count; ?>)</button>
                    </nav>
                </div>

                <div id="voting-section" class="p-6 <?php echo $voted ? 'hidden' : ''; ?>">
                    <?php if (isset($login_error)): ?>
                    <div id="login-popup" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-md fade-in">
                        <span class="block"><?php echo $login_error; ?></span>
                        <p class="mt-2">
                            <a href="loginh.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1 rounded text-sm font-semibold inline-block transition duration-300 button-hover">Login Now</a>
                            <a href="signuph.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-1 rounded text-sm font-semibold inline-block transition duration-300 button-hover ml-2">Sign Up</a>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($vote_error)): ?>
                    <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative shadow-md fade-in">
                        <span class="block"><?php echo $vote_error; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($poll['require_login'] && !$user_logged_in): ?>
                    <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative shadow-md fade-in">
                        <span class="block">This poll requires you to login before voting.</span>
                        <p class="mt-2">
                            <a href="loginh.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1 rounded text-sm font-semibold inline-block transition duration-300 button-hover">Login Now</a>
                            <a href="signuph.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-1 rounded text-sm font-semibold inline-block transition duration-300 button-hover ml-2">Sign Up</a>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" <?php echo (!$user_logged_in) ? 'onsubmit="return checkLoginStatus()"' : ''; ?>>
                        <p class="text-sm text-gray-500 mb-4">
                            <i class="fas fa-user-secret mr-1"></i> Anonymous voting is <?php echo $poll['anonymous_voting'] ? 'enabled' : 'disabled'; ?>
                            <?php if ($user_logged_in): ?>
                            <span class="ml-4"><i class="fas fa-user mr-1"></i> Voting as: <strong><?php echo $username; ?></strong></span>
                            <?php endif; ?>
                        </p>
                        <div class="space-y-3">
                            <?php foreach ($options as $index => $option) { ?>
                                <div class="flex items-center grow-hover">
                                    <input id="option-<?php echo $index; ?>" name="poll-option" type="radio" value="<?php echo $index; ?>" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500" required>
                                    <label for="option-<?php echo $index; ?>" class="ml-3 block text-gray-700"><?php echo htmlspecialchars($option); ?></label>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="w-full md:w-auto px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300 button-hover pulse-animation">Submit Vote</button>
                            <p class="text-xs text-gray-500 mt-2">Once you vote, you cannot change your answer.</p>
                        </div>
                    </form>
                </div>

                <div id="results-section" class="p-6 <?php echo !$voted ? 'hidden' : ''; ?>">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Poll Results</h2>
                        <p class="text-sm text-gray-500"><span class="font-medium"><?php echo $vote_count; ?></span> votes total</p>
                    </div>
                    <div class="mb-8">
                        <canvas id="resultsChart" width="400" height="300"></canvas>
                    </div>
                    <div class="space-y-4">
                        <?php
                        $vote_counts = [];
                        foreach ($options as $index => $option) {
                            $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM votes WHERE poll_id = $poll_id AND option_index = $index"))['total'];
                            $vote_counts[$index] = $count;
                        }
                        $total_votes = array_sum($vote_counts);
                        foreach ($options as $index => $option) {
                            $votes = $vote_counts[$index];
                            $percentage = $total_votes > 0 ? round(($votes / $total_votes) * 100) : 0;
                            // Array of vibrant colors for progress bars
                            $colors = [
                                '#EF4444', // red
                                '#EC4899', // pink
                                '#10B981', // green
                                '#F59E0B', // amber
                                '#4F46E5', // indigo
                                '#6366F1', // purple
                                '#06B6D4', // cyan
                                '#8B5CF6', // violet
                                '#F97316', // orange
                                '#14B8A6'  // teal
                            ];
                            $barColor = $colors[$index % count($colors)];
                            ?>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="font-medium text-gray-700"><?php echo htmlspecialchars($option); ?></span>
                                    <span class="text-gray-500"><?php echo $votes; ?> votes (<?php echo $percentage; ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full progress-animate" style="width: <?php echo $percentage; ?>%; --progress-width: <?php echo $percentage; ?>%; background-color: <?php echo $barColor; ?>"></div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg text-sm text-gray-600">
                        <p><strong>Poll activity:</strong> This poll has been actively receiving votes since <?php echo date("M d, Y", strtotime($poll['created_at'])); ?>.</p>
                        <p class="mt-2"><strong>Real-time updates:</strong> Results are updated in real-time as votes are cast.</p>
                    </div>
                </div>

                <div id="comments-section" class="p-6 hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Comments</h2>
                        <button class="text-indigo-600 hover:text-indigo-800 font-medium text-underline">Sort by: Newest</button>
                    </div>
                    <div class="mb-6">
                        <form method="POST">
                            <textarea name="comment" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Add a comment..." required></textarea>
                            <div class="mt-2 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300 button-hover">Post Comment</button>
                            </div>
                        </form>
                    </div>
                    <div class="space-y-6">
                        <?php foreach ($comments as $comment) { ?>
                            <div class="border-b border-gray-200 pb-6 card-hover">
                                <div class="flex justify-between">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                                            <?php echo strtoupper(substr($comment['username'], 0, 1)); ?>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="font-medium"><?php echo htmlspecialchars($comment['username']); ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo date("M d, Y H:i", strtotime($comment['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <button class="text-gray-400 hover:text-gray-600 icon-hover"><i class="fas fa-ellipsis-v"></i></button>
                                </div>
                                <div class="mt-3">
                                    <p class="text-gray-700"><?php echo htmlspecialchars($comment['comment']); ?></p>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <?php if ($comment_count > 3) { ?>
                        <div class="mt-6 text-center">
                            <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-underline">Load More Comments</a>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="fade-in-delay-2">
                <h2 class="text-xl font-semibold mb-4">Related Polls</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php
                    $related_sql = "SELECT id, title, category FROM polls WHERE category = ? AND id != ? ORDER BY created_at DESC LIMIT 2";
                    $related_stmt = $conn->prepare($related_sql);
                    $related_stmt->bind_param("si", $poll['category'], $poll_id);
                    $related_stmt->execute();
                    $related_result = $related_stmt->get_result();
                    while ($related = $related_result->fetch_assoc()) {
                        $related_vote_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM votes WHERE poll_id = {$related['id']}"))['total'];
                        
                        // Define category images
                        $categoryImages = [
                            'technology' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600',
                            'food' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?q=80&w=600',
                            'entertainment' => 'https://images.unsplash.com/photo-1603190287605-e6ade32fa852?q=80&w=600',
                            'sports' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?q=80&w=600',
                            'politics' => 'https://images.unsplash.com/photo-1575320181282-9afab399332c?q=80&w=600',
                            'education' => 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?q=80&w=600',
                            'health' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?q=80&w=600',
                            'business' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?q=80&w=600'
                        ];
                        $imageUrl = $categoryImages[$related['category']] ?? 'https://images.unsplash.com/photo-1523961131990-5ea7c61b2107?q=80&w=600';
                        ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover relative h-48 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="absolute inset-0">
                                <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($related['category']); ?>" class="w-full h-full object-cover">
                                <!-- Gradient overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-black/20"></div>
                            </div>
                            <div class="absolute inset-0 p-4 flex flex-col justify-between text-white">
                                <div>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded"><?php echo htmlspecialchars($related['category']); ?></span>
                                    <h3 class="text-lg font-bold mt-2 mb-2 text-white"><?php echo htmlspecialchars($related['title']); ?></h3>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-200 mb-3">
                                        <span><i class="fas fa-vote-yea mr-1"></i> <?php echo $related_vote_count; ?> votes</span>
                                    </div>
                                    <a href="poll-details.php?id=<?php echo $related['id']; ?>" class="text-indigo-200 hover:text-white font-medium text-sm">Vote Now <i class="fas fa-arrow-right ml-1"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Share Modal -->
    <div id="share-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Share Poll</h3>
                <button id="close-share-modal" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="mb-4 text-gray-600">Share this poll with your friends and family to get their opinions!</p>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="share-url">
                    Poll URL
                </label>
                <div class="flex">
                    <input id="share-url" type="text" value="<?php echo "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"; ?>" class="border rounded-l px-4 py-2 w-full bg-gray-100 text-gray-800" readonly>
                    <button id="copy-url" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-r px-4">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"); ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full flex items-center justify-center transition-colors">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"); ?>&text=<?php echo urlencode($poll['title']); ?>" target="_blank" class="bg-blue-400 hover:bg-blue-500 text-white p-3 rounded-full flex items-center justify-center transition-colors">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($poll['title'] . ': ' . "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"); ?>" target="_blank" class="bg-green-500 hover:bg-green-600 text-white p-3 rounded-full flex items-center justify-center transition-colors">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="mailto:?subject=<?php echo urlencode($poll['title'] . ' - Vote on this poll'); ?>&body=<?php echo urlencode("I thought you might be interested in this poll: " . "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"); ?>" class="bg-red-500 hover:bg-red-600 text-white p-3 rounded-full flex items-center justify-center transition-colors">
                    <i class="fas fa-envelope"></i>
                </a>
            </div>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div><h3 class="text-xl font-semibold mb-4 fade-in">VotePoll</h3><p class="text-gray-300 fade-in-delay-1">The easiest way to create polls and gather opinions.</p></div>
                <div><h4 class="text-lg font-semibold mb-4 fade-in">Quick Links</h4><ul class="space-y-2 fade-in-delay-1"><li><a href="index.php" class="text-gray-300 hover:text-white text-underline">Home</a></li><li><a href="polls.php" class="text-gray-300 hover:text-white text-underline">Browse Polls</a></li><li><a href="create-poll.php" class="text-gray-300 hover:text-white text-underline">Create Poll</a></li></ul></div>
                <div><h4 class="text-lg font-semibold mb-4 fade-in">Support</h4><ul class="space-y-2 fade-in-delay-1"><li><a href="#" class="text-gray-300 hover:text-white text-underline">Help Center</a></li><li><a href="#" class="text-gray-300 hover:text-white text-underline">Contact Us</a></li><li><a href="#" class="text-gray-300 hover:text-white text-underline">FAQ</a></li></ul></div>
                <div><h4 class="text-lg font-semibold mb-4 fade-in">Connect With Us</h4><div class="flex space-x-4 fade-in-delay-1"><a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-facebook"></i></a><a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-twitter"></i></a><a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-instagram"></i></a><a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-linkedin"></i></a></div></div>
            </div>
            <div class="mt-8 pt-6 border-t border-gray-700 text-center text-gray-400 fade-in-delay-2">
                <p>© 2023 VotePoll. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart.js for poll results
        if (document.getElementById('resultsChart')) {
            const ctx = document.getElementById('resultsChart').getContext('2d');
            
            // Prepare data for chart
            const labels = [];
            const data = [];
            const backgroundColors = [];
            
            <?php foreach ($options as $index => $option) { 
                $count = $vote_counts[$index];
                $color = $colors[$index % count($colors)];
            ?>
                labels.push('<?php echo addslashes(htmlspecialchars($option)); ?>');
                data.push(<?php echo $count; ?>);
                backgroundColors.push('<?php echo $color; ?>');
            <?php } ?>
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} votes (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Tab switching functionality
        const voteTab = document.getElementById('vote-tab');
        const resultsTab = document.getElementById('results-tab');
        const commentsTab = document.getElementById('comments-tab');
        
        const votingSection = document.getElementById('voting-section');
        const resultsSection = document.getElementById('results-section');
        const commentsSection = document.getElementById('comments-section');
        
        voteTab.addEventListener('click', function() {
            voteTab.classList.add('text-indigo-600', 'border-b-2', 'border-indigo-600');
            resultsTab.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600');
            commentsTab.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600');
            
            votingSection.classList.remove('hidden');
            resultsSection.classList.add('hidden');
            commentsSection.classList.add('hidden');
        });
        
        resultsTab.addEventListener('click', function() {
            voteTab.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600');
            resultsTab.classList.add('text-indigo-600', 'border-b-2', 'border-indigo-600');
            commentsTab.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600');
            
            votingSection.classList.add('hidden');
            resultsSection.classList.remove('hidden');
            commentsSection.classList.add('hidden');
        });
        
        commentsTab.addEventListener('click', function() {
            voteTab.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600');
            resultsTab.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600');
            commentsTab.classList.add('text-indigo-600', 'border-b-2', 'border-indigo-600');
            
            votingSection.classList.add('hidden');
            resultsSection.classList.add('hidden');
            commentsSection.classList.remove('hidden');
        });

        // Sharing functionality
        const shareBtn = document.getElementById('share-btn');
        const shareModal = document.getElementById('share-modal');
        const closeShareModal = document.getElementById('close-share-modal');
        const shareUrl = document.getElementById('share-url');
        const copyUrl = document.getElementById('copy-url');
        
        // Open share modal
        shareBtn.addEventListener('click', function() {
            shareModal.classList.remove('hidden');
            // Select the URL text for easy copying
            shareUrl.select();
        });
        
        // Close share modal
        closeShareModal.addEventListener('click', function() {
            shareModal.classList.add('hidden');
        });
        
        // Click outside to close
        shareModal.addEventListener('click', function(e) {
            if (e.target === shareModal) {
                shareModal.classList.add('hidden');
            }
        });
        
        // Copy URL to clipboard
        copyUrl.addEventListener('click', function() {
            shareUrl.select();
            document.execCommand('copy');
            
            // Show copied feedback
            const originalText = copyUrl.innerHTML;
            copyUrl.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(function() {
                copyUrl.innerHTML = originalText;
            }, 1500);
        });
        
        // If there's a hash in the URL for comments section
        if (window.location.hash === '#comments-section') {
            // Trigger click on comments tab to show comments
            commentsTab.click();
        }
    });
    
    // Check if user is logged in before submitting vote
    function checkLoginStatus() {
        <?php if (!$user_logged_in): ?>
        // Create a modal popup
        let modalHTML = `
            <div id="loginModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 fade-in">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Login Required</h3>
                    <p class="text-gray-700 mb-6">You need to be logged in to vote in this poll. Please login or create an account.</p>
                    <div class="flex justify-end space-x-3">
                        <a href="signuph.php" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-800 font-medium">Sign Up</a>
                        <a href="loginh.php" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-medium">Login</a>
                    </div>
                </div>
            </div>
        `;
        
        // Append modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Prevent form submission
        return false;
        <?php else: ?>
        return true;
        <?php endif; ?>
    }
    </script>
</body>
</html>

<?php
$stmt->close();
$comments_stmt->close();
$related_stmt->close();
mysqli_close($conn);
?>