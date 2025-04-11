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

// Fetch all polls (no user_id filter)
$sql = "SELECT p.id, p.title, p.category, p.created_at, p.image_url, COUNT(v.id) as vote_count 
        FROM polls p 
        LEFT JOIN votes v ON p.id = v.poll_id 
        GROUP BY p.id 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Polls - VotePoll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="animations.css">
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
                <a href="pollresult.php" class="hover:text-gray-300 font-medium text-underline">Browse Polls</a>
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

    <!-- Header -->
    <header class="bg-gradient-to-r from-pink-200 via-pink-100 to-white text-indigo-700 pt-14 pb-4 relative shadow-sm mt-16">
        <div class="container mx-auto px-4 relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-6 fade-in">Browse Polls</h1>
            <div class="flex flex-col md:flex-row gap-8 mb-5">
                <div class="md:w-2/3">
                    <p class="text-xl mb-6 fade-in-delay-1 text-gray-700">Discover polls on various topics and make your voice heard</p>
                    
                    <!-- Search Box -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-3 mt-20 shadow-md fade-in-delay-1 border border-indigo-100">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-grow">
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <i class="fas fa-search text-indigo-500 group-hover:text-indigo-600 transition-colors"></i>
                                    </div>
                                    <input type="text" id="search-input" class="bg-white border border-indigo-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-9 p-2 shadow-sm" placeholder="Search for polls...">
                                </div>
                            </div>
                            <div>
                                <select id="category-filter" class="bg-white border border-indigo-200 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2 shadow-sm appearance-none">
                                    <option selected>All Categories</option>
                                    <option value="technology">Technology</option>
                                    <option value="food">Food</option>
                                    <option value="entertainment">Entertainment</option>
                                    <option value="sports">Sports</option>
                                    <option value="politics">Politics</option>
                                    <option value="education">Education</option>
                                </select>
                            </div>
                            <div>
                                <select id="sort-filter" class="bg-white border border-indigo-200 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2 shadow-sm appearance-none">
                                    <option selected>Sort By</option>
                                    <option value="newest">Newest</option>
                                    <option value="popular">Most Popular</option>
                                    <option value="active">Most Active</option>
                                </select>
                            </div>
                            <button id="apply-filters" class="bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-medium py-2 px-4 rounded-lg shadow-sm">
                                <i class="fas fa-filter mr-1"></i>Apply
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="md:w-1/3 flex items-center justify-center">
                    <img src="https://www.polly.ai/hubfs/Blog%20Images/Illustrations%20(white,%20svg)/Analyzing%20Results%20Fun%201.svg" alt="Poll Background" class="max-h-48 object-contain hidden md:block">
                </div>
            </div>
        </div>
    </header>

    <!-- Polls Section -->
    <section class="py-6 container mx-auto px-4">
        <div id="polls-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $poll_id = $row['id'];
                    $title = htmlspecialchars($row['title']);
                    $category = htmlspecialchars($row['category']);
                    $created_at = date("M d, Y", strtotime($row['created_at']));
                    $vote_count = $row['vote_count'];
                    $category_color = [
                        'technology' => 'bg-blue-100 text-blue-800',
                        'food' => 'bg-green-100 text-green-800',
                        'entertainment' => 'bg-purple-100 text-purple-800',
                        'sports' => 'bg-red-100 text-red-800',
                        'politics' => 'bg-indigo-100 text-indigo-800',
                        'education' => 'bg-yellow-100 text-yellow-800'
                    ][$category] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover fade-in-delay-1 relative h-80 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        <?php
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
                        
                        // Use custom image if available, otherwise use category image
                        $imageUrl = !empty($row['image_url']) ? $row['image_url'] : ($categoryImages[$category] ?? 'https://images.unsplash.com/photo-1523961131990-5ea7c61b2107?q=80&w=600');
                        ?>
                        <div class="absolute inset-0">
                            <img src="<?php echo $imageUrl; ?>" alt="<?php echo $category; ?>" class="w-full h-full object-cover">
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
                                    <span><i class="fas fa-calendar-alt mr-1"></i> Created on <?php echo $created_at; ?></span>
                                    <span><i class="fas fa-vote-yea mr-1"></i> <?php echo $vote_count; ?> votes</span>
                                </div>
                                <a href="poll-details.php?id=<?php echo $poll_id; ?>" class="block text-center bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg transition duration-300 button-hover">Vote Now</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="text-center text-gray-500">No polls available yet. <a href="create-poll.php" class="text-indigo-600 hover:text-indigo-800 text-underline">Create one now!</a></p>';
            }
            ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div><h3 class="text-xl font-semibold mb-4 fade-in">VotePoll</h3><p class="text-gray-300 fade-in-delay-1">The easiest way to create polls and gather opinions.</p></div>
                <div><h4 class="text-lg font-semibold mb-4 fade-in">Quick Links</h4><ul class="space-y-2 fade-in-delay-1"><li><a href="index.php" class="text-gray-300 hover:text-white text-underline">Home</a></li><li><a href="pollresult.php" class="text-gray-300 hover:text-white text-underline">Browse Polls</a></li><li><a href="create-poll.php" class="text-gray-300 hover:text-white text-underline">Create Poll</a></li></ul></div>
                <div><h4 class="text-lg font-semibold mb-4 fade-in">Support</h4><ul class="space-y-2 fade-in-delay-1"><li><a href="#" class="text-gray-300 hover:text-white text-underline">Help Center</a></li><li><a href="#" class="text-gray-300 hover:text-white text-underline">Contact Us</a></li><li><a href="#" class="text-gray-300 hover:text-white text-underline">FAQ</a></li></ul></div>
                <div><h4 class="text-lg font-semibold mb-4 fade-in">Connect With Us</h4><div class="flex space-x-4 fade-in-delay-1"><a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-facebook"></i></a><a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-twitter"></i></a><a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-instagram"></i></a><a href="#" class="text-gray-300 hover:text-white text-xl icon-hover"><i class="fab fa-linkedin"></i></a></div></div>
            </div>
            <div class="mt-8 pt-6 border-t border-gray-700 text-center text-gray-400 fade-in-delay-2">
                <p>© 2023 VotePoll. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const pollsGrid = document.getElementById('polls-grid');
        const pollCards = Array.from(pollsGrid.querySelectorAll('.card-hover')); // Select only poll cards
        const searchInput = document.getElementById('search-input');
        const categoryFilter = document.getElementById('category-filter');
        const sortFilter = document.getElementById('sort-filter');
        const applyFiltersBtn = document.getElementById('apply-filters');

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = categoryFilter.value.toLowerCase();
            const selectedSort = sortFilter.value;

            // Filter polls
            let filteredCards = pollCards.filter(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const category = card.querySelector('span').textContent.toLowerCase();
                const matchesSearch = title.includes(searchTerm);
                const matchesCategory = selectedCategory === 'all categories' || category === selectedCategory;
                return matchesSearch && matchesCategory;
            });

            // Sort filtered polls
            if (selectedSort === 'newest') {
                filteredCards.sort((a, b) => new Date(b.querySelector('.fa-calendar-alt').nextSibling.textContent.split('on ')[1]) - new Date(a.querySelector('.fa-calendar-alt').nextSibling.textContent.split('on ')[1]));
            } else if (selectedSort === 'popular' || selectedSort === 'active') {
                filteredCards.sort((a, b) => parseInt(b.querySelector('.fa-vote-yea').parentElement.textContent) - parseInt(a.querySelector('.fa-vote-yea').parentElement.textContent));
            }

            // Update grid
            pollsGrid.innerHTML = '';
            if (filteredCards.length > 0) {
                filteredCards.forEach(card => pollsGrid.appendChild(card));
            } else {
                pollsGrid.innerHTML = '<p class="text-center text-gray-500 col-span-3">No matching polls found.</p>';
            }
        }

        // Apply filters on button click
        applyFiltersBtn.addEventListener('click', applyFilters);

        // Optional: Apply filters on input/select change (real-time)
        searchInput.addEventListener('input', applyFilters);
        categoryFilter.addEventListener('change', applyFilters);
        sortFilter.addEventListener('change', applyFilters);

        // Animations
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

<?php mysqli_close($conn); ?>
