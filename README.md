# 🛒 Reshu Ecommerce Website

A simple and dynamic ecommerce web application built with **PHP**, **MySQL**, **HTML/CSS**, and basic JavaScript. It allows users to browse products, register/login, manage a shopping cart, and place orders.

---

## 📌 Features

-  User registration and login system
-  Product listing with price and stock info
-  Add to cart functionality
-  Cart management (edit quantity, remove items)
-  Checkout and order placement
-  Session-based user authentication
-  Modular PHP structure (reusable components)
-  Order summary and price breakdown (MRP, selling price)
-  Admin-ready file structure (can be extended)

---

## 📁 Project Structure

reshu-ecommerce/
|
|── config/
|└── db.php # Database connection
|
├── includes/
│ ├── footer.php 
│ └── header.php # Common header/navbar
│
├── admin/        #used by seller
│ ├── add_product.php    
│ ├── admin_products.php
│ ├── admin_orders.php
│ ├── delete_product.php
│ └── edit_product.php
│
├── assets/
│ └── images/
│
├── index.php
├── login.php
├── register.php
├── logout.php
├── profile.php
├── products.php
├── product.php
├── cart.php
├── my_order.php
├── search.php
├── track_order.php
|
├── project.sql
└── README.md # You're reading this!


---

## 🛠️ Technologies Used

| Tech         | Purpose                     |
|--------------|-----------------------------|
| PHP          | Backend scripting           |
| MySQL        | Relational database         |
| HTML & CSS   | UI layout and styling       |
| Bootstrap    | (Optional) Responsive UI    |
| JavaScript   | Interactions (minimal)      |

---

