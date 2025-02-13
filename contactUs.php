<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | F2 Examination Hall</title>
    <style>
        body,
        html {
            font-family: Cambria, Georgia, serif;
        }

        .content-wrapper {
            margin-left: 240px;
            padding: 10px 20px;
            background-color: #fff;

        }

        h1 {
            margin-top: 0;
            margin-bottom: 15px;
        }

        h2 {
            margin-top: 0;
        }

        h3 {
            margin-top: 40px;
        }

        .border-wrapper {
            border: 1px solid #000000;
            padding: 20px;
            margin-bottom: 20px;
        }

        .content-box {
            display: flex;
            justify-content: space-between;
        }

        .content-box-left p i {
            margin-right: 15px;
            /* Add space between icon and text */
        }

        #email,
        #contact {
            color: #007bff;
        }

        #email:hover {
            color: #0056b3;
        }

        .icon {
            display: flex;
            gap: 15px;
        }

        .icon a {
            text-decoration: none;
        }

        .icon a i {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 50px;
            height: 50px;
            font-size: 24px;
            background-color: #dedede;
            border-radius: 50%;
            color: #000;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-size: cover;
            background-position: center;
        }

        .icon a:nth-child(1) i:hover {
            background-color: #3b5998;
            color: #fff;
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .icon a:nth-child(2) i:hover {
            background-image: url('styles/styleImages/instagram_logo_bg_color.jpg');
            color: #fff;
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .icon a:nth-child(3) i:hover {
            background-color: #000;
            color: #fff;
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .icon a:nth-child(4) i:hover {
            background-color: #CC0000;
            color: #fff;
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .icon a:nth-child(5) i:hover {
            background-color: #075E54;
            color: #fff;
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_user.php'); ?>
    <?php require_once('breadcrumb.php'); ?>

    <div class="content-wrapper">
        <h1>Contact Us</h1>
        <div class="border-wrapper">
            <div class="content-box">
                <div class="content-box-left">
                    <h2>Pejabat Pengurusan Akademik UTHM</h2>
                    <p>We are here to assist with any inquiries you may have and eagerly anticipate your communication.</p>
                    <p><i class="fa-solid fa-location-dot"></i>&nbsp;&nbsp;Universiti Tun Hussein Onn Malaysia, 86400 Parit Raja, Batu Pahat, Johor</p>
                    <p><i class="fa-solid fa-phone"></i><a href="https://api.whatsapp.com/send?phone=6074537696" id="contact">+607-4537696</a></p>
                    <!-- <p><i class="fa-solid fa-fax"></i>+607-4536085</p> -->
                    <p><i class="fa-solid fa-envelope"></i><a href="mailto:ppa@uthm.edu.my" id="email">ppa@uthm.edu.my</a></p>

                    <h3>Damage Complaint</h3>
                    <p><i class="fa-solid fa-phone"></i><a href="https://api.whatsapp.com/send?phone=6074537696" id="contact">+607-4537696</a></p>
                    <p><i class="fa-solid fa-envelope"></i><a href="mailto:pphadmin@uthm.edu.my" id="email">pphadmin@uthm.edu.my</a></p>

                    <h3>Find Us</h3>
                    <div class="icon">
                        <a href="https://www.facebook.com/uthmjohor/"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/uthmjohor/"><i class="fa-brands fa-instagram"></i></a>
                        <a href="https://x.com/uthmjohor?lang=en"><i class="fa-brands fa-x-twitter"></i></a>
                        <a href="https://www.youtube.com/c/UTHMTV"><i class="fa-brands fa-youtube"></i></a>
                        <a href="https://api.whatsapp.com/send?phone=6074537696"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="content-box-right">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3987.7203957121633!2d103.08223907496689!3d1.858125498124906!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31d05eaa55064201%3A0xf01530984f17faa9!2sPejabat%20Pengurusan%20Akademik%20UTHM!5e0!3m2!1sen!2smy!4v1718981218990!5m2!1sen!2smy" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

        </div>
    </div>
    <?php require_once('footer.php'); ?>
</body>

</html>