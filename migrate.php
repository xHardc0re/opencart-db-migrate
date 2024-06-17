<?php

// Database connection settings
$oldDbConfig = [
    'host' => 'old_host',
    'user' => 'old_user',
    'password' => 'old_pass',
    'database' => 'old_database'
];

$newDbConfig = [
    'host' => 'new_host',
    'user' => 'new_user',
    'password' => 'new_pass',
    'database' => 'new_database'
];

// Define database prefixes
$oldPrefix = 'old_prefix';
$newPrefix = 'new_prefix';

// Connect to old and new databases
$oldDb = new mysqli($oldDbConfig['host'], $oldDbConfig['user'], $oldDbConfig['password'], $oldDbConfig['database']);
$newDb = new mysqli($newDbConfig['host'], $newDbConfig['user'], $newDbConfig['password'], $newDbConfig['database']);

if ($oldDb->connect_error) {
    die("Old DB Connection failed: " . $oldDb->connect_error);
}

if ($newDb->connect_error) {
    die("New DB Connection failed: " . $newDb->connect_error);
}

// Function to truncate tables
function truncateTables($newDb, $newPrefix, $tables)
{
    foreach ($tables as $table) {
        if ($newDb->query("TRUNCATE TABLE `{$newPrefix}{$table}`") === FALSE) {
            die("Error truncating table `{$newPrefix}{$table}`: " . $newDb->error);
        }
    }
}

// Function to get columns of a table
function getTableColumns($db, $table)
{
    $columns = [];
    $result = $db->query("SHOW COLUMNS FROM `{$table}`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        $result->free();
    }
    return $columns;
}

// Function to migrate a single table with dynamic column handling
function migrateTable($oldDb, $newDb, $oldPrefix, $newPrefix, $table)
{
    $newTableColumns = getTableColumns($newDb, "{$newPrefix}{$table}");
    if (empty($newTableColumns)) {
        die("No columns found in new table `{$newPrefix}{$table}`");
    }

    $query = "SELECT * FROM `{$oldPrefix}{$table}`";
    $result = $oldDb->query($query);

    if ($result === FALSE) {
        die("Error selecting from `{$oldPrefix}{$table}`: " . $oldDb->error);
    }

    $columnsToMigrate = array_intersect(getTableColumns($oldDb, "{$oldPrefix}{$table}"), $newTableColumns);
    $columnsList = implode(", ", array_map(function ($column) {
        return "`$column`";
    }, $columnsToMigrate));
    $placeholders = implode(", ", array_fill(0, count($columnsToMigrate), '?'));

    while ($row = $result->fetch_assoc()) {
        $values = [];
        foreach ($columnsToMigrate as $column) {
            $values[] = $row[$column];
        }

        $stmt = $newDb->prepare("INSERT INTO `{$newPrefix}{$table}` ($columnsList) VALUES ($placeholders)");

        if ($stmt === FALSE) {
            die("Error preparing statement for `{$newPrefix}{$table}`: " . $newDb->error);
        }

        $types = str_repeat('s', count($values)); // Assuming all values are strings; adjust as per your schema
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute() === FALSE) {
            die("Error executing statement for `{$newPrefix}{$table}`: " . $stmt->error);
        }

        $stmt->close();
    }

    $result->free();
    echo "$table migrated successfully.\n";
}

// Function to migrate categories and related tables
function migrateCategories($oldDb, $newDb, $oldPrefix, $newPrefix)
{
    $tables = ['category', 'category_description', 'category_path', 'category_to_store', 'category_to_layout'];

    truncateTables($newDb, $newPrefix, $tables);

    foreach ($tables as $table) {
        migrateTable($oldDb, $newDb, $oldPrefix, $newPrefix, $table);
    }
}

// Function to migrate products and related tables
function migrateProducts($oldDb, $newDb, $oldPrefix, $newPrefix)
{
    $tables = [
        'product', 'product_description', 'product_image', 'product_special',
        'product_to_category', 'product_to_store', 'product_to_layout',
        'product_attribute', 'product_option', 'product_option_value'
    ];

    truncateTables($newDb, $newPrefix, $tables);

    foreach ($tables as $table) {
        migrateTable($oldDb, $newDb, $oldPrefix, $newPrefix, $table);
    }
}

// Function to migrate customers and related tables
function migrateCustomers($oldDb, $newDb, $oldPrefix, $newPrefix)
{
    $tables = ['customer', 'address', 'customer_group', 'customer_reward', 'customer_transaction'];

    truncateTables($newDb, $newPrefix, $tables);

    foreach ($tables as $table) {
        migrateTable($oldDb, $newDb, $oldPrefix, $newPrefix, $table);
    }
}

// Function to migrate orders and related tables
function migrateOrders($oldDb, $newDb, $oldPrefix, $newPrefix)
{
    $tables = ['order', 'order_product', 'order_option', 'order_total', 'order_history', 'order_voucher'];

    truncateTables($newDb, $newPrefix, $tables);

    foreach ($tables as $table) {
        migrateTable($oldDb, $newDb, $oldPrefix, $newPrefix, $table);
    }
}

// Run migrations
migrateCategories($oldDb, $newDb, $oldPrefix, $newPrefix);
migrateProducts($oldDb, $newDb, $oldPrefix, $newPrefix);
migrateCustomers($oldDb, $newDb, $oldPrefix, $newPrefix);
migrateOrders($oldDb, $newDb, $oldPrefix, $newPrefix);

// Close database connections
$oldDb->close();
$newDb->close();
