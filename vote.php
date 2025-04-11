<?php
// Include database configuration
require_once 'includes/config.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMessage('Invalid request method.', 'error');
    redirect('polls.php');
}

// Get poll ID and selected option
if (!isset($_POST['poll_id']) || !is_numeric($_POST['poll_id']) || !isset($_POST['option_id'])) {
    setMessage('Missing required parameters.', 'error');
    redirect('polls.php');
}

$poll_id = (int)$_POST['poll_id'];
$option_id = (int)$_POST['option_id'];

// Get poll details to check settings
$pollQuery = "SELECT * FROM polls WHERE id = $poll_id";
$pollResult = mysqli_query($conn, $pollQuery);

if (mysqli_num_rows($pollResult) == 0) {
    setMessage('Poll not found.', 'error');
    redirect('polls.php');
}

$poll = mysqli_fetch_assoc($pollResult);

// Check if poll requires login
if ($poll['require_login'] && !isLoggedIn()) {
    setMessage('You must be logged in to vote in this poll.', 'error');
    redirect('login.php');
}

// Check if poll has ended
if ($poll['end_date'] && strtotime($poll['end_date']) < time()) {
    setMessage('This poll has ended.', 'error');
    redirect('poll-details.php?id=' . $poll_id);
}

// Check if option exists for this poll
$optionQuery = "SELECT * FROM poll_options WHERE id = $option_id AND poll_id = $poll_id";
$optionResult = mysqli_query($conn, $optionQuery);

if (mysqli_num_rows($optionResult) == 0) {
    setMessage('Invalid option selected.', 'error');
    redirect('poll-details.php?id=' . $poll_id);
}

// Check if user has already voted
$user_id = isLoggedIn() ? $_SESSION['user_id'] : NULL;
$ip_address = $_SERVER['REMOTE_ADDR'];

$voteCheckQuery = "";
if ($user_id) {
    $voteCheckQuery = "SELECT * FROM votes WHERE poll_id = $poll_id AND user_id = $user_id";
} else {
    $voteCheckQuery = "SELECT * FROM votes WHERE poll_id = $poll_id AND ip_address = '$ip_address' AND user_id IS NULL";
}

$voteCheckResult = mysqli_query($conn, $voteCheckQuery);

if (mysqli_num_rows($voteCheckResult) > 0) {
    // User has already voted
    if ($poll['multiple_choices']) {
        // Allow multiple choices - check if this specific option has been voted for
        $optionVoteQuery = "SELECT * FROM votes WHERE poll_id = $poll_id AND option_id = $option_id";
        
        if ($user_id) {
            $optionVoteQuery .= " AND user_id = $user_id";
        } else {
            $optionVoteQuery .= " AND ip_address = '$ip_address' AND user_id IS NULL";
        }
        
        $optionVoteResult = mysqli_query($conn, $optionVoteQuery);
        
        if (mysqli_num_rows($optionVoteResult) > 0) {
            setMessage('You have already voted for this option.', 'error');
            redirect('poll-details.php?id=' . $poll_id);
        }
    } else {
        // Single choice only
        setMessage('You have already voted in this poll.', 'error');
        redirect('poll-details.php?id=' . $poll_id);
    }
}

// Record the vote
$user_id_sql = $user_id ? $user_id : "NULL";
$sql = "INSERT INTO votes (poll_id, option_id, user_id, ip_address) VALUES ($poll_id, $option_id, $user_id_sql, '$ip_address')";

if (mysqli_query($conn, $sql)) {
    setMessage('Vote recorded successfully!', 'success');
} else {
    setMessage('Failed to record vote: ' . mysqli_error($conn), 'error');
}

// Redirect back to poll details
redirect('poll-details.php?id=' . $poll_id);
?> 