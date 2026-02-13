# 🚀 Symfony 6 Web Application

This project is a **web application developed using Symfony 6.4**, following best practices in web development and software architecture.  
It is designed as part of an academic and professional learning process, focusing on clean code, scalability, and real-world use cases.

---

## 📌 Project Description

This application is built using the **Symfony framework** and aims to demonstrate:

- Proper project structure with MVC architecture
- Backend development with PHP and Symfony
- Database integration and CRUD operations
- Clean and maintainable code

---

## 🛠️ Technologies Used

- **PHP 8.1+**
- **Symfony 6.4 / Twig**
- **Composer**
- **MySQL**
- **HTML5 / TailWind **
- **Git & GitHub**
- **Symfony CLI**

---

## 📋 Prerequisites

Before installing the project, make sure you have the following tools installed:

- PHP 8.1 or higher
- Composer
- Symfony CLI
- Web server (XAMPP or WAMP recommended)
- Git
- Code editor (VS Code, PhpStorm, etc.)

---

## 🚀 Installation & Setup

Make sure PHP 8.1 or higher is installed and added to the system PATH. You can verify this by running:

php -v

Install Composer from https://getcomposer.org/download/ and verify the installation using:

composer -v

Install Symfony CLI using Windows PowerShell with the following commands:

Set-ExecutionPolicy RemoteSigned -Scope CurrentUser  
irm get.scoop.sh | iex  
scoop install symfony-cli  

After installation, verify Symfony CLI and system requirements:

symfony -v  
symfony check:requirements  

Clone the project repository and navigate to the project directory:

git clone https://github.com/USERNAME/PROJECT_NAME.git  
cd PROJECT_NAME  

Install project dependencies using Composer:

composer install  

Create a `.env.local` file and configure the database connection as follows:

DATABASE_URL="mysql://username:password@127.0.0.1:3306/database_name"

Run the application using the Symfony local server:

symfony serve  

Access the application in your browser at:

http://127.0.0.1:8000  

Alternatively, you can run the project using the PHP built-in server:

php -S localhost:8000 -t public  

Or by placing the project inside the `htdocs` (XAMPP) or `www` (WAMP) directory and accessing:

http://localhost/PROJECT_NAME/public/index.php
