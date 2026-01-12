<?php
// Reset database by dropping and recreating

$mysqli = new mysqli("localhost", "root", "", "");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Drop database
echo "Dropping database eldogas...\n";
if ($mysqli->query("DROP DATABASE IF EXISTS eldogas")) {
    echo "Database dropped successfully\n";
} else {
    echo "Error dropping database: " . $mysqli->error . "\n";
}

// Create database
echo "Creating database eldogas...\n";
if ($mysqli->query("CREATE DATABASE eldogas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $mysqli->error . "\n";
}

$mysqli->close();
echo "Done!\n";
?>
