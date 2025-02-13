<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if the logged-in user is an admin
if ($_SESSION['role'] !== 'admin') {
    // Redirect to login page or display an error page
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Feedback | F2 Examination Hall</title>
    <style>
        body,
        html {
            font-family: Cambria, Georgia, serif;
        }

        .content-wrapper {
            margin-left: 240px;
            padding: 10px 20px;
            background-color: #fff;
            min-height: calc(100vh - 50px);
            /* 100% viewport height minus header and footer heights */
        }

        h1 {
            margin-top: 0;
            margin-bottom: 15px;
        }

        .border-wrapper {
            border: 1px solid #000000;
            padding: 20px;
            margin-bottom: 20px;
        }

        .function {
            display: flex;
            justify-content: space-between;
            align-items: right;
            margin-bottom: 15px;
        }

        #tableButton {
            padding: 12px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: Cambria, Georgia, serif;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        #tableButton:hover {
            background-color: #0056b3;
            color: white;
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3);
        }

        #printButton {
            padding: 12px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: Cambria, Georgia, serif;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
            margin-right: 5px;
        }

        #printButton:hover {
            background-color: #218838;
            color: white;
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Feedback</h1>
        <div class="border-wrapper">
            <div class="function">
                <a href="feedback_admin.php"><button id="tableButton">Table View</button></a>
                    <button id="printButton">Print</button>
            </div>
            <canvas id="feedbackChart" width="800" height="400"></canvas>

            <?php
            // Fetch feedback data from the database
            $sql = "SELECT f.*, u.user_username 
            FROM feedback f
            INNER JOIN users u ON f.user_id = u.user_id";
            $result = $conn->query($sql);

            // Process feedback data
            $labels = ["Satisfaction", "Ease Of Use", "Functionality", "Performance"];
            $data = [0, 0, 0, 0]; // Initialize data array

            if ($result->num_rows > 0) {
                $totalFeedbacks = $result->num_rows;

                while ($row = $result->fetch_assoc()) {
                    $data[0] += $row["satisfaction"];
                    $data[1] += $row["easeOfUse"];
                    $data[2] += $row["functionality"];
                    $data[3] += $row["performance"];
                }

                // Calculate average ratings
                foreach ($data as &$value) {
                    $value /= $totalFeedbacks;
                }
            }

            ?>

            <script>
                var ctx = document.getElementById('feedbackChart').getContext('2d');
                var myChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{
                            label: 'Average Ratings',
                            data: <?php echo json_encode($data); ?>,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });


                // Function to print the chart as a PDF
                document.getElementById('printButton').addEventListener('click', function() {
                    // Ask for confirmation before printing
                    if (confirm("Are you sure you want to print the chart?")) {
                        var canvas = document.getElementById('feedbackChart');
                        var pdf = new jsPDF('portrait', 'mm', 'a4'); // A4 portrait mode

                        // Load and add the logo
                        var logoImg = new Image();
                        logoImg.onload = function() {
                            var logoWidth = 60; // Adjust logo width as needed
                            var logoHeight = 15; // Adjust logo height as needed
                            var logoX = (pdf.internal.pageSize.width - logoWidth) / 2;
                            var logoY = 15; // Adjust logo top margin as needed
                            pdf.addImage(logoImg, 'PNG', logoX, logoY, logoWidth, logoHeight); // Add logo

                            // Add space between logo and title
                            var space = 10; // Adjust space between logo and title as needed
                            logoY += logoHeight + space;

                            // Header - Title
                            var title = "F2 Examination Hall Reservation Management System";
                            var titleFontSize = 16;
                            var titleWidth = pdf.getStringUnitWidth(title) * titleFontSize / pdf.internal.scaleFactor;
                            var titleX = (pdf.internal.pageSize.width - titleWidth) / 2; // Centered horizontally
                            var titleY = logoY; // Title Y position adjusted
                            pdf.setFont("times"); // Set font to Times New Roman
                            pdf.setFontSize(titleFontSize);
                            pdf.setTextColor(0, 0, 0);
                            pdf.text(title, titleX, titleY, {
                                align: 'center'
                            }); // Center title

                            // Add introduction content
                            var introductionContent = "This report presents the average ratings of the F2 Examination Hall Reservation Management System based on user feedback. The system's performance across various aspects such as satisfaction, ease of use, functionality, and performance is analyzed and visualized in the following chart.";
                            var introductionFontSize = 12;
                            var lineHeight = 7; // Adjust line height as needed
                            var availableWidth = pdf.internal.pageSize.width; // Initialize available width for text
                            var introductionLines = pdf.splitTextToSize(introductionContent, availableWidth);
                            var introductionY = titleY + 15; // Space between title and introduction
                            pdf.setFontSize(introductionFontSize);

                            // Center the introduction content horizontally
                            var leftMargin = (pdf.internal.pageSize.width - introductionLines.reduce((max, line) => Math.max(max, pdf.getStringUnitWidth(line) * introductionFontSize), 0) / pdf.internal.scaleFactor) / 2;

                            for (var i = 0; i < introductionLines.length; i++) {
                                pdf.text(leftMargin, introductionY + (lineHeight * i), introductionLines[i], {
                                    maxWidth: availableWidth
                                });
                            }


                            // Chart
                            var imgData = canvas.toDataURL('image/png');
                            var imgWidth = 180; // Adjust width as needed
                            var imgHeight = canvas.height * imgWidth / canvas.width; // Maintain aspect ratio
                            pdf.addImage(imgData, 'PNG', 15, introductionY + (lineHeight * introductionLines.length) + 10, imgWidth, imgHeight);

                            // Footer
                            pdf.setFontSize(10);
                            pdf.setTextColor(100, 100, 100);
                            pdf.text("Generated on: " + new Date().toLocaleString(), 15, pdf.internal.pageSize.height - 10);

                            pdf.save('feedback_chart.pdf');
                        };
                        logoImg.src = 'images/uthmLogo.png'; // Path to your logo image
                    }
                });
            </script>
        </div>
    </div>
    <?php require_once('footer.php'); ?>
</body>

</html>