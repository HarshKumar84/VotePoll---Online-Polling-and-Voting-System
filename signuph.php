<?php
session_start();
// Step 1: Connect to the database
$servername = "localhost"; // Database server
$username = "root";        // Database username
$password = "";            // Database password (change if needed)
$dbname = "poll_system";   // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 2: Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data and sanitize it
    $firstName = trim($_POST['first-name']);
    $lastName = trim($_POST['last-name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $terms = isset($_POST['terms']) ? $_POST['terms'] : false;

    // Step 3: Perform validations
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['message'] = "Please fill in all fields.";
        $_SESSION['message_type'] = "error";
        header("Location: signuph.php");
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['message'] = "Password must be at least 8 characters long.";
        $_SESSION['message_type'] = "error";
        header("Location: signuph.php");
        exit;
    }

    if ($password !== $confirmPassword) {
        $_SESSION['message'] = "Passwords do not match.";
        $_SESSION['message_type'] = "error";
        header("Location: signuph.php");
        exit;
    }

    if (!$terms) {
        $_SESSION['message'] = "You must agree to the terms and conditions.";
        $_SESSION['message_type'] = "error";
        header("Location: signuph.php");
        exit;
    }

    // Step 4: Check if the email already exists using a prepared statement
    $emailCheckQuery = $conn->prepare("SELECT id FROM CA WHERE email = ?");
    $emailCheckQuery->bind_param("s", $email);
    $emailCheckQuery->execute();
    $emailCheckQuery->store_result();

    if ($emailCheckQuery->num_rows > 0) {
        $_SESSION['message'] = "The email address is already registered.";
        $_SESSION['message_type'] = "error";
        header("Location: signuph.php");
        exit;
    }
    $emailCheckQuery->close();

    // Step 5: Hash the password using password_hash() function
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Step 6: Insert the data into the database using a prepared statement
    $insertQuery = $conn->prepare("INSERT INTO CA (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
    $insertQuery->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);

    if ($insertQuery->execute()) {
        // Registration successful, set session message and redirect
        $_SESSION['message'] = "Registration successful! You can now log in.";
        $_SESSION['message_type'] = "success";
        header("Location: loginh.php");
        exit;
    } else {
        $_SESSION['message'] = "Error: " . $insertQuery->error;
        $_SESSION['message_type'] = "error";
        header("Location: signuph.php");
        exit;
    }
    $insertQuery->close();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - VotePoll</title>
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
                <a href="create-poll.php" class="hover:text-gray-300 text-underline">Create Poll</a>
                <a href="loginh.php" class="bg-white text-black hover:bg-gray-100 px-4 py-2 rounded-lg font-semibold transition duration-300 button-hover">Login</a>
                <a href="signuph.php" class="bg-gray-800 hover:bg-gray-900 px-4 py-2 rounded-lg font-semibold transition duration-300 button-hover">Signup</a>
            </div>
        </div>
    </nav>

    <!-- Message Alert -->
    <?php if (isset($_SESSION['message'])): ?>
    <div class="fixed top-20 right-4 z-50 animate-fade-in-down">
        <div class="bg-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded shadow-lg" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <?php if ($_SESSION['message_type'] === 'success'): ?>
                        <i class="fas fa-check-circle text-green-500"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $_SESSION['message']; ?></p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="inline-flex rounded-md p-1.5 text-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-500 hover:bg-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-200 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Dismiss</span>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php 
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    endif; 
    ?>

    <!-- Registration Section -->
    <section class="py-16 mt-16">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden fade-in">
                <div class="bg-indigo-600 text-white py-6 px-8">
                    <h2 class="text-2xl font-bold">Create Your Account</h2>
                    <p class="text-indigo-100 mt-1">Join our community and start creating polls</p>
                </div>
                
                <div class="p-8">
                    <form action="signuph.php" method="POST" id="register-form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="fade-in-delay-1">
                                <label for="first-name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input type="text" id="first-name" name="first-name" class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Your first name" required>
                            </div>
                            
                            <div class="fade-in-delay-1">
                                <label for="last-name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input type="text" id="last-name" name="last-name" class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Your last name" required>
                            </div>
                        </div>
                        
                        <div class="mb-6 fade-in-delay-1">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" id="email" name="email" class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Your email address" required>
                            </div>
                        </div>
                        
                        <div class="mb-6 fade-in-delay-2">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Create a password" required>
                                <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                        </div>
                        
                        <div class="mb-6 fade-in-delay-2">
                            <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="confirm-password" name="confirm-password" class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Confirm your password" required>
                            </div>
                        </div>
                        
                        <div class="flex items-start mb-6 fade-in-delay-3">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" required>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms" class="text-gray-700">I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-underline">Terms and Conditions</a> and <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-underline">Privacy Policy</a>.</label>
                            </div>
                        </div>
                        
                        <div class="mb-6 fade-in-delay-3">
                            <button type="submit" class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300 button-hover pulse-animation">
                                Create Account
                            </button>
                        </div>
                        
                        <div class="text-center fade-in-delay-3">
                            <p class="text-sm text-gray-600">Already have an account? <a href="loginh.php" class="text-indigo-600 hover:text-indigo-800 font-medium text-underline">Sign in</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-auto">
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
                        <li><a href="loginh.php" class="text-gray-300 hover:text-white text-underline">Login</a></li>
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
                <p>&copy; 2023 VotePoll. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const togglePassword = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Change eye icon
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
            
            // Form validation
            const registerForm = document.getElementById('register-form');
            // Animation on scroll
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
            
            // Run on load and scroll
            checkAnimations();
            window.addEventListener('scroll', checkAnimations);
        });
    </script>
</body>
</html>

