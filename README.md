# OpenCart Quick N Dirty Migration Script

This script facilitates the migration of products, categories, customers, and orders.

## Overview

This PHP script connects to two databases:
- **Old Database:** OpenCart Database (`old_prefix`)
- **New Database:** OpenCart Database (`new_prefix`)

It migrates the following data tables:
- Products and related tables (`product`, `product_description`, etc.)
- Categories and related tables (`category`, `category_description`, etc.)
- Customers and related tables (`customer`, `address`, etc.)
- Orders and related tables (`order`, `order_product`, etc.)

## Setup

1. **Database Configuration:**
   - Update the `$oldDbConfig` and `$newDbConfig` arrays in the script with your database connection details (`host`, `user`, `password`, `database`).

2. **Prefixes:**
   - Adjust `$oldPrefix` and `$newPrefix` variables to match your database table prefixes.

3. **Run the Script:**
   - Execute the script (`php migrate.php`) from the command line or via a web server with PHP installed.

## Features

- Dynamic column handling to avoid errors when migrating data with schema differences.
- Truncates related tables in the new database before migration to ensure a clean slate.
- Error handling for database connections, SQL queries, and data insertion.
