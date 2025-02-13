# F2 Examination Hall Reservation Management System

## Introduction
Welcome to the F2 Examination Hall Reservation Management System! This system aims to improve operational efficiency by automating the reservation process for UTHM community. Please follow the instructions below to set up and run the system on your local machine.

## Prerequisites
- A local web server environment such as XAMPP 
- A web browser

## Installation Instructions

### Step 1: Download and Install Localhost Server
1. Download XAMPP or WAMP from their official websites:
   - XAMPP: https://www.apachefriends.org/index.html
2. Follow the instructions on the website to install the chosen software on your machine.

### Step 2: Extract the F2 Examination Hall Reservation Management System Files
1. Download the `F2 Examination Hall Reservation Management System.zip` file from the provided source.
2. Extract the contents of the `F2 Examination Hall Reservation Management System.zip` file.
   - On Windows: Right-click the zip file and select "Extract All..."
   - On Mac: Double-click the zip file to extract it.

### Step 3: Move Files to htdocs Directory
1. Locate the extracted `F2 Examination Hall Reservation Management System` folder.
2. Copy the `F2 Examination Hall Reservation Management System` folder to the `htdocs` directory of your localhost server and rename it to `FYP`.
   - For XAMPP: The `htdocs` directory is usually found in `C:\xampp\htdocs`

### Step 4: Setting up database (NOT COMPULSORY) 
1. Open and run the XAMPP Software.
2. Start the "Apache" and "MySQL".
3. Click the button of "Admin" of MySQL to go to the http://localhost/phpmyadmin/
4. At the top navigation bar, click on the "Import" button.
5. At the "File to import" section, click on the "Choose File" button and select the "fyp_db.sql" file provided in the database folder.
6. Click "Go" button located at the bottom right of the page.

### Step 4: Start the Localhost Server
1. Open the XAMPP control panel.
2. Start the Apache server.
   - For XAMPP: Click the "Start" button next to Apache.

### Step 5: Run the Application (user)
1. Open your web browser.
2. Type `http://localhost/FYP/register.php` in the address bar and press Enter.
3. The register page F2 Examination Hall Reservation Management System should now be running on your local server.
4. You can now try to login the system and explore it.

### Step 5: Run the Application (administrator)
1. Open your web browser.
2. Type `http://localhost/FYP/login.php` in the address bar and press Enter.
3. The login page of F2 Examination Hall Reservation Management System should now be running on your local server.
4. You can try to login using 
	admin name: admin1
	password: Admin@123
    to log in to the admin panel.
5. You can now try to login the system and explore it.


## For first-time usage - the administrator needs to upload the "User Manual.pdf" via the http://localhost/FYP/userManual_admin.php so that it will be shown on the user site

## Troubleshooting
- If the application does not load, ensure that the Apache server is running.
- Verify that the `F2 Examination Hall Reservation Management System' folder is correctly placed in the `htdocs` - Check that there are no port conflicts on port 80 (the default port for Apache).

## Support
For further assistance, please contact support at hooiqii168@gmail.com.

Here is the Youtube link for full system demonstration:
https://youtu.be/u20SnEVSCJc

Thank you for using the F2 Examination Hall Reservation Management System!
