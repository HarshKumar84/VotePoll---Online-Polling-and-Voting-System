<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "poll_system");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch username if user is logged in
$username = "";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT first_name FROM CA WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $username = htmlspecialchars($row['first_name']);
    }
    $stmt->close();
}

// Get session messages
$message = "";
$messageType = "";
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Fetch recent polls for the Recent Polls section
$recent_polls_sql = "SELECT p.id, p.title, p.category, p.created_at, p.image_url, COUNT(v.id) as vote_count 
                     FROM polls p 
                     LEFT JOIN votes v ON p.id = v.poll_id 
                     GROUP BY p.id 
                     ORDER BY p.created_at DESC
                     LIMIT 3";
$recent_polls_result = $conn->query($recent_polls_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VotePoll - Online Polling and Voting System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="animations.css">
    <style>
        html, body {
            overflow-x: hidden;
            max-width: 100%;
        }
        img {
            max-width: 100%;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-black text-white shadow-lg fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold flex items-center wobble">
                <i class="fas fa-poll mr-2"></i>
                VotePoll
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

    <!-- Message Alert -->
    <?php if (!empty($message)): ?>
    <div class="fixed top-20 right-4 z-50 animate-fade-in-down">
        <div class="bg-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded shadow-lg" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <?php if ($messageType === 'success'): ?>
                        <i class="fas fa-check-circle text-green-500"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $message; ?></p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="inline-flex rounded-md p-1.5 text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-500 hover:bg-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-200 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Dismiss</span>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <header class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-indigo-700 text-white py-20 relative mt-16">
        <div class="absolute inset-0 overflow-hidden opacity-50">
            <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?q=80&w=2000" alt="Hero Background" class="w-full h-full object-cover">
        </div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-5xl md:text-5xl font-bold mb-4 fade-in">Make Your Voice Heard</h1>
            <p class="text-xl md:text-2xl mb-8 fade-in-delay-1">Create polls, gather opinions, and see results in real-time</p>
            <div class="flex flex-col md:flex-row justify-center gap-4 fade-in-delay-2">
                <a href="create-poll.php" class="bg-white text-indigo-700 hover:bg-indigo-100 px-6 py-3 rounded-lg font-semibold transition duration-300 button-hover pulse-animation">Create a Poll</a>
                <a href="pollresult.php" class="bg-indigo-600 hover:bg-indigo-500 border border-white px-6 py-3 rounded-lg font-semibold transition duration-300 button-hover">Vote Now</a>
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <section class="py-16 container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12 fade-in">Why Choose VotePoll?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-md text-center card-hover fade-in-delay-1">
                <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 icon-hover">
                    <i class="fas fa-bolt text-2xl text-indigo-600"></i>
                </div>
                <img src="https://media.istockphoto.com/id/2168505525/vector/flat-illustration-of-analyst-reviewing-performance-metrics-on-real-time-project-dashboard.jpg?s=612x612&w=0&k=20&c=N8fIjFUQ06VYG0HoHsFdW9FQ6dK_K8NWz5P3H_wwWZk=" alt="Real-time Results" class="w-full h-50 object-cover rounded-lg mb-4">
                <h3 class="text-xl font-semibold mb-3">Real-time Results</h3>
                <p class="text-gray-600">Watch as results update instantly as people cast their votes.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center card-hover fade-in-delay-2">
                <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 icon-hover">
                    <i class="fas fa-user-secret text-2xl text-indigo-600"></i>
                </div>
                <img src="https://www.votesforschools.com/site/assets/files/11166/istock-1412722623.570x373.jpg" class="w-full h-50 object-cover rounded-lg mb-5">
                <h3 class="text-xl font-semibold mb-3">Anonymous Voting</h3>
                <p class="text-gray-600">Enable anonymous voting to get honest, unbiased feedback.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center card-hover fade-in-delay-3">
                <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 icon-hover">
                    <i class="fas fa-chart-pie text-2xl text-indigo-600"></i>
                </div>
                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f" alt="Analyze Results" class="w-full h-50 object-cover rounded-lg mb-4" style="height: 270px;">
                <h3 class="text-xl font-semibold mb-3">Advanced Analytics</h3>
                <p class="text-gray-600">Get detailed insights with beautiful charts and data visualization.</p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-16 bg-gradient-to-r from-gray-100 to-gray-50 relative">
        <div class="absolute inset-0 overflow-hidden opacity-70">
            <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=2000" alt="How It Works Background" class="w-full h-100 object-cover">
        </div>
        <div class="container mx-auto px-4 relative z-10">
            <h2 class="text-3xl font-bold text-center mb-12 fade-in">How It Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center fade-in-delay-1 bg-white p-6 rounded-lg shadow-md">
                    <img src="https://i.ytimg.com/vi/AIgtuB3569w/maxresdefault.jpg" alt="Create a Poll" class="w-full  md:h-64 object-cover rounded-lg mb-4" style="height: 270px;">
                    <h3 class="text-xl font-semibold mb-3">Create a Poll</h3>
                    <p class="text-gray-600">Set up your question, add options, and configure settings like anonymous voting.</p>
                </div>
                <div class="text-center fade-in-delay-2 bg-white p-6 rounded-lg shadow-md">
                    <img src="https://thriveworks.com/wp-content/uploads/2019/09/blog-thriveworks-sharing-opinions.jpg" alt="Share with Others" class="w-full h-50 object-cover rounded-lg mb-4">
                    <h3 class="text-xl font-semibold mb-3">Share with Others</h3>
                    <p class="text-gray-600">Send the poll link to friends, colleagues, or post it on social media.</p>
                </div>
                <div class="text-center fade-in-delay-3 bg-white p-6 rounded-lg shadow-md">
                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f" alt="Analyze Results" class="w-full h-50 object-cover rounded-lg mb-4" style="height: 270px;">
                    <h3 class="text-xl font-semibold mb-3">Analyze Results</h3>
                    <p class="text-gray-600">Watch votes come in and view detailed analytics in real-time.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Use Cases Section -->
    <section class="py-16 bg-gradient-to-r from-indigo-50 to-purple-50 relative">
        <div class="absolute inset-0 overflow-hidden opacity-30">
            <img src="https://images.unsplash.com/photo-1600880292089-90a7e086ee0c?q=80&w=2000" alt="Use Cases Background" class="w-full h-full object-cover">
        </div>
        <div class="container mx-auto px-4 relative z-10">
            <h2 class="text-3xl font-bold text-center mb-12 fade-in">Use Cases</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Business & Marketing -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl fade-in-delay-1">
                    <div class="bg-indigo-600 p-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-briefcase mr-3"></i>
                            Business & Marketing
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 mb-4">Gather customer feedback, conduct market research, and make data-driven decisions for your business.</p>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-indigo-500 mt-1 mr-2"></i>
                                <span>Product feature prioritization</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-indigo-500 mt-1 mr-2"></i>
                                <span>Customer satisfaction surveys</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-indigo-500 mt-1 mr-2"></i>
                                <span>Market research</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Education -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl fade-in-delay-1">
                    <div class="bg-blue-600 p-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-graduation-cap mr-3"></i>
                            Education
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 mb-4">Engage students, gather feedback on courses, and make decisions about educational programs.</p>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                                <span>Student feedback on courses</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                                <span>Classroom engagement</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                                <span>Curriculum planning</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Community & Events -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl fade-in-delay-2">
                    <div class="bg-green-600 p-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-users mr-3"></i>
                            Community & Events
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 mb-4">Engage your community, plan events, and make group decisions efficiently.</p>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span>Event planning</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span>Community decisions</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span>Group activities</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Politics & Governance -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl fade-in-delay-2">
                    <div class="bg-purple-600 p-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-landmark mr-3"></i>
                            Politics & Governance
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 mb-4">Conduct polls for political campaigns, gather public opinion, and make informed decisions.</p>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-purple-500 mt-1 mr-2"></i>
                                <span>Public opinion surveys</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-purple-500 mt-1 mr-2"></i>
                                <span>Policy feedback</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-purple-500 mt-1 mr-2"></i>
                                <span>Campaign engagement</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Polls Section -->
    <section class="py-16 container mx-auto px-4">
        <div class="flex justify-between items-center mb-8 fade-in">
            <h2 class="text-3xl font-bold">Recent Polls</h2>
            <a href="pollresult.php" class="text-indigo-600 hover:text-indigo-800 font-semibold text-underline">View All</a>
        </div>
        
        <!-- Add browse poll image banner -->
        <div class="w-full mb-8 fade-in-delay-1 relative">
            <img src="https://www.pewresearch.org/wp-content/uploads/sites/20/2024/08/SR_24.08.28_facts-about-polling_feature.jpg" alt="Browse Polls" class="w-full h-64 md:h-96 object-cover rounded-lg shadow-md">
            <div class="absolute inset-0 bg-white/50 rounded-lg"></div>
            <div class="absolute inset-0 flex items-center px-4 md:px-12">
                <div class="w-full md:w-1/2 text-left">
                    <h3 class="text-3xl md:text-5xl font-bold text-black drop-shadow-md mb-4">Browse Popular Polls</h3>
                    <p class="text-black text-xl md:text-2xl drop-shadow-md">Discover and vote on trending topics.</p>
                    <a href="pollresult.php" class="mt-6 inline-block bg-white text-indigo-700 hover:bg-indigo-50 px-8 py-3 rounded-lg font-semibold text-lg transition duration-300 button-hover shadow-lg">Explore Now</a>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php
            // Dynamic poll cards fetched from database
            if ($recent_polls_result->num_rows > 0) {
                while ($poll = $recent_polls_result->fetch_assoc()) {
                    $poll_id = $poll['id'];
                    $title = htmlspecialchars($poll['title']);
                    $category = htmlspecialchars($poll['category']);
                    $created_at = date("M d, Y", strtotime($poll['created_at']));
                    $vote_count = $poll['vote_count'];
                    
                    // Define category-specific styles and images
                    $category_color = [
                        'technology' => 'bg-blue-100 text-blue-800',
                        'food' => 'bg-green-100 text-green-800',
                        'entertainment' => 'bg-purple-100 text-purple-800',
                        'sports' => 'bg-red-100 text-red-800',
                        'politics' => 'bg-indigo-100 text-indigo-800',
                        'education' => 'bg-yellow-100 text-yellow-800'
                    ][$category] ?? 'bg-gray-100 text-gray-800';
                    
                    $categoryImages = [
                        'technology' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600',
                        'food' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?q=80&w=1000',
                        'entertainment' => 'https://images.unsplash.com/photo-1616530940355-351fabd9524b?q=80&w=1000',
                        'sports' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?q=80&w=600',
                        'politics' => 'https://images.unsplash.com/photo-1575320181282-9afab399332c?q=80&w=600',
                        'education' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1000',
                        'health' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?q=80&w=600',
                        'business' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?q=80&w=600'
                    ];
                    
                    // Use the poll's custom image if available, otherwise use a category image
                    $imageUrl = !empty($poll['image_url']) ? $poll['image_url'] : ($categoryImages[$category] ?? 'https://images.unsplash.com/photo-1523961131990-5ea7c61b2107?q=80&w=600');
                    ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover fade-in-delay-1 relative h-80 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        <div class="absolute inset-0">
                            <img src="<?php echo $imageUrl; ?>" alt="<?php echo $category; ?> Poll" class="w-full h-full object-cover">
                            <!-- Gradient overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-black/20"></div>
                        </div>
                        <div class="absolute inset-0 p-6 flex flex-col justify-between text-white">
                            <div>
                                <span class="<?php echo $category_color; ?> text-xs font-medium px-2.5 py-0.5 rounded"><?php echo $category; ?></span>
                                <h3 class="text-2xl font-bold mt-2 mb-3 text-white"><?php echo $title; ?></h3>
                            </div>
                            <div>
                                <div class="text-sm text-gray-200 mb-4 flex justify-between">
                                    <span><i class="fas fa-calendar-alt mr-1"></i> <?php echo $created_at; ?></span>
                                    <span><i class="fas fa-vote-yea mr-1"></i> <?php echo $vote_count; ?> votes</span>
                                </div>
                                <a href="poll-details.php?id=<?php echo $poll_id; ?>" class="block text-center bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg transition duration-300 button-hover">Vote Now</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // If no polls are available
                ?>
                <div class="col-span-3 text-center py-8">
                    <p class="text-gray-600">No polls available yet. <a href="create-poll.php" class="text-indigo-600 hover:underline">Create one now!</a></p>
                </div>
                <?php
            }
            ?>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-gradient-to-r from-gray-50 to-indigo-50 relative">
        <div class="absolute inset-0 overflow-hidden opacity-20">
            <img src="https://images.unsplash.com/photo-1533750516457-a7f992034fec?q=80&w=2000" alt="FAQ Background" class="w-full h-full object-cover">
        </div>
        <div class="container mx-auto px-4 relative z-10">
            <h2 class="text-3xl font-bold text-center mb-12 fade-in">Frequently Asked Questions</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <!-- FAQ Item 1 -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300 fade-in-delay-1">
                    <h3 class="text-xl font-semibold text-indigo-700 mb-3 flex items-center">
                        <i class="fas fa-question-circle text-indigo-500 mr-3 text-2xl"></i>
                        How do I create a poll?
                    </h3>
                    <p class="text-gray-700">Creating a poll is easy! Simply click on the "Create Poll" button in the navigation menu, fill in your question and options, and configure your settings. Once you're done, click "Create" and your poll will be ready to share.</p>
                </div>
                
                <!-- FAQ Item 2 -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300 fade-in-delay-1">
                    <h3 class="text-xl font-semibold text-indigo-700 mb-3 flex items-center">
                        <i class="fas fa-user-secret text-indigo-500 mr-3 text-2xl"></i>
                        Can I make my polls anonymous?
                    </h3>
                    <p class="text-gray-700">Yes, you can enable anonymous voting when creating your poll. This option allows respondents to vote without revealing their identity, which can lead to more honest feedback.</p>
                </div>
                
                <!-- FAQ Item 3 (Previously 5) -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300 fade-in-delay-2 md:col-span-2">
                    <h3 class="text-xl font-semibold text-indigo-700 mb-3 flex items-center">
                        <i class="fas fa-users text-indigo-500 mr-3 text-2xl"></i>
                        Is there a limit to how many people can vote on my poll?
                    </h3>
                    <p class="text-gray-700">No, there is no limit to how many people can vote on your polls. You can share your poll with as many participants as you'd like.</p>
                </div>
            </div>
            
            
        </div>
    </section>

    <!-- Call to Action -->
    <section class="bg-gradient-to-r from-indigo-400 to-indigo-600 text-white py-16 relative">
        <div class="absolute inset-0 overflow-hidden opacity-40">
            <img src="https://images.unsplash.com/photo-1551434678-e076c223a692?q=80&w=2000" alt="CTA Background" class="w-full h-full object-cover">
        </div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h2 class="text-3xl font-bold mb-4 fade-in">Ready to Create Your Own Poll?</h2>
            <p class="text-xl mb-8 fade-in-delay-1">It's free and takes less than a minute to get started.</p>
            <div class="flex flex-col md:flex-row justify-center gap-4 fade-in-delay-2">
                <a href="create-poll.php" class="bg-white text-indigo-700 hover:bg-indigo-50 px-8 py-3 rounded-lg font-semibold text-lg inline-block transition duration-300 button-hover">Create Poll Now</a>
                <?php if (empty($username)): ?>
                    <a href="login.php" class="bg-indigo-600 hover:bg-indigo-500 border border-white px-8 py-3 rounded-lg font-semibold text-lg inline-block transition duration-300 button-hover">Sign In</a>
                <?php else: ?>
                    <a href="logout.php" class="bg-indigo-600 hover:bg-indigo-500 border border-white px-8 py-3 rounded-lg font-semibold text-lg inline-block transition duration-300 button-hover">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4 fade-in">VotePoll</h3>
                    <p class="text-gray-300 fade-in-delay-1">The easiest way to create polls and gather opinions.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4 fade-in">Quick Links</h4>
                    <ul class="space-y-2 fade-in-delay-1">
                        <li><a href="index.php" class="text-gray-300 hover:text-white text-underline">Home</a></li>
                        <li><a href="pollresult.php" class="text-gray-300 hover:text-white text-underline">Browse Polls</a></li>
                        <li><a href="create-poll.php" class="text-gray-300 hover:text-white text-underline">Create Poll</a></li>
                        <li><a href="login.php" class="text-gray-300 hover:text-white text-underline">Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4 fade-in">Support</h4>
                    <ul class="space-y-2 fade-in-delay-1">
                        <li><a href="#" class="text-gray-300 hover:text-white text-underline">Help Center</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white text-underline">Contact Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white text-underline">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4 fade-in">Connect With Us</h4>
                    <div class="flex space-x-4 fade-in-delay-1">
                        <a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-gray-700 text-center text-gray-400 fade-in-delay-2">
                <p>© 2025 VotePoll. All rights reserved. </p>
                <p class="text-white text-extra-bold hover:underline text-2xl">Made by:- Harsh, Rounak, Nitin and Dilip </p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function isInViewport(element) {
                const rect = element.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
            
            function checkAnimations() {
                const elements = document.querySelectorAll('.fade-in, .fade-in-delay-1, .fade-in-delay-2, .fade-in-delay-3');
                elements.forEach(element => {
                    if (isInViewport(element)) {
                        element.style.animationPlayState = 'running';
                    }
                });
            }
            
            checkAnimations();
            window.addEventListener('scroll', checkAnimations);
        });
    </script>
</body>
</html>