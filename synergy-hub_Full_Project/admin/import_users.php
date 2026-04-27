<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] != UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Error uploading file!";
        header("Location: users.php");
        exit();
    }
    
    $handle = fopen($file['tmp_name'], 'r');
    $headers = fgetcsv($handle); // Skip header row
    
    $imported = 0;
    $errors = [];
    $batch = [];
    $batch_size = 100;
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) < 4) {
            $errors[] = "Invalid row format at line " . ($imported + 2);
            continue;
        }
        
        list($student_id, $name, $email, $password) = $data;
        $role = isset($data[4]) ? $data[4] : 'User';
        $status = isset($data[5]) ? $data[5] : 'Active';
        $points = isset($data[6]) ? intval($data[6]) : 0;
        
        // Validate data
        if (empty($name) || empty($email) || empty($password)) {
            $errors[] = "Missing required fields for $email";
            continue;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format: $email";
            continue;
        }
        
        // Check if email already exists
        $check_sql = "SELECT UserID FROM Users WHERE Email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Email already exists: $email";
            continue;
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $batch[] = [
            'student_id' => $student_id,
            'name' => $name,
            'email' => $email,
            'password' => $hashed_password,
            'role' => $role,
            'status' => $status,
            'points' => $points
        ];
        
        $imported++;
        
        // Insert batch
        if (count($batch) >= $batch_size) {
            insertBatch($conn, $batch);
            $batch = [];
        }
    }
    
    // Insert remaining batch
    if (!empty($batch)) {
        insertBatch($conn, $batch);
    }
    
    fclose($handle);
    
    // Log import
    $log_sql = "INSERT INTO ImportHistory (FileName, RecordsImported, Status, ImportedBy, ImportedAt) VALUES (?, ?, ?, ?, NOW())";
    $log_stmt = mysqli_prepare($conn, $log_sql);
    $file_name = $file['name'];
    $status = empty($errors) ? 'Success' : 'Partial Success';
    mysqli_stmt_bind_param($log_stmt, "sisi", $file_name, $imported, $status, $_SESSION['user_id']);
    mysqli_stmt_execute($log_stmt);
    
    if (empty($errors)) {
        $_SESSION['success'] = "Successfully imported $imported users!";
    } else {
        $_SESSION['success'] = "Imported $imported users with " . count($errors) . " errors.";
        $_SESSION['error'] = implode("<br>", array_slice($errors, 0, 5));
    }
    
    header("Location: users.php");
    exit();
}

function insertBatch($conn, $batch) {
    $values = [];
    $params = [];
    $types = '';
    
    foreach ($batch as $user) {
        $values[] = "(?, ?, ?, ?, ?, ?, ?, NOW())";
        $params[] = $user['student_id'];
        $params[] = $user['name'];
        $params[] = $user['email'];
        $params[] = $user['password'];
        $params[] = $user['role'];
        $params[] = $user['status'];
        $params[] = $user['points'];
        $types .= 'ssssssi';
    }
    
    $sql = "INSERT INTO Users (StudentID, Name, Email, PasswordHash, Role, MembershipStatus, PointsBalance, CreatedAt) 
            VALUES " . implode(',', $values);
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
}
?>