# Project-<b color='blue'>MyShop</b>

<h1>Online Shop Management System - <span>My</span><span>Shop</span></h1>
<br>
<h2>Introduction</h2>
<p>
My Online Shop Management System is a web-based application built with PHP that helps local shop owners manage their products, categories, billing, and workers efficiently. It provides a modern, responsive interface powered by Tailwind CSS, Font Awesome, and other CDN-based tools for faster loading and cleaner design.

The system is designed to make day-to-day shop operations easier by providing features like product management, category handling, billing history, etc</p>
<h2>Tech Stack</h2>
<p>
Frontend:

Tailwind CSS
 (CDN) – Utility-first CSS framework

Font Awesome
 (CDN) – Open-source icons library

JavaScript (ES6+)
 – Dynamic client-side functionality

Chart.js
 (CDN) – Open-source charting library

Backend:

PHP
 – Core backend language

MySQL
 – Relational database

Other:

CDN integrations for styling, icons, and charts (all open-source)</p>
<h2>How to Run the Project</h2>
<p>
Follow these steps to set up and run the Online Shop Management System on your local machine:

1️⃣ Install Required Software

Download and install XAMPP (recommended) or WAMP/LAMP.

XAMPP Download

2️⃣ Download the Project

If you are using GitHub:

Click the green Code button on this repository.

Select Download ZIP.

Extract the ZIP file.

If you have the project on DVD, copy the project folder directly to your computer.

3️⃣ Move Project to Server Directory

Copy the project folder into your htdocs directory (for XAMPP):

C:\xampp\htdocs\online-shop-management

4️⃣ Start Apache & MySQL

Open XAMPP Control Panel

Start Apache and MySQL modules

5️⃣ Create Database

Open your browser and go to:

http://localhost/phpmyadmin/


Create a new database (example: shop_management_db).

Import the provided shop_management_db.sql file into this database.

6️⃣ Configure Database Connection

Open the file:

/config/db.php


Update it with your local database credentials:

$servername = "localhost";
$username   = "root";      // default in XAMPP
$password   = "";          // default is empty
$dbname     = "shop_management_db";

7️⃣ Run the Project

Now open the project in your browser:

http://localhost/online-shop-management/

8️⃣ Login / Signup
You said:
dont add clone info because i put full project in dvd for my college submission]
ChatGPT said:

Got it 👍 Since you’re submitting via DVD, I’ll remove the Git clone step and make it a manual setup guide. Here’s the cleaned step-by-step setup you can include in your README or a separate INSTALLATION.md for your college project:

⚙️ How to Run the Project

Follow these steps to set up and run the Online Shop Management System on your local machine:

1️⃣ Install Required Software

Download and install XAMPP (recommended) or WAMP/LAMP.

XAMPP Download

2️⃣ Copy Project Files

Copy the project folder from your DVD to your local machine.

Place it inside the htdocs directory (if using XAMPP):

C:\xampp\htdocs\online-shop-management

3️⃣ Start Apache & MySQL

Open the XAMPP Control Panel.

Start Apache and MySQL services.

4️⃣ Create Database

Open your browser and go to:

http://localhost/phpmyadmin/


Create a new database (example: shop_management_db).

Import the provided SQL file (shop_management_db.sql) into this database.

5️⃣ Configure Database Connection

Open the file:

/config/db.php


Update it with your local database details:

$servername = "localhost";
$username   = "root";      // default in XAMPP
$password   = "";          // default is empty
$dbname     = "shop_management_db";

6️⃣ Run the Project

Open your browser and visit:

http://localhost/online-shop-management/

7️⃣ Login / Signup

Use the signup page to register a worker/shop account.

Then, log in using the created credentials.</p>
<h2>Future Scope</h2>
<ul>
  <li>
User/customer portal to buy and track products online.</li>

<li>Advanced reporting with analytics dashboards.</li>

<li>Multi-shop support with centralized admin.</li>

<li>Payment gateway integration.</li></ul>

