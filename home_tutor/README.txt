ONLINE HOME TUTOR MANAGEMENT SYSTEM  (BCA project - PHP + MySQL)
================================================================

HOW TO RUN (XAMPP, Windows)
1. Install and open XAMPP. Click START for both "Apache" and "MySQL".
2. Copy the whole folder "home_tutor" into:   C:\xampp\htdocs\
   (final path: C:\xampp\htdocs\home_tutor\index.php)
3. Open  http://localhost/phpmyadmin  in your browser.
   - Click the "Import" tab  ->  "Choose file"
   - Select  home_tutor\database\home_tutor_db.sql  ->  click "Import" at the bottom.
   (The file creates the database "home_tutor_db" by itself.)
4. Open  http://localhost/home_tutor/  and start using the site.

DEMO LOGINS (from the sample data)
  Admin   : admin@hometutor.com   /  Admin@123
  Student : ram@example.com       /  Password@123
  Tutor   : anita@example.com     /  Password@123   (approved, shown in the tutor list)
  Tutor   : pooja@example.com     /  Password@123   (pending - waits for admin approval)

DATABASE SETTINGS
  File: config/db.php  (default XAMPP: user = root, password = empty).
  If you set a MySQL password, change $DB_PASS there.

FOLDER GUIDE
  index.php            Home page + top tutors
  register.php         Register (name, email, phone, role, password) with email validation
  login.php / logout.php
  dashboard.php        Sends the user to the correct dashboard
  tutors.php           Search tutors by subject, location, rating
  tutor_view.php       Tutor profile, reviews and booking request form
  student_dashboard.php  Student requests: cancel, delete, review
  tutor_dashboard.php    Tutor: accept / reject / complete requests
  profile.php          Edit profile (tutors also edit subjects, fee, etc.) + change password
  admin_dashboard.php  Statistics, tutors waiting for approval, print report
  admin_users.php      Approve / reject / delete users
  admin_requests.php   View / delete all booking requests
  config/db.php        Database connection + small helper functions
  includes/            functions.php (validation, login helpers), header.php, footer.php
  assets/css/style.css Styles
  assets/js/validate.js  Browser-side validation (email, phone, password)
  database/home_tutor_db.sql  Database tables + sample data

RULES USED
  - Email: must look like name@domain.com and must be unique.
  - Phone: 10-digit Nepali mobile number starting with 96, 97 or 98.
  - Password: 8-64 characters, at least one letter and one number (stored hashed).
  - Students are approved immediately; tutors need admin approval before they appear in search.
