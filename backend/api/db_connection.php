<?php
// ==================== DATABASE CONNECTION ====================

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'geotraverse_erp';

/**
 * Get database connection
 * @return mysqli
 */
function getConnection() {
    global $db_host, $db_user, $db_password, $db_name;
    
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false, 
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Alternative function name for backward compatibility
 */
function getDbConnection() {
    return getConnection();
}

/**
 * Execute query and return results as array
 * @param mysqli $conn
 * @param string $sql
 * @param string $types
 * @param array $params
 * @return array
 */
function executeQuery($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    $stmt->close();
    return $data;
}

/**
 * Execute update/insert/delete and return affected rows
 * @param mysqli $conn
 * @param string $sql
 * @param string $types
 * @param array $params
 * @return int
 */
function executeUpdate($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

/**
 * Get single row from query
 * @param mysqli $conn
 * @param string $sql
 * @param string $types
 * @param array $params
 * @return array|null
 */
function getSingleRow($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}
?>