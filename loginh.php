<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "poll_system");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, first_name, password FROM CA WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['message'] = "Welcome back, " . htmlspecialchars($row['first_name']) . "! You have successfully logged in.";
            $_SESSION['message_type'] = "success";
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['message'] = "Invalid password!";
            $_SESSION['message_type'] = "error";
            header("Location: loginh.php");
            exit;
        }
    } else {
        $_SESSION['message'] = "Email not found!";
        $_SESSION['message_type'] = "error";
        header("Location: loginh.php");
        exit;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VotePoll</title>
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

    <!-- Login Section -->
    <section class="py-16 mt-16">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden fade-in">
                <div class="bg-indigo-600 text-white py-6 px-8">
                    <h2 class="text-2xl font-bold">Login to Your Account</h2>
                    <p class="text-indigo-100 mt-1">Access your polls and voting history</p>
                </div>
                
                <div class="p-8">
                    <form action="loginh.php" method="POST" id="login-form">
                        <div class="mb-6 fade-in-delay-1">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" id="email" name="email" class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your email" required>
                            </div>
                        </div>
                        
                        <div class="mb-6 fade-in-delay-1">
                            <div class="flex justify-between mb-1">
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <a href="#" class="text-sm text-indigo-600 hover:text-indigo-800 text-underline">Forgot Password?</a>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your password" required>
                                <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-center mb-6 fade-in-delay-2">
                            <input type="checkbox" id="remember-me" name="remember-me" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700">Remember me</label>
                        </div>
                        
                        <div class="mb-8 fade-in-delay-2">
                            <button type="submit" class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300 button-hover pulse-animation">
                                Sign In
                            </button>
                        </div>
                        
                        <div class="text-center fade-in-delay-3">
                            <p class="text-sm text-gray-600">Don't have an account? <a href="signuph.php" class="text-indigo-600 hover:text-indigo-800 font-medium text-underline">Sign up</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }

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