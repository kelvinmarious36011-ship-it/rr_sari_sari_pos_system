# RR Sari-Sari Store POS Database

A simple and user-friendly **Point of Sale (POS) Database System** designed for RR Sari-Sari Store. This database manages user accounts, product inventory, sales transactions, and transaction details using MySQL.

## 📌 Project Overview

The RR Sari-Sari Store POS Database is designed to help manage daily store operations efficiently. It provides a structured database for storing product information, monitoring stock levels, recording customer purchases, and managing sales transactions.

## ✨ Features

- **User Management** – Stores login credentials and staff information.
- **Product Management** – Stores product names, prices, and available stock.
- **Inventory Tracking** – Monitors product quantities.
- **Sales Transactions** – Records transaction codes, total amounts, payments, and change.
- **Transaction Details** – Stores purchased products, quantities, unit prices, and subtotals.
- **Automatic Timestamps** – Records creation dates and transaction dates.
- **Sample Products** – Includes 25 sample sari-sari store products.

## 🛠️ Technologies Used

- **Database:** MySQL
- **Language:** SQL
- **Database Management Tool:** MySQL Workbench / phpMyAdmin
- **Database Name:** `rr_sari_sari_pos`

## 🗄️ Database Structure

The database consists of four main tables:

| Table Name | Description |
|---|---|
| `users` | Stores user login credentials and staff information. |
| `products` | Stores product names, prices, and stock quantities. |
| `transactions` | Stores sales transaction records. |
| `transaction_items` | Stores individual products included in each transaction. |

## 🔐 Default Login Credentials

The database includes a default administrator account.

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `admin123` |
| Full Name | Store Owner |

**Note:** The password is stored as a bcrypt hash in the database. Change the default credentials before deploying the system.

## 📦 Sample Products

The database includes the following sample products:

- Lucky Me Pancit Canton
- Lucky Me Instant Noodles
- Coca-Cola 1.5L
- Sprite 1.5L
- Royal Tru-Orange 1.5L
- C2 Green Tea 500ml
- Kopiko Coffee 3-in-1
- Nescafe Classic 3-in-1
- San Marino Corned Tuna
- Century Tuna Flakes
- Argentina Corned Beef
- Purefoods Hotdog 1kg
- Gardenia Bread
- Egg (per piece)
- Rice (per kilo)
- Bath Soap
- Colgate Toothpaste
- Safeguard Soap
- Detergent Powder
- Matches
- Candle
- Battery AA
- Load Card 50
- Load Card 100
- Load Card 200

## ⚙️ Installation and Setup

Follow these steps to install the database:

### Step 1: Install MySQL

Install one of the following tools:

- XAMPP
- MySQL Server
- MySQL Workbench

### Step 2: Start MySQL

If you are using XAMPP:

1. Open XAMPP Control Panel.
2. Start Apache (if using phpMyAdmin).
3. Start MySQL.

### Step 3: Create the Database

1. Open phpMyAdmin or MySQL Workbench.
2. Open the SQL query editor.
3. Copy the SQL database script.
4. Paste the script into the query editor.
5. Execute the SQL script.

The database will automatically create:

- Database: `rr_sari_sari_pos`
- Users table
- Products table
- Transactions table
- Transaction items table
- Default administrator account
- 25 sample products

### Step 4: Verify the Database

Run the following SQL commands to check whether the tables were successfully created:

```sql
USE rr_sari_sari_pos;

SHOW TABLES;

SELECT * FROM users;
SELECT * FROM products;
SELECT * FROM transactions;
SELECT * FROM transaction_items;
```

## 🔗 Database Relationships

The database uses the following relationship:

- One transaction can contain multiple transaction items.
- Each transaction item belongs to one transaction.
- Deleting a transaction automatically deletes its associated transaction items.

**Foreign Key:**

```sql
FOREIGN KEY (transaction_id)
REFERENCES transactions(id)
ON DELETE CASCADE
```

## 📊 Database Information

| Property | Details |
|---|---|
| Project Name | RR Sari-Sari Store POS |
| Database Name | rr_sari_sari_pos |
| Database Type | Relational Database |
| DBMS | MySQL |
| Tables | 4 |
| Sample Products | 25 |
| Default User | admin |

## 👨‍💻 Developer

**Project:** RR Sari-Sari Store POS System  
**Module:** Database Management  
**Purpose:** Point of Sale and Inventory Management

## 📄 License

This project is intended for educational and small-business use. You may modify and improve the database according to your project requirements.

---

**RR Sari-Sari Store POS Database**  
*Making store transactions and inventory management easier!*
