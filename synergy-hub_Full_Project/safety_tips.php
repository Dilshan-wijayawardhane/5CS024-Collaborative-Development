<?php
// safety_tips.php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT Name FROM Users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Check if tables exist
$tips_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'SafetyTips'");
$has_tips_table = mysqli_num_rows($tips_table_check) > 0;

$quiz_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'SafetyQuiz'");
$has_quiz_table = mysqli_num_rows($quiz_table_check) > 0;

$incidents_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'SafetyIncidents'");
$has_incidents_table = mysqli_num_rows($incidents_table_check) > 0;

// Get active emergency alerts for banner from EmergencyAlerts table
$critical_alert = null;
$alerts_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'EmergencyAlerts'");
if (mysqli_num_rows($alerts_table_check) > 0) {
    $alerts_sql = "SELECT * FROM EmergencyAlerts WHERE expires_at > NOW() AND severity = 'critical' ORDER BY created_at DESC LIMIT 1";
    $alerts_result = mysqli_query($conn, $alerts_sql);
    if ($alerts_result && mysqli_num_rows($alerts_result) > 0) {
        $critical_alert = mysqli_fetch_assoc($alerts_result);
    }
}

// Get safety tip of the day (random)
$tip_of_day = null;
if ($has_tips_table) {
    $tips_sql = "SELECT * FROM SafetyTips WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
    $tips_result = mysqli_query($conn, $tips_sql);
    if ($tips_result && mysqli_num_rows($tips_result) > 0) {
        $tip_of_day = mysqli_fetch_assoc($tips_result);
    }
}

// Get categories for filtering
$categories_result = false;
if ($has_tips_table) {
    $categories_sql = "SELECT DISTINCT category FROM SafetyTips WHERE is_active = 1 ORDER BY category";
    $categories_result = mysqli_query($conn, $categories_sql);
}

// Get all safety tips with optional category filter
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$all_tips_result = false;
$tips_count = 0;
if ($has_tips_table) {
    $where_clause = "WHERE is_active = 1";
    if ($category_filter && $category_filter !== 'all') {
        $where_clause .= " AND category = '$category_filter'";
    }
    if ($search_query) {
        $where_clause .= " AND (title LIKE '%$search_query%' OR content LIKE '%$search_query%' OR tags LIKE '%$search_query%')";
    }

    $all_tips_sql = "SELECT * FROM SafetyTips $where_clause ORDER BY 
        CASE 
            WHEN priority = 'high' THEN 1 
            WHEN priority = 'medium' THEN 2 
            WHEN priority = 'low' THEN 3 
        END, created_at DESC";
    $all_tips_result = mysqli_query($conn, $all_tips_sql);
    if ($all_tips_result) {
        $tips_count = mysqli_num_rows($all_tips_result);
    }
}

// Get recent safety incidents
$incidents_result = false;
if ($has_incidents_table) {
    $incidents_sql = "SELECT * FROM SafetyIncidents WHERE report_date > DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY report_date DESC LIMIT 5";
    $incidents_result = mysqli_query($conn, $incidents_sql);
}

