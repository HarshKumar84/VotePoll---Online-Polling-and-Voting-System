<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "poll_system");
if (!$conn) {
    die("Connection failed: Database 'poll_system' nahi mila - " . mysqli_connect_error());
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['poll-title'];
    $description = $_POST['poll-description'];
    $category = $_POST['poll-category'];
    $options = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'option_') === 0 && !empty($value)) {
            $options[] = $value;
        }
    }
    $allow_multiple_choices = isset($_POST['multiple-votes']) ? 1 : 0;
    $anonymous_voting = isset($_POST['anonymous-voting']) ? 1 : 0;
    $require_login = isset($_POST['require-login']) ? 1 : 0;
    $show_results = isset($_POST['show-results']) ? 1 : 0;
    $end_date = !empty($_POST['end-date']) ? $_POST['end-date'] : NULL;

    // Handle image upload - store directly in database as base64
    $image_url = NULL;
    if(isset($_FILES['poll-image']) && $_FILES['poll-image']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['poll-image']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($ext), $allowed)) {
            // Read file content and encode as base64
            $image_content = file_get_contents($_FILES['poll-image']['tmp_name']);
            $image_base64 = base64_encode($image_content);
            $image_url = 'data:image/' . $ext . ';base64,' . $image_base64;
        }
    }

    if (empty($title) || empty($category) || count($options) < 2) {
        die("Please fill in all required fields and provide at least two options.");
    }

    $options_json = json_encode($options);

    // First, check if image_url column exists in polls table
    $columnCheck = $conn->query("SHOW COLUMNS FROM polls LIKE 'image_url'");
    if($columnCheck->num_rows == 0) {
        // Column doesn't exist, create it
        $conn->query("ALTER TABLE polls ADD COLUMN image_url LONGTEXT");
    }

    // Increase max allowed packet size for this connection
    $conn->query("SET GLOBAL max_allowed_packet=104857600"); // 100MB
    $conn->query("SET SESSION wait_timeout=300");

    // Prepared statement - added image_url parameter
    $stmt = $conn->prepare("INSERT INTO polls (title, description, category, options, allow_multiple_choices, anonymous_voting, require_login, show_results, end_date, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiiiiss", $title, $description, $category, $options_json, $allow_multiple_choices, $anonymous_voting, $require_login, $show_results, $end_date, $image_url);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Poll created successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: pollresult.php"); // Changed to pollresult.php instead of polls.html
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Poll - VotePoll</title>
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
                <a href="pollresult.php" class="hover:text-gray-300 text-underline">Browse Polls</a>
                <a href="create-poll.php" class="hover:text-gray-300 font-medium text-underline">Create Poll</a>
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

    <!-- Header Section -->
    <header class="bg-gradient-to-r from-pink-500 via-indigo-600 to-pink-500 text-white py-20 relative mt-16">
        <div class="container mx-auto px-4 text-center">
            
            <h1 class="text-3xl md:text-4xl font-bold mb-4 fade-in">Create a New Poll</h1>
            <p class="text-lg fade-in-delay-1">Design your poll, set options, and share it with the world</p>
        </div>
    </header>

    <!-- Create Poll Form Section -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-6 md:p-8 fade-in-delay-1">
                <form action="create-poll.php" method="POST" enctype="multipart/form-data">
                    <!-- Poll Basic Info -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4 pb-2 border-b border-gray-200">Poll Information</h2>
                        
                        <div class="mb-4 fade-in-delay-1">
                            <label for="poll-title" class="block text-sm font-medium text-gray-700 mb-1">Poll Question <span class="text-red-500">*</span></label>
                            <input type="text" id="poll-title" name="poll-title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter your question here..." required>
                            <p class="text-xs text-gray-500 mt-1">Be clear and specific with your question (max 100 characters)</p>
                        </div>
                        
                        <div class="mb-4 fade-in-delay-1">
                            <label for="poll-description" class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                            <textarea id="poll-description" name="poll-description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Add more context to your question..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">Provide additional details about your poll if needed</p>
                        </div>
                        
                        <div class="mb-4 fade-in-delay-2">
                            <label for="poll-category" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                            <select id="poll-category" name="poll-category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="" disabled selected>Select a category</option>
                                <option value="technology">Technology</option>
                                <option value="food">Food</option>
                                <option value="entertainment">Entertainment</option>
                                <option value="sports">Sports</option>
                                <option value="politics">Politics</option>
                                <option value="education">Education</option>
                                <option value="health">Health</option>
                                <option value="business">Business</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-4 fade-in-delay-2">
                            <label for="poll-image" class="block text-sm font-medium text-gray-700 mb-1">Cover Image (Optional)</label>
                            <div class="flex items-center justify-center w-full">
                                <label for="poll-image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 card-hover">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6" id="image-upload-text">
                                        <i class="fas fa-cloud-upload-alt text-gray-500 text-2xl mb-2"></i>
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                        <p class="text-xs text-gray-500">SVG, PNG, JPG or GIF</p>
                                    </div>
                                    <div id="image-preview" class="hidden w-full h-full">
                                        <img id="preview-image" class="w-full h-full object-contain" src="#" alt="Preview">
                                    </div>
                                    <input id="poll-image" name="poll-image" type="file" class="hidden" accept="image/*" />
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">This image will be used as the poll background.</p>
                        </div>
                    </div>
                    
                    <!-- Poll Options -->
                    <div class="mb-8 fade-in-delay-2">
                        <h2 class="text-xl font-semibold mb-4 pb-2 border-b border-gray-200">Poll Options</h2>
                        
                        <div class="space-y-3" id="poll-options">
                            <div class="flex items-center grow-hover">
                                <input type="text" name="option_1" class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Option 1" required>
                                <button type="button" class="ml-2 text-gray-400 hover:text-gray-600" disabled>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="flex items-center grow-hover">
                                <input type="text" name="option_2" class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Option 2" required>
                                <button type="button" class="ml-2 text-gray-400 hover:text-gray-600" disabled>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="flex items-center grow-hover">
                                <input type="text" name="option_3" class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Option 3">
                                <button type="button" class="ml-2 text-red-500 hover:text-red-700 icon-hover">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="button" id="add-option" class="mt-3 flex items-center text-indigo-600 hover:text-indigo-800 icon-hover">
                            <i class="fas fa-plus-circle mr-1"></i> Add Another Option
                        </button>
                        
                        <p class="text-xs text-gray-500 mt-2">You can add up to 10 options</p>
                    </div>
                    
                    <!-- Poll Settings -->
                    <div class="mb-8 fade-in-delay-3">
                        <h2 class="text-xl font-semibold mb-4 pb-2 border-b border-gray-200">Poll Settings</h2>
                        
                        <div class="space-y-4">
                            <div class="flex items-start grow-hover">
                                <div class="flex items-center h-5">
                                    <input id="multiple-votes" name="multiple-votes" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="multiple-votes" class="font-medium text-gray-700">Allow multiple choices</label>
                                    <p class="text-gray-500">Let voters select more than one option</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start grow-hover">
                                <div class="flex items-center h-5">
                                    <input id="anonymous-voting" name="anonymous-voting" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" checked>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="anonymous-voting" class="font-medium text-gray-700">Anonymous voting</label>
                                    <p class="text-gray-500">Keeps voters' identities private</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start grow-hover">
                                <div class="flex items-center h-5">
                                    <input id="require-login" name="require-login" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="require-login" class="font-medium text-gray-700">Require login to vote</label>
                                    <p class="text-gray-500">Only registered users can vote</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start grow-hover">
                                <div class="flex items-center h-5">
                                    <input id="show-results" name="show-results" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" checked>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="show-results" class="font-medium text-gray-700">Show results before voting</label>
                                    <p class="text-gray-500">Let people see current results before they vote</p>
                                </div>
                            </div>
                            
                            <div class="grow-hover">
                                <label for="end-date" class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                                <input type="date" id="end-date" name="end-date" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">The poll will automatically close on this date. Leave blank to keep it open indefinitely.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4 fade-in-delay-3">
                        <button type="button" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-300 button-hover">
                            Save as Draft
                        </button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300 button-hover pulse-animation">
                            Create Poll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
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
                <p>© 2023 VotePoll. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
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

            const addOptionButton = document.getElementById('add-option');
            const pollOptionsContainer = document.getElementById('poll-options');
            let optionCount = 3; // Starting with 3 options
            
            // Add option button functionality
            addOptionButton.addEventListener('click', function() {
                if (optionCount < 10) {
                    optionCount++;
                    const optionRow = document.createElement('div');
                    optionRow.className = 'flex items-center grow-hover';
                    optionRow.innerHTML = `
                        <input type="text" name="option_${optionCount}" class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Option ${optionCount}">
                        <button type="button" class="ml-2 text-red-500 hover:text-red-700 icon-hover remove-option">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    pollOptionsContainer.appendChild(optionRow);
                    
                    if (optionCount === 10) {
                        addOptionButton.disabled = true;
                        addOptionButton.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                }
            });
            
            // Remove option functionality
            pollOptionsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-option')) {
                    e.target.closest('.flex').remove();
                    optionCount--;
                    addOptionButton.disabled = false;
                    addOptionButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    
                    // Renumber the options
                    const optionInputs = pollOptionsContainer.querySelectorAll('input[type="text"]');
                    optionInputs.forEach((input, index) => {
                        input.name = `option_${index + 1}`;
                        input.placeholder = `Option ${index + 1}`;
                    });
                }
            });

            // Image preview functionality
            const imageInput = document.getElementById('poll-image');
            const imagePreview = document.getElementById('image-preview');
            const previewImage = document.getElementById('preview-image');
            const uploadText = document.getElementById('image-upload-text');
            
            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        uploadText.classList.add('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>