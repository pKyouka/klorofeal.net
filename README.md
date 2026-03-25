<div align="center">
<img alt="Klorofeal" src="https://img.shields.io/badge/Klorofeal-Inventory%20Management%20System-2ea44f?style=for-the-badge&logo=laravel&logoColor=white" />

# Klorofeal - Advanced Inventory & POS Management System

**A modern, feature-rich inventory management and point-of-sale system built with Laravel**

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-3.x-06B6D4?style=flat-square&logo=tailwind-css)
![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)

</div>

---

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Database Modules](#database-modules)
- [Development](#development)
- [Testing](#testing)
- [License](#license)

---

## 🎯 About

**Klorofeal** is a comprehensive inventory management and point-of-sale (POS) system designed for businesses that need a powerful solution to manage their inventory, sales, purchases, and warehouse operations. Built with Laravel 12 and modern web technologies, Klorofeal provides an intuitive interface and robust functionality for multi-warehouse operations.

---

## ✨ Features

### Core Functionality
- 🏪 **Multi-Workspace Support** - Manage multiple business locations or branches
- 📦 **Inventory Management** - Complete stock tracking and management
- 🛒 **Point of Sale (POS)** - Modern POS interface for fast checkout
- 🛍️ **Product Management** - Comprehensive product catalog with categories
- 📥 **Purchase Management** - Track supplier purchases and inventory intake
- 📊 **Reports & Analytics** - Detailed business insights and reporting
- 🏭 **Warehouse Management** - Multi-warehouse operations and transfers
- 👥 **Role-Based Access Control** - Secure user and role management
- 🔒 **Multi-Tenant Architecture** - Workspace isolation and data security

### Technical Features
- Built with **Laravel 12** framework
- **Tailwind CSS** for modern responsive UI
- **Alpine.js** for interactive components
- **Vite** for fast build processes
- **DataTables** for advanced data handling
- **Payment Gateway Integration** (Midtrans for Indonesian payment processing)
- RESTful API architecture
- Comprehensive migration system
- Queue system for background jobs

---

## 🛠️ Tech Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | Laravel 12.x, PHP 8.2+ |
| **Frontend** | Tailwind CSS 3.x, Alpine.js 3.x |
| **Build Tool** | Vite 7.x |
| **Database** | MySQL/PostgreSQL (Laravel Agnostic) |
| **Payment** | Midtrans PHP SDK |
| **Testing** | PHPUnit 11.x |
| **Package Manager** | Composer, NPM |

---

## 📁 Project Structure

```
klorofeal.net/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   ├── Role.php
│   │   ├── User.php
│   │   ├── UserRole.php
│   │   └── Workspace.php
│   ├── Modules/
│   │   ├── Inventory/
│   │   ├── POS/
│   │   ├── Products/
│   │   ├── Purchase/
│   │   ├── Reports/
│   │   └── Warehouse/
│   ├── Services/
│   ├── Traits/
│   └── Scopes/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
├── config/
├── tests/
└── public/
```

---

## ⚙️ Requirements

- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Node.js / NPM**: 18+
- **Database**: MySQL 8.0+ or PostgreSQL 12+
- **Extensions**: mbstring, openssl, PDO

---

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/klorofeal.net.git
cd klorofeal.net
```

### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# JavaScript dependencies
npm install
```

### 3. Environment Configuration
```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed demo data (optional)
php artisan db:seed
```

### 5. Build Assets
```bash
# Development build
npm run dev

# Production build
npm run build
```

### 6. Start Development Server
```bash
# Quick start with all services
php artisan serve
npm run dev
php artisan queue:listen

# Or use the concurrent development command
composer run dev
```

Visit your application at `http://localhost:8000`

---

## 💻 Usage

### Initial Setup

1. **Create Admin Account** - During first launch, create your administrator account
2. **Configure Workspace** - Set up your primary business location/workspace
3. **Product Catalog** - Add your product categories and products
4. **POS Registers** - Configure point of sale terminals
5. **User Management** - Create staff accounts with appropriate roles

### Key Operations

- **Receiving Stock**: Use Purchase Management module to receive items
- **Selling Items**: Process sales through POS module
- **Inventory Tracking**: Monitor stock levels in Inventory module
- **Generating Reports**: Access Reports module for KPIs and analytics
- **Warehouse Transfers**: Move stock between locations using Warehouse module

---

## 📊 Database Modules

| Module | Purpose | Key Tables |
|--------|---------|-----------|
| Users | User & Role Management | `users`, `roles`, `user_roles` |
| Products | Product Catalog | `products`, `product_categories` |
| Suppliers | Vendor Management | `suppliers` |
| Purchase | Stock Intake | `purchases`, `purchase_items` |
| Sales | Point of Sale | `sales`, `sale_items` |
| Inventory | Stock Management | `stocks` |
| Workspace | Multi-tenant Operations | `workspaces` |

---

## 🔧 Development

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/YourTest.php

# Generate coverage report
php artisan test --coverage
```

### Code Quality
```bash
# Format code with Pint
composer run pint

# Check for errors
php artisan tinker
```

### Database Seeders
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder
```

---

## 🔐 Security Features

- ✅ Role-based access control (RBAC)
- ✅ Workspace data isolation
- ✅ Laravel Breeze authentication
- ✅ CSRF protection
- ✅ Secure payment processing
- ✅ Input validation and sanitization

---

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 👨‍💻 Contributing

Contributions are welcome! Feel free to submit issues and enhancement requests.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📞 Support

For support, please open an issue in the repository or contact the development team.

---

<div align="center">

**Made with ❤️ using Laravel**

</div>