// Get quiz questions
$quiz_result = false;
if ($has_quiz_table) {
    $quiz_sql = "SELECT * FROM SafetyQuiz WHERE is_active = 1 ORDER BY RAND() LIMIT 5";
    $quiz_result = mysqli_query($conn, $quiz_sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergy Hub - Safety Tips & Resources</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Emergency Banner */
        .emergency-banner {
            background: #ef4444;
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: pulse 2s infinite;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
        }

        .emergency-banner i {
            font-size: 24px;
            margin-right: 15px;
        }

        .emergency-banner-content {
            flex: 1;
        }

        .emergency-banner-title {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .emergency-banner-link {
            color: white;
            text-decoration: underline;
            font-weight: 600;
            white-space: nowrap;
            margin-left: 20px;
        }

        /* Header */
        .safety-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .safety-header h1 {
            font-size: 36px;
            color: #1e293b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .safety-header h1 i {
            color: #22d3ee;
        }

        .safety-header p {
            color: #4b5563;
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Tip of the Day */
        .tip-of-day {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 20px 40px rgba(245, 158, 11, 0.3);
            position: relative;
            overflow: hidden;
        }

        .tip-of-day::before {
            content: "💡";
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 120px;
            opacity: 0.2;
            transform: rotate(15deg);
        }

        .tip-of-day-label {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            backdrop-filter: blur(5px);
        }

        .tip-of-day-title {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .tip-of-day-content {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.95;
            max-width: 80%;
        }

        /* Quick Stats */
        .safety-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 20px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }

        /* Search and Filter */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .search-wrapper {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 50px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 30px;
            background: white;
            color: #4b5563;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 14px;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Safety Tips Grid */
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .tip-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .tip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .tip-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .tip-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .tip-title {
            flex: 1;
        }

        .tip-title h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .tip-category {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        .tip-body {
            padding: 20px;
        }

        .tip-content {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .tip-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .tip-tag {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        .tip-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .tip-priority {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .priority-high {
            color: #ef4444;
        }

        .priority-medium {
            color: #f59e0b;
        }

        .priority-low {
            color: #10b981;
        }

        .read-more-btn {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .read-more-btn:hover {
            background: #667eea;
            color: white;
        }

        /* Quick Safety Quiz */
        .quiz-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .quiz-title {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quiz-question {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .quiz-question-text {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .quiz-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .quiz-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .quiz-option:hover {
            background: #f1f5f9;
        }

        .quiz-option input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .quiz-option label {
            flex: 1;
            cursor: pointer;
            color: #475569;
        }

        .quiz-feedback {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            display: none;
        }

        .quiz-feedback.correct {
            background: #dcfce7;
            color: #166534;
            display: block;
        }

        .quiz-feedback.incorrect {
            background: #fee2e2;
            color: #991b1b;
            display: block;
        }

        .quiz-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .quiz-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .quiz-score {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-top: 20px;
        }

        /* Downloadable Resources */
        .resources-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .resource-card {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }

        .resource-card:hover {
            transform: translateY(-3px);
        }

        .resource-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
        }

        .resource-card h4 {
            color: #1e293b;
            margin-bottom: 10px;
        }

        .resource-card p {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .download-btn {
            display: inline-block;
            padding: 8px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .download-btn:hover {
            background: #5a67d8;
            transform: scale(1.05);
        }

        /* Recent Incidents */
        .incidents-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .incident-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .incident-item:last-child {
            border-bottom: none;
        }

        .incident-icon {
            width: 40px;
            height: 40px;
            background: #fee2e2;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ef4444;
        }

        .incident-content {
            flex: 1;
        }

        .incident-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .incident-location {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 3px;
        }

        .incident-date {
            font-size: 11px;
            color: #94a3b8;
        }

        .no-incidents {
            text-align: center;
            color: #6b7280;
            padding: 30px;
        }

        /* Safety Checklist */
        .checklist-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .checklist-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .checklist-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checklist-item label {
            flex: 1;
            color: #1e293b;
            font-weight: 500;
            cursor: pointer;
        }

        .checklist-item.completed {
            background: #dcfce7;
            text-decoration: line-through;
            color: #166534;
        }

        .save-checklist {
            margin-top: 20px;
            padding: 10px 25px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .save-checklist:hover {
            background: #059669;
        }

        /* Emergency Contacts Reminder */
        .contacts-reminder {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .reminder-text h3 {
            font-size: 20px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reminder-text p {
            opacity: 0.9;
        }

        .reminder-buttons {
            display: flex;
            gap: 15px;
        }

        .reminder-btn {
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .reminder-btn.emergency {
            background: #ef4444;
            color: white;
        }

        .reminder-btn.emergency:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        .reminder-btn.contacts {
            background: #667eea;
            color: white;
        }

        .reminder-btn.contacts:hover {
            background: #5a67d8;
            transform: scale(1.05);
        }

        /* Back to Emergency */
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: opacity 0.3s;
        }

        .back-link:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        @media (max-width: 768px) {
            .search-wrapper {
                flex-direction: column;
            }
            
            .reminder-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .reminder-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Emergency Banner - Only shows if there's a critical alert -->
        <?php if ($critical_alert): ?>
        <div class="emergency-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div class="emergency-banner-content">
                <div class="emergency-banner-title"><?php echo htmlspecialchars($critical_alert['title']); ?></div>
                <div><?php echo htmlspecialchars($critical_alert['message']); ?></div>
            </div>
            <a href="emergency.php" class="emergency-banner-link">View Details →</a>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="safety-header">
            <h1>
                <i class="fa-solid fa-shield-halved"></i>
                Safety Tips & Resources
                <i class="fa-solid fa-heart-pulse"></i>
            </h1>
            <p>Your safety is our priority. Learn essential safety practices and stay prepared.</p>
        </div>

        <!-- Emergency Contacts Reminder -->
        <div class="contacts-reminder">
            <div class="reminder-text">
                <h3><i class="fa-solid fa-phone-alt"></i> Need Immediate Help?</h3>
                <p>Emergency services are available 24/7. Don't hesitate to call.</p>
            </div>
            <div class="reminder-buttons">
                <a href="tel:0112345678" class="reminder-btn emergency">
                    <i class="fa-solid fa-phone"></i> Call Security
                </a>
                <a href="emergency.php" class="reminder-btn contacts">
                    <i class="fa-solid fa-address-book"></i> All Contacts
                </a>
            </div>
        </div>

        <!-- Tip of the Day -->
        <?php if ($tip_of_day): ?>
        <div class="tip-of-day">
            <span class="tip-of-day-label"><i class="fa-regular fa-lightbulb"></i> TIP OF THE DAY</span>
            <h2 class="tip-of-day-title"><?php echo htmlspecialchars($tip_of_day['title']); ?></h2>
            <div class="tip-of-day-content"><?php echo htmlspecialchars($tip_of_day['content']); ?></div>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="safety-stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
                <div class="stat-value"><?php echo $tips_count; ?></div>
                <div class="stat-label">Safety Tips</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-video"></i></div>
                <div class="stat-value">8</div>
                <div class="stat-label">Training Videos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-download"></i></div>
                <div class="stat-value">12</div>
                <div class="stat-label">Resources</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
                <div class="stat-value">85%</div>
                <div class="stat-label">Quiz Avg Score</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-section">
            <form method="GET" action="" class="search-wrapper">
                <input type="text" name="search" class="search-input" placeholder="Search safety tips..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn"><i class="fa-solid fa-search"></i> Search</button>
            </form>

            <div class="filter-buttons">
                <a href="?category=all" class="filter-btn <?php echo (!$category_filter || $category_filter === 'all') ? 'active' : ''; ?>">All</a>
                <?php 
                if ($categories_result && mysqli_num_rows($categories_result) > 0) {
                    while($category = mysqli_fetch_assoc($categories_result)) {
                        $active = ($category_filter === $category['category']) ? 'active' : '';
                        echo '<a href="?category=' . urlencode($category['category']) . '" class="filter-btn ' . $active . '">' . htmlspecialchars($category['category']) . '</a>';
                    }
                } else {
                ?>
                <a href="?category=Fire%20Safety" class="filter-btn <?php echo $category_filter === 'Fire Safety' ? 'active' : ''; ?>">🔥 Fire Safety</a>
                <a href="?category=Personal%20Safety" class="filter-btn <?php echo $category_filter === 'Personal Safety' ? 'active' : ''; ?>">🛡️ Personal Safety</a>
                <a href="?category=Cyber%20Safety" class="filter-btn <?php echo $category_filter === 'Cyber Safety' ? 'active' : ''; ?>">💻 Cyber Safety</a>
                <a href="?category=Medical%20Emergency" class="filter-btn <?php echo $category_filter === 'Medical Emergency' ? 'active' : ''; ?>">🏥 Medical</a>
                <a href="?category=Travel%20Safety" class="filter-btn <?php echo $category_filter === 'Travel Safety' ? 'active' : ''; ?>">🚌 Travel</a>
                <?php } ?>
            </div>
        </div>

        <!-- Safety Tips Grid -->
        <?php if ($all_tips_result && mysqli_num_rows($all_tips_result) > 0): ?>
        <div class="tips-grid">
            <?php while($tip = mysqli_fetch_assoc($all_tips_result)): 
                $priority_class = '';
                $priority_icon = '';
                if ($tip['priority'] === 'high') {
                    $priority_class = 'priority-high';
                    $priority_icon = 'fa-circle-exclamation';
                } elseif ($tip['priority'] === 'medium') {
                    $priority_class = 'priority-medium';
                    $priority_icon = 'fa-triangle-exclamation';
                } else {
                    $priority_class = 'priority-low';
                    $priority_icon = 'fa-circle-info';
                }
                
                // Determine icon based on category
                $category_icon = 'fa-shield-halved';
                if (strpos($tip['category'], 'Fire') !== false) $category_icon = 'fa-fire';
                else if (strpos($tip['category'], 'Cyber') !== false) $category_icon = 'fa-laptop';
                else if (strpos($tip['category'], 'Medical') !== false) $category_icon = 'fa-heart-pulse';
                else if (strpos($tip['category'], 'Travel') !== false) $category_icon = 'fa-bus';
                else if (strpos($tip['category'], 'Personal') !== false) $category_icon = 'fa-user-shield';
                else if (strpos($tip['category'], 'Lab') !== false) $category_icon = 'fa-flask';
            ?>
            <div class="tip-card">
                <div class="tip-header">
                    <div class="tip-icon"><i class="fa-solid <?php echo $category_icon; ?>"></i></div>
                    <div class="tip-title">
                        <h3><?php echo htmlspecialchars($tip['title']); ?></h3>
                        <span class="tip-category"><?php echo htmlspecialchars($tip['category']); ?></span>
                    </div>
                </div>
                <div class="tip-body">
                    <div class="tip-content">
                        <?php 
                        // Show shortened content
                        $content = htmlspecialchars($tip['content']);
                        echo strlen($content) > 150 ? substr($content, 0, 150) . '...' : $content;
                        ?>
                    </div>
                    
                    <?php if (!empty($tip['tags'])): ?>
                    <div class="tip-tags">
                        <?php 
                        $tags = explode(',', $tip['tags']);
                        foreach($tags as $tag): 
                        ?>
                        <span class="tip-tag">#<?php echo trim(htmlspecialchars($tag)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="tip-footer">
                        <div class="tip-priority <?php echo $priority_class; ?>">
                            <i class="fa-solid <?php echo $priority_icon; ?>"></i>
                            <?php echo ucfirst($tip['priority']); ?> Priority
                        </div>
                        <button class="read-more-btn" onclick="showFullTip(<?php echo $tip['id']; ?>)">Read More</button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div style="background: white; border-radius: 20px; padding: 50px; text-align: center; color: #6b7280;">
            <i class="fa-solid fa-face-frown" style="font-size: 48px; margin-bottom: 20px; color: #94a3b8;"></i>
            <h3 style="margin-bottom: 10px;">No safety tips found</h3>
            <p>Try adjusting your search or filter criteria.</p>
        </div>
        <?php endif; ?>

        <!-- Quick Safety Quiz -->
        <div class="quiz-section">
            <h2 class="quiz-title"><i class="fa-solid fa-question-circle"></i> Test Your Safety Knowledge</h2>
            
            <?php if ($quiz_result && mysqli_num_rows($quiz_result) > 0): ?>
            <form id="safetyQuiz">
                <?php 
                $question_num = 1;
                while($question = mysqli_fetch_assoc($quiz_result)): 
                ?>
                <div class="quiz-question" id="q_<?php echo $question['id']; ?>">
                    <div class="quiz-question-text"><?php echo $question_num . '. ' . htmlspecialchars($question['question']); ?></div>
                    <div class="quiz-options">
                        <?php 
                        $options = json_decode($question['options'], true);
                        foreach($options as $index => $option): 
                        ?>
                        <div class="quiz-option">
                            <input type="radio" name="q_<?php echo $question['id']; ?>" value="<?php echo $index; ?>" id="q<?php echo $question['id']; ?>_<?php echo $index; ?>">
                            <label for="q<?php echo $question['id']; ?>_<?php echo $index; ?>"><?php echo htmlspecialchars($option); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="quiz-feedback" id="feedback_<?php echo $question['id']; ?>"></div>
                </div>
                <?php 
                $question_num++;
                endwhile; 
                ?>
                
                <button type="button" class="quiz-submit" onclick="checkQuizAnswers()">Check Answers</button>
            </form>
            <div class="quiz-score" id="quizScore"></div>
            <?php else: ?>
            <p style="text-align: center; color: #6b7280; padding: 30px;">
                <i class="fa-solid fa-circle-info" style="font-size: 24px; margin-bottom: 10px; display: block; color: #667eea;"></i>
                Quiz questions are being prepared. Check back soon!
            </p>
            <?php endif; ?>
        </div>

        <!-- Safety Checklist -->
        <div class="checklist-section">
            <h2 class="quiz-title"><i class="fa-solid fa-check-double"></i> Emergency Preparedness Checklist</h2>
            <p style="color: #6b7280; margin-bottom: 20px;">Track your preparedness level</p>
            
            <div class="checklist-items">
                <div class="checklist-item">
                    <input type="checkbox" id="check1" class="checklist-checkbox">
                    <label for="check1">Know emergency exit routes in all buildings</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="check2" class="checklist-checkbox">
                    <label for="check2">Save emergency contacts in phone</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="check3" class="checklist-checkbox">
                    <label for="check3">Prepare a personal emergency kit</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="check4" class="checklist-checkbox">
                    <label for="check4">Learn CPR and basic first aid</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="check5" class="checklist-checkbox">
                    <label for="check5">Share location with trusted contacts</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="check6" class="checklist-checkbox">
                    <label for="check6">Know campus security numbers</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="check7" class="checklist-checkbox">
                    <label for="check7">Have a communication plan with family</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="check8" class="checklist-checkbox">
                    <label for="check8">Identify safe zones in each building</label>
                </div>
            </div>
            
            <button class="save-checklist" onclick="saveChecklist()">
                <i class="fa-regular fa-floppy-disk"></i> Save My Progress
            </button>
        </div>

        <!-- Downloadable Resources -->
        <div class="resources-section">
            <h2 class="quiz-title"><i class="fa-solid fa-download"></i> Downloadable Safety Resources</h2>
            
            <div class="resources-grid">
                <div class="resource-card">
                    <div class="resource-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <h4>Emergency Procedures Guide</h4>
                    <p>Complete guide for all emergency scenarios on campus</p>
                    <a href="#" class="download-btn" onclick="downloadResource('emergency_guide.pdf')">
                        <i class="fa-solid fa-download"></i> Download PDF
                    </a>
                </div>
                
                <div class="resource-card">
                    <div class="resource-icon"><i class="fa-solid fa-map"></i></div>
                    <h4>Campus Safety Map</h4>
                    <p>Evacuation routes and emergency assembly points</p>
                    <a href="#" class="download-btn" onclick="downloadResource('safety_map.pdf')">
                        <i class="fa-solid fa-download"></i> Download PDF
                    </a>
                </div>
                
                <div class="resource-card">
                    <div class="resource-icon"><i class="fa-solid fa-list-check"></i></div>
                    <h4>Emergency Contact Card</h4>
                    <p>Printable card with all emergency numbers</p>
                    <a href="#" class="download-btn" onclick="downloadResource('contact_card.pdf')">
                        <i class="fa-solid fa-download"></i> Download PDF
                    </a>
                </div>
                
                <div class="resource-card">
                    <div class="resource-icon"><i class="fa-solid fa-video"></i></div>
                    <h4>Safety Training Videos</h4>
                    <p>Watch short safety training videos</p>
                    <a href="#" class="download-btn" onclick="openTrainingVideos()">
                        <i class="fa-solid fa-play"></i> Watch Now
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Safety Incidents -->
        <?php if ($incidents_result && mysqli_num_rows($incidents_result) > 0): ?>
        <div class="incidents-section">
            <h2 class="quiz-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Safety Updates</h2>
            
            <?php while($incident = mysqli_fetch_assoc($incidents_result)): ?>
            <div class="incident-item">
                <div class="incident-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                <div class="incident-content">
                    <div class="incident-title"><?php echo htmlspecialchars($incident['title']); ?></div>
                    <div class="incident-location">
                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($incident['location']); ?>
                    </div>
                    <div class="incident-date">
                        <i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($incident['report_date'])); ?>
                        <span style="margin-left: 10px; padding: 2px 8px; border-radius: 30px; font-size: 10px; background: 
                            <?php 
                            if($incident['status'] == 'active') echo '#ef4444';
                            elseif($incident['status'] == 'investigating') echo '#f59e0b';
                            else echo '#10b981';
                            ?>; color: white;">
                            <?php echo ucfirst($incident['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- Back to Emergency Dashboard -->
        <div style="text-align: center;">
            <a href="emergency.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Emergency Dashboard
            </a>
            <span style="color: rgba(255,255,255,0.5); margin: 0 15px;">|</span>
            <a href="index.php" class="back-link">
                <i class="fa-solid fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Tip Detail Modal -->
    <div id="tipModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; padding: 30px; max-width: 600px; max-height: 80vh; overflow-y: auto; position: relative;">
            <button onclick="closeModal()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        // Show full tip in modal
        function showFullTip(tipId) {
            const modal = document.getElementById('tipModal');
            const modalContent = document.getElementById('modalContent');
            
            // Show loading
            modalContent.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 30px; color: #667eea;"></i><p>Loading...</p></div>';
            modal.style.display = 'flex';
            
            // Fetch tip details
            fetch('get_safety_tip.php?id=' + tipId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let priorityColor = '#10b981';
                        let priorityIcon = 'fa-circle-info';
                        if (data.tip.priority === 'high') {
                            priorityColor = '#ef4444';
                            priorityIcon = 'fa-circle-exclamation';
                        } else if (data.tip.priority === 'medium') {
                            priorityColor = '#f59e0b';
                            priorityIcon = 'fa-triangle-exclamation';
                        }
                        
                        modalContent.innerHTML = `
                            <h2 style="margin-bottom: 15px; color: #1e293b;">${data.tip.title}</h2>
                            <div style="margin-bottom: 20px;">
                                <span style="background: #667eea; color: white; padding: 5px 15px; border-radius: 30px; font-size: 12px; margin-right: 10px;">${data.tip.category}</span>
                                <span style="color: ${priorityColor};">
                                    <i class="fa-solid ${priorityIcon}"></i>
                                    ${data.tip.priority.toUpperCase()} Priority
                                </span>
                            </div>
                            <div style="line-height: 1.8; color: #4b5563; margin-bottom: 20px;">${data.tip.content}</div>
                            ${data.tip.tags ? `<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;"><strong style="color: #1e293b;">Tags:</strong> ${data.tip.tags.split(',').map(tag => '<span style="display: inline-block; background: #f3f4f6; padding: 3px 10px; border-radius: 30px; margin: 0 5px 5px 0; font-size: 12px;">#' + tag.trim() + '</span>').join('')}</div>` : ''}
                            <div style="margin-top: 20px; color: #94a3b8; font-size: 12px;"><i class="fa-regular fa-eye"></i> ${data.tip.views} views</div>
                        `;
                    } else {
                        modalContent.innerHTML = '<div style="text-align: center; padding: 20px; color: #ef4444;"><i class="fa-solid fa-exclamation-circle" style="font-size: 30px; margin-bottom: 10px;"></i><p>Error loading tip details</p></div>';
                    }
                })
                .catch(error => {
                    modalContent.innerHTML = '<div style="text-align: center; padding: 20px; color: #ef4444;"><i class="fa-solid fa-exclamation-circle" style="font-size: 30px; margin-bottom: 10px;"></i><p>Error loading tip details</p></div>';
                });
        }
        
        function closeModal() {
            document.getElementById('tipModal').style.display = 'none';
        }
        
        // Quiz functionality
        function checkQuizAnswers() {
            let score = 0;
            let total = 0;
            const questions = document.querySelectorAll('.quiz-question');
            
            questions.forEach(question => {
                total++;
                const qId = question.id.replace('q_', '');
                const selected = document.querySelector(`input[name="q_${qId}"]:checked`);
                const feedback = document.getElementById(`feedback_${qId}`);
                
                if (selected) {
                    // For demo, we'll use a simple pattern
                    // In a real app, you'd compare with correct_answer from database
                    // For now, we'll use a simple check based on question text
                    const questionText = question.querySelector('.quiz-question-text').textContent;
                    const selectedValue = selected.value;
                    
                    let isCorrect = false;
                    
                    // Simple logic for demo - in production, fetch correct answers via AJAX
                    if (questionText.includes('fire') && selectedValue === '2') isCorrect = true;
                    else if (questionText.includes('security number') && selectedValue === '1') isCorrect = true;
                    else if (questionText.includes('password') && selectedValue === '2') isCorrect = true;
                    else if (questionText.includes('medical emergency') && selectedValue === '1') isCorrect = true;
                    else if (questionText.includes('assemble') && selectedValue === '1') isCorrect = true;
                    else if (questionText.includes('suspicious email') && selectedValue === '2') isCorrect = true;
                    else if (questionText.includes('safe walk') && selectedValue === '3') isCorrect = true;
                    else if (questionText.includes('emergency kit') && selectedValue === '3') isCorrect = true;
                    
                    if (isCorrect) {
                        score++;
                        feedback.className = 'quiz-feedback correct';
                        feedback.innerHTML = '<i class="fa-solid fa-check-circle"></i> Correct! Good job.';
                    } else {
                        feedback.className = 'quiz-feedback incorrect';
                        feedback.innerHTML = '<i class="fa-solid fa-times-circle"></i> Not quite. Review the safety tips above.';
                    }
                } else {
                    feedback.className = 'quiz-feedback incorrect';
                    feedback.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> Please select an answer.';
                }
            });
            
            document.getElementById('quizScore').innerHTML = `You scored ${score} out of ${total}! ${score === total ? '🎉 Perfect! You\'re safety conscious!' : 'Keep learning and stay safe!'}`;
        }
        
        // Save checklist progress
        function saveChecklist() {
            const checkboxes = document.querySelectorAll('.checklist-checkbox');
            let completed = 0;
            
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    completed++;
                    cb.closest('.checklist-item').classList.add('completed');
                } else {
                    cb.closest('.checklist-item').classList.remove('completed');
                }
            });
            
            // Save to localStorage
            localStorage.setItem('safetyChecklist', JSON.stringify({
                date: new Date().toISOString(),
                completed: completed,
                total: checkboxes.length
            }));
            
            alert(`✅ Checklist saved! You've completed ${completed} out of ${checkboxes.length} items. Keep up the good work!`);
        }
        
        // Load checklist from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const saved = localStorage.getItem('safetyChecklist');
            if (saved) {
                const data = JSON.parse(saved);
                console.log('Previous checklist progress:', data);
                // You could show a reminder message
            }
            
            // Close modal when clicking outside
            const modal = document.getElementById('tipModal');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        });
        
        // Download resource (simulated)
        function downloadResource(filename) {
            alert(`📥 Downloading ${filename}...\n\nIn a real application, this would download the actual PDF file with safety information.`);
            // In production, you'd redirect to actual file:
            // window.location.href = 'downloads/' + filename;
        }
        
        // Open training videos
        function openTrainingVideos() {
            alert('Opening safety training videos playlist...');
            window.open('https://www.youtube.com/results?search_query=campus+safety+training', '_blank');
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>