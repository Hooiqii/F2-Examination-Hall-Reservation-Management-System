<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if the booking ID is set in the URL & retrieve counter from bookingList.php
if (isset($_GET['id'])) {
    $bookingId = $_GET['id'];

    // Fetch details of the specific booking based on the ID
    $sql = "SELECT * FROM booking WHERE bk_id = $bookingId";
    $result = $conn->query($sql);
    $booking = $result->fetch_assoc(); // Fetch a single booking record
} else {
    // Redirect or display an error message if the ID is not provided
    header('Location: bookingList.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="styles/bookingDetails.css">
    <title>Booking Details | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_user.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Booking Details</h1>
        <div class="border-wrapper">
            <div class="function">
                <div class="id"><h3>Booking ID -- <?php echo $booking['bk_id']; ?></h3></div>
                <button id="printButton">Print</button>
            </div>
            <p><strong>Reservation Purpose:</strong> <?php echo $booking['bk_purpose']; ?></p>
            <p><strong>Course Code:</strong> <?php echo $booking['bk_courseCode']; ?></p>
            <p><strong>Faculty/Department:</strong> <?php echo $booking['bk_facDepartment']; ?></p>
            <p><strong>Applicant's Name:</strong> <?php echo $booking['bk_name']; ?></p>
            <p><strong>Contact Number:</strong> <?php echo $booking['bk_contactNo']; ?></p>
            <p><strong>Floor Selection:</strong> <?php echo $booking['bk_floorSelection']; ?></p>
            <p><strong>Start Date:</strong> <?php echo $booking['bk_startDate']; ?></p>
            <p><strong>End Date:</strong> <?php echo $booking['bk_startDate']; ?></p>
            <p><strong>Start Time:</strong> <?php echo $booking['bk_startTime']; ?></p>
            <p><strong>End Time:</strong> <?php echo $booking['bk_endTime']; ?></p>
            <p><strong>Number of Participants:</strong> <?php echo $booking['bk_participantNo']; ?></p>
            <p><strong>File Attachment:</strong> <?php echo $booking['bk_fileAttachment']; ?></p>
            <p><strong>Remark:</strong> <?php echo $booking['bk_remark']; ?></p>
        </div>
    </div>

    <?php require_once('footer.php'); ?>

    <script>
       document.getElementById('printButton').addEventListener('click', function () {
    // Ask for confirmation before printing
    if (confirm("Are you sure you want to print the booking details?")) {
        var doc = new jsPDF('portrait', 'mm', 'a4'); // A4 portrait mode

        // Load and add the logo
        var logoImg = new Image();
        logoImg.onload = function() {
            var logoWidth = 60; // Adjust logo width as needed
            var logoHeight = 15; // Adjust logo height as needed
            var logoX = (doc.internal.pageSize.width - logoWidth) / 2;
            var logoY = 15; // Adjust logo top margin as needed
            doc.addImage(logoImg, 'PNG', logoX, logoY, logoWidth, logoHeight); // Add logo

            // Add space between logo and title
            var space = 10; // Adjust space between logo and title as needed
            logoY += logoHeight + space;

            // Header - Title
            var title = "F2 Examination Hall Reservation Management System";
            var titleFontSize = 16;
            var titleWidth = doc.getStringUnitWidth(title) * titleFontSize / doc.internal.scaleFactor;
            var titleX = (doc.internal.pageSize.width - titleWidth) / 2; // Centered horizontally
            var titleY = logoY; // Title Y position adjusted
            doc.setFont("times"); // Set font to Times New Roman
            doc.setFontSize(titleFontSize);
            doc.setTextColor(0, 0, 0);
            doc.text(title, titleX, titleY, {
                align: 'center'
            }); // Center title

            // Add introduction content
            var introductionContent = "This report provides the details of the booking made for the F2 Examination Hall. Below are the specifics of the reservation.";
            var introductionFontSize = 12;
            var lineHeight = 7; // Adjust line height as needed
            var availableWidth = doc.internal.pageSize.width - 20; // Adjust available width for text
            var introductionLines = doc.splitTextToSize(introductionContent, availableWidth);
            var introductionY = titleY + 15; // Space between title and introduction
            doc.setFontSize(introductionFontSize);

            // Center the introduction content horizontally
            var leftMargin = (doc.internal.pageSize.width - availableWidth) / 2;

            for (var i = 0; i < introductionLines.length; i++) {
                doc.text(leftMargin, introductionY + (lineHeight * i), introductionLines[i], {
                    maxWidth: availableWidth
                });
            }

            // Define table data
            var tableData = [
                ["Booking ID", <?php echo json_encode($booking['bk_id']); ?>],
                ["Reservation Purpose", <?php echo json_encode($booking['bk_purpose']); ?>],
                ["Course Code", <?php echo json_encode($booking['bk_courseCode']); ?>],
                ["Faculty/Department", <?php echo json_encode($booking['bk_facDepartment']); ?>],
                ["Applicant's Name", <?php echo json_encode($booking['bk_name']); ?>],
                ["Contact Number", <?php echo json_encode($booking['bk_contactNo']); ?>],
                ["Floor Selection", <?php echo json_encode($booking['bk_floorSelection']); ?>],
                ["Start Date", <?php echo json_encode($booking['bk_startDate']); ?>],
                ["End Date", <?php echo json_encode($booking['bk_endDate']); ?>],
                ["Start Time", <?php echo json_encode($booking['bk_startTime']); ?>],
                ["End Time", <?php echo json_encode($booking['bk_endTime']); ?>],
                ["Number of Participants", <?php echo json_encode($booking['bk_participantNo']); ?>],
                ["File Attachment", <?php echo json_encode($booking['bk_fileAttachment']); ?>],
                ["Remark", <?php echo json_encode($booking['bk_remark']); ?>]
            ];

            // Replace null or empty fields with "-"
            tableData.forEach(function(row) {
                row.forEach(function(cell, index) {
                    if (!cell) {
                        row[index] = "-";
                    }
                });
            });

            // Add table with reservation details
            doc.autoTable({
                startY: introductionY + (lineHeight * introductionLines.length) + 10,
                head: [['Field', 'Details']],
                body: tableData,
                theme: 'striped',
                styles: {
                    cellPadding: 3,
                    fontSize: 12,
                    valign: 'middle',
                    overflow: 'linebreak',
                    tableWidth: 'auto'
                },
                headStyles: {
                    halign: 'center' // Center-align header text
                },
                columnStyles: {
                    0: {cellWidth: 50, halign: 'left'}, // Field column width and left-align
                    1: {cellWidth: 130, halign: 'left'} // Details column width and left-align
                },
                didDrawCell: function(data) {
                    doc.setDrawColor(0); // Set border color
                    doc.setLineWidth(0.1); // Set border width
                    doc.rect(data.cell.x, data.cell.y, data.cell.width, data.cell.height); // Draw border
                }
            });

            // Add footer
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 10, 290);

            doc.save('booking_details.pdf');
        };
        logoImg.src = 'images/uthmLogo.png'; // Path to your logo image
    }
});

    </script>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>