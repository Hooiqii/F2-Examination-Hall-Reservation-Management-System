<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if the user has already submitted feedback
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM feedback WHERE user_id = ?";
$statement = $conn->prepare($query);
$statement->bind_param("i", $user_id);
$statement->execute();
// Store the result set
$statement->store_result();

// Get the number of rows
$num_rows_of_feedback = $statement->num_rows;
$query2 = "SELECT * FROM booking WHERE user_id = ? AND (bk_status='Approve' or bk_status ='Pending')";
$statement2 = $conn->prepare($query2);
$statement2->bind_param("i", $user_id);
$statement2->execute();
// Store the result set
$statement2->store_result();
// Get the number of rows
$num_rows_of_booking = $statement2->num_rows;

if($num_rows_of_booking == 0){
    echo "<script>alert('A booking must be made before a feedback form can be submitted.'); window.location.href = 'index.php';</script>";
    exit();
}
if ($num_rows_of_feedback >= $num_rows_of_booking ) {
    echo "<script>alert('Feedback can only be submitted once per booking.'); window.location.href = 'index.php';</script>";
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $satisfaction = $_POST['satisfaction'];
    $easeOfUse = $_POST['easeOfUse'];
    $functionality = $_POST['functionality'];
    $feature = $_POST['feature'];
    $performance = $_POST['performance'];
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];

    // Insert feedback into the database
    $query = "INSERT INTO feedback (satisfaction, easeOfUse, functionality, feature, performance, comment, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $statement = $conn->prepare($query);
    $statement->bind_param("iiisisi", $satisfaction, $easeOfUse, $functionality, $feature, $performance, $comment, $user_id);
    if ($statement->execute()) {
        // Feedback submitted successfully
        echo "<script>alert('Thank you for your feedback!'); window.location.href = 'index.php';</script>";
    } else {
        // Error occurred while inserting feedback
        echo "<script>alert('Failed to submit feedback!');</script>";
        echo $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/feedback_user.css">
    <title>Feedback | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <!-- need to change depend on user type -->
    <?php require_once('navbar_user.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Feedback</h1>
        <div class="border-wrapper">
            <form id="feedbackForm" action="feedback_user.php" method="POST" onsubmit="return validateForm()">
                <span class="feedback_content">
                    <label for="rating">Overall Satisfaction</label>
                    <p>Rate your overall satisfaction with the Examination Hall Reservation Management System.<span style="color: red">*</span></p>
                    <input type="hidden" name="satisfaction" id="satisfaction" value="">
                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                        <i class="far fa-star" data-value="<?php echo $i; ?>"></i>
                    <?php } ?>
                </span>
                <hr>
                <span class="feedback_content">
                    <label for="rating">Ease Of Use</label>
                    <p>How easy was it to navigate the system and reserve examination halls?<span style="color: red">*</span></p>
                    <input type="hidden" name="easeOfUse" id="easeOfUse" value="">
                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                        <i class="far fa-star" data-value="<?php echo $i; ?>"></i>
                    <?php } ?>
                </span>
                <hr>
                <span class="feedback_content">
                    <label for="rating">Functionality</label>
                    <p>Did the system meet your needs for reserving examination halls?<span style="color: red">*</span></p>
                    <input type="hidden" name="functionality" id="functionality" value="">
                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                        <i class="far fa-star" data-value="<?php echo $i; ?>"></i>
                    <?php } ?>
                    <p>Were there any features you found particularly helpful or lacking?</p>
                    <input type="text" id="feature" name="feature" placeholder="Enter your comments or feedback here...">
                </span>
                <hr>
                <span class="feedback_content">
                    <label for="rating">Performance</label>
                    <p>How would you rate the speed and responsiveness of the system?<span style="color: red">*</span></p>
                    <input type="hidden" name="performance" id="performance" value="">
                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                        <i class="far fa-star" data-value="<?php echo $i; ?>"></i>
                    <?php } ?>
                </span>
                <hr>
                <span class="feedback_content">
                    <label for="rating">Additional Comment</label>
                    <p>Please share any additional comments or feedback you have about the system.</p>
                    <input type="text" id="comment" name="comment" placeholder="Enter your comments or feedback here...">
                </span>
                <input type="submit" value="Submit" onclick="return confirm('Are you sure you want to submit the feedback?');">
            </form>
        </div>
    </div>

    <?php require_once('footer.php'); ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const satisfactionStars = document.querySelectorAll(".feedback_content:nth-of-type(1) .far.fa-star");
            const easeOfUseStars = document.querySelectorAll(".feedback_content:nth-of-type(2) .far.fa-star");
            const functionalityStars = document.querySelectorAll(".feedback_content:nth-of-type(3) .far.fa-star");
            const performanceStars = document.querySelectorAll(".feedback_content:nth-of-type(4) .far.fa-star");

            function handleStarClick(stars, satisfactionInput) {
                return function(event) {
                    const clickedStarValue = parseInt(event.target.getAttribute("data-value"));
                    satisfactionInput.value = clickedStarValue;

                    stars.forEach(function(star) {
                        const starValue = parseInt(star.getAttribute("data-value"));
                        if (starValue <= clickedStarValue) {
                            star.classList.remove("far");
                            star.classList.add("fas");
                        } else {
                            star.classList.remove("fas");
                            star.classList.add("far");
                        }
                    });
                };
            }

            satisfactionStars.forEach(function(star) {
                const satisfactionInput = document.getElementById("satisfaction");
                star.addEventListener("click", handleStarClick(satisfactionStars, satisfactionInput));
            });

            easeOfUseStars.forEach(function(star) {
                const easeOfUseInput = document.getElementById("easeOfUse");
                star.addEventListener("click", handleStarClick(easeOfUseStars, easeOfUseInput));
            });

            functionalityStars.forEach(function(star) {
                const functionalityInput = document.getElementById("functionality");
                star.addEventListener("click", handleStarClick(functionalityStars, functionalityInput));
            });

            performanceStars.forEach(function(star) {
                const performanceInput = document.getElementById("performance");
                star.addEventListener("click", handleStarClick(performanceStars, performanceInput));
            });
        });

        function validateForm() {
            const satisfaction = document.getElementById("satisfaction").value;
            const easeOfUse = document.getElementById("easeOfUse").value;
            const functionality = document.getElementById("functionality").value;
            const performance = document.getElementById("performance").value;

            if (!satisfaction || !easeOfUse || !functionality || !performance) {
                alert("Please fill in all required rating fields.");
                return false;
            }

            return true;
        }
    </script>

</body>

</html>
