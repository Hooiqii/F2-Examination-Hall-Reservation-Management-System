<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body, html{
            margin: 0;
            padding: 0;
            font-family: Cambria, Georgia, serif;
            height: 40%;
        }

        .wrapper {
            min-height: 100%;
            position: relative;
        }

        .footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px 20px 10px 10px;
            width: 100%;
            position: absolute;
            bottom: 0;
        }

        .footer p {
            margin: 5px 0;
            text-align: left;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <footer class="footer">
        <p>F2 Examination Hall Reservation Management System © <?php echo date("Y"); ?></p>
        <p>Developed by AI210308 | Universiti Tun Hussein Onn Malaysia</p>
    </footer>
</div>
</body>
</html>
