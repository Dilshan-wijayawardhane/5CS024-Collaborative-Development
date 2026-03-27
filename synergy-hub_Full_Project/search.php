<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$term = isset($_GET['q']) ? $_GET['q'] : '';

if (strlen($term) < 2) {
    echo json_encode(['results' => []]);
    exit();
}

$search_term = "%" . $term . "%";
$results = [];


$facility_sql = "SELECT FacilityID as id, Name as title, Type as category, 'facility' as type, 
                        CONCAT(Type, ' - ', Status) as description 
                 FROM Facilities 
                 WHERE Name LIKE ? OR Type LIKE ? 
                 LIMIT 5";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
if ($facility_stmt) {
    mysqli_stmt_bind_param($facility_stmt, "ss", $search_term, $search_term);
    mysqli_stmt_execute($facility_stmt);
    $facility_result = mysqli_stmt_get_result($facility_stmt);
    while($row = mysqli_fetch_assoc($facility_result)) {
        $results[] = $row;
    }
}


$event_sql = "SELECT EventID as id, Title as title, Category as category, 'event' as type,
                     CONCAT(Location, ' - ', DATE_FORMAT(StartTime, '%b %d, %h:%i %p')) as description
              FROM Events 
              WHERE Title LIKE ? OR Description LIKE ? OR Location LIKE ?
              LIMIT 5";
$event_stmt = mysqli_prepare($conn, $event_sql);
if ($event_stmt) {
    mysqli_stmt_bind_param($event_stmt, "sss", $search_term, $search_term, $search_term);
    mysqli_stmt_execute($event_stmt);
    $event_result = mysqli_stmt_get_result($event_stmt);
    while($row = mysqli_fetch_assoc($event_result)) {
        $results[] = $row;
    }
}


$club_sql = "SELECT ClubID as id, Name as title, Category as category, 'club' as type,
                    Description as description
             FROM Clubs 
             WHERE Name LIKE ? OR Description LIKE ? OR Category LIKE ?
             LIMIT 5";
$club_stmt = mysqli_prepare($conn, $club_sql);
if ($club_stmt) {
    mysqli_stmt_bind_param($club_stmt, "sss", $search_term, $search_term, $search_term);
    mysqli_stmt_execute($club_stmt);
    $club_result = mysqli_stmt_get_result($club_stmt);
    while($row = mysqli_fetch_assoc($club_result)) {
        $results[] = $row;
    }
}


$transport_sql = "SELECT route_id as id, CONCAT(from_campus, ' → ', to_campus) as title, 
                         'transport' as type, CONCAT('Next: ', next_departure, ' - ', status) as description
                  FROM campus_transport 
                  WHERE from_campus LIKE ? OR to_campus LIKE ?
                  LIMIT 5";
$transport_stmt = mysqli_prepare($conn, $transport_sql);
if ($transport_stmt) {
    mysqli_stmt_bind_param($transport_stmt, "ss", $search_term, $search_term);
    mysqli_stmt_execute($transport_stmt);
    $transport_result = mysqli_stmt_get_result($transport_stmt);
    while($row = mysqli_fetch_assoc($transport_result)) {
        $results[] = $row;
    }
}


$gym_sql = "SELECT 'gym' as type, 'WLV Gym' as title, 'Facility' as category,
                   CONCAT('Status: ', status, ' - Closes: ', closing_time) as description
            FROM gym_status 
            WHERE status LIKE ? OR closing_time LIKE ?
            LIMIT 3";
$gym_stmt = mysqli_prepare($conn, $gym_sql);
if ($gym_stmt) {
    mysqli_stmt_bind_param($gym_stmt, "ss", $search_term, $search_term);
    mysqli_stmt_execute($gym_stmt);
    $gym_result = mysqli_stmt_get_result($gym_stmt);
    while($row = mysqli_fetch_assoc($gym_result)) {
        $results[] = $row;
    }
}

echo json_encode(['results' => $results]);
?>