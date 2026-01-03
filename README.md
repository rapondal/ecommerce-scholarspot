# ScholarSpot

## Short Description
ScholarSpot is a specialized e-commerce platform built with HTML,CSS,JAVASCRIPT,PHP and MySQL, designed to streamline the buying and selling of academic supplies and student essentials. The system features a secure checkout process and an administrative dashboard to manage inventory and user transactions efficiently.

## Technologies Used
* **Frontend:** HTML, Tailwind and Vanilla CSS, JavaScript
* **Backend:** PHP (OOP + MySQLi Prepared Statements)
* **Database:** MySQL

## Features
* User Authentication (Login/Register)
* Browse Products
* Shopping Cart Functionality
* Checkout & Order History
* Manage Users Account
* Admin Dashboard and Stock Management
* Products Management (CRUD)
* Orders Management
* Coupons Management
* Shipment Management
* Users Management
* Activity Logs
* Reports Management

## Installation Instructions
1. **Clone the repository:**
   git clone https://github.com/rapondal/ecommerce-scholarspot.git
2. **Import SQL file:**
   Open phpMyAdmin, create a new database, and import the file found in "/database/ecommerce_db.sql"
3. **Configure Database:**
   Update "db_connect.php" or your database connection file with your local credentials (DB Name, Username, Password).
4. **Start Local Server:**
   Move the folder to "htdocs" (XAMPP) and access it via "localhost/ecommerce"

## Admin Login 
* The admin account is located in the file /database/admin-account.txt

## Project Structure
* /database - SQL export files
* /docs - PDF project documentation
* /css - Stylesheets
* /js -  Scripts
* /images - All images
* /vendor - For 2FA authentication
* **User side**
* dashboard.php - Homepage
* products.php - Users can view/browse all products.
* orderdetails.php - Users can view their order history. 
* user_coupon.php - Users can view their available vouchers.
* userprofile.php - Users page to update their password.
* cart.php - Users page to view the summary of the order and to confirm the order so that can proceed to checkout.
* checkout.php - Users page to confirm and pay their orders.
* checkout_success.php - Confirmation that the order is confirmed.
* **Admin Side**
* admindashboard.php?page=dashboard - Admin dashboard that can view recent orders,recently added orders and low stock alerts. Can also view the total revenues, total orders, total products and total users.
* admindashboard.php?page=orders - Orders Management that admin can accept,delete and view the orders. Can also track the status of the orders.
* admindashboard.php?page=products - Products Management that admin can add,edit and delete the products.
* admindashboard.php?page=coupons - Coupons Management that admin can create and delete a coupon/vouchers for the user.
* admindashboard.php?page=shipment - Shipment Management that admin can view the order of the customer, view the route for shipment and create it to deliver to the exact location of the customer.
* admindashboard.php?page=users - Users Management that admin can view users information, track their status if its active or not and can manage and delete accounts.
* admindashboard.php?page=activity_logs - Activity Logs that admin can view all the customer and admin accounts and view the time/date, role, action and details.
* admindashboard.php?page=reports - Reports Management that admin can view the Analytics & Reports,Inventory Status reports, and print the digital report in a tabular form.

## Developers Information
* **Group Name: Sole Solero**
* **Members:**
* Seon Soquena  - BSIT 2-A - Role (Front-end Developer and Documentation)
* Renmar Doromal  - BSIT 2-A - Role (Back-end Developer and Documentation)
* Kyle Benjamin - BSIT 2-A - Role (UX/UI Designer)
* John Matthew Robles - BSIT 2-A - Role (UX/UI Designer)
* Ronjeru Taylaran - BSIT 2-A - Role (UX/UI Designer)
* Michael Angelo Heria - BSIT 2-A - Role (UX/UI Designer)
