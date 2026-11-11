# Food Bridge - Community Food Sharing Platform

> **Developed by Team EtherX**  
> A civic platform connecting edible food surplus directly with local shelters, orphanages, community kitchens, and families in need across Gujarat.

---

## 🌟 Overview
**Food Bridge** is designed to reduce food waste and alleviate local hunger. The system provides two main workflows:
1. **Donors (Community Contributors)**: List surplus cooked meals, fresh produce, and dry ration kits with pickup windows and dietary assurances.
2. **Beneficiaries (Need Food)**: Request emergency food assistance (strictly 100% Pure Vegetarian) for individuals, families, and community shelters.
3. **Admin Console**: Real-time management of active cities, verified partner organizations, donation statuses, requests, and user records.

---

## 🚀 Key Features

- **Public Landing Page**: Clean responsive overview with platform metrics, mission, and direct access.
- **Secure Authentication**:
  - MySQL-backed session authentication with `password_hash()` (Bcrypt).
  - Registration, Login, Forgot Password, and OTP verification flow.
  - Role-based access control protecting all Customer and Admin pages.
- **Customer Portal**:
  - Real-time Dashboard with donation & request statistics.
  - Multi-step Donation Flow: Food Details → City Selection → Verified Organization Selection → Confirmation.
  - Food Request Workflow: Strictly 100% Pure Vegetarian meals.
  - Interactive "My Donations" and "My Requests" audit trails with detailed inspection views.
  - User Profile management.
- **Admin Operations Hub**:
  - Overview Analytics (Total Donors, Rescued Meals, Active Cities, Verified Centers).
  - User Management (view, activate/deactivate accounts).
  - Real-time Donation & Request status updating.
  - Partner Organization & Distribution City toggles.
- **Universal Gujarati Community Focus**:
  - Tailored for metropolitan and regional hubs (Rajkot, Ahmedabad, Surat, Vadodara, Bhavnagar, Gandhinagar).
  - 100% Pure Vegetarian standard.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.x (PDO MySQL)
- **Database**: MySQL 8.x
- **Frontend**: HTML5, CSS3 (Modular Styles), Vanilla JavaScript
- **Icons**: Font Awesome 6.5
- **Typography**: Inter (Google Fonts)

---

## 📂 Project Structure

```text
foodbridge/
├── admin/                     # Administrative portal
│   ├── css/                   # Admin CSS stylesheets
│   ├── admin-login.php        # Admin login
│   ├── cities.php             # City management
│   ├── dashboard.php          # Admin dashboard
│   ├── donations.php          # Donation management
│   ├── organizations.php      # NGO & Shelter partners
│   ├── requests.php           # Food requests management
│   └── users.php              # User accounts management
├── config/                    # Database & Auth configuration
│   ├── auth.php               # Authentication & session helpers
│   └── db.php                 # PDO MySQL connection
├── css/                       # Frontend styles
├── includes/                  # Reusable components
│   └── app-header.php         # Customer navigation header
├── js/                        # Client-side scripts
├── about.php                  # Platform mission & directives
├── confirmation.php           # Donation confirmation
├── dashboard.php              # Customer dashboard
├── database.sql               # Database schema & seed data
├── donate-food.php            # Step 1 of donation
├── donation-details.php       # Donation inspection
├── forgot-password.php        # Password recovery
├── index.php                  # Public landing page
├── login.php                  # Customer login
├── logout.php                 # User logout handler
├── my-donations.php           # Donor contribution history
├── my-request-details.php     # Request details
├── my-requests.php            # Request history
├── need-food.php              # Request food form (100% Pure Veg)
├── profile.php                # User profile
├── register.php               # Account creation
├── reset-password.php         # Password reset
├── select-city.php            # Step 2 of donation
├── select-organization.php    # Step 3 of donation
└── verify-otp.php             # OTP verification
```

---

## ⚙️ Setup & Installation

1. **Clone the repository**:
   ```bash
   git clone <repo-url>
   ```

2. **Move to Web Server**:
   Place the project directory in your web server root (e.g., `d:/laragon/www/foodbridge` or `c:/xampp/htdocs/foodbridge`).

3. **Import Database**:
   - Open MySQL (e.g., via phpMyAdmin, HeidiSQL, or MySQL CLI).
   - Import `database.sql` into MySQL:
     ```bash
     mysql -u root -p < database.sql
     ```
   - This creates the `foodbridge` database and populates initial cities, partner organizations, and seed accounts.

4. **Database Configuration**:
   Verify `config/db.php`:
   ```php
   $host = '127.0.0.1';
   $port = '3306';
   $dbname = 'foodbridge';
   $user = 'root';
   $pass = ''; // Enter your MySQL root password if set
   ```

5. **Run the Project**:
   Start Apache and MySQL in Laragon / XAMPP, and navigate to:
   ```
   http://localhost/foodbridge/
   ```

---

## 🔐 Default Demo Credentials

### Customer / User Portal
- **Login URL**: `http://localhost/foodbridge/login.php`
- **Email**: `dhruv.gohel@example.com`
- **Password**: `password123`

### Admin Portal
- **Login URL**: `http://localhost/foodbridge/admin/admin-login.php`
- **Email**: `ops.head@foodbridge.org`
- **Password**: `admin123`

---

## 👥 Authors
Developed with ❤️ by **Team EtherX**
