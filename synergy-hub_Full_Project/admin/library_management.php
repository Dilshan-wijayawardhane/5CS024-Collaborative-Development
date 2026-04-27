<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$message = '';
$error = '';

// Handle tab parameter from URL
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'books';

// Handle book operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_book']) || isset($_POST['edit_book'])) {
        $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $author = mysqli_real_escape_string($conn, $_POST['author']);
        $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $quantity = intval($_POST['quantity']);
        $available = intval($_POST['available']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        
        if ($book_id > 0) {
            $sql = "UPDATE books SET title=?, author=?, isbn=?, category=?, quantity=?, available=?, description=? WHERE book_id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssiisi", $title, $author, $isbn, $category, $quantity, $available, $description, $book_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Book updated successfully!";
                logAdminActivity($conn, 'UPDATE_BOOK', "Updated book: $title");
            } else {
                $error = "Error updating book: " . mysqli_error($conn);
            }
        } else {
            $sql = "INSERT INTO books (title, author, isbn, category, quantity, available, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssiis", $title, $author, $isbn, $category, $quantity, $available, $description);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Book added successfully!";
                logAdminActivity($conn, 'ADD_BOOK', "Added book: $title");
            } else {
                $error = "Error adding book: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['delete_book'])) {
        $book_id = intval($_POST['book_id']);
        
        // Check if book is borrowed
        $check_sql = "SELECT COUNT(*) as count FROM borrowed_books WHERE book_id = ? AND status = 'borrowed'";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $book_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $check = mysqli_fetch_assoc($check_result);
        
        if ($check['count'] > 0) {
            $error = "Cannot delete book - it is currently borrowed";
        } else {
            $delete_sql = "DELETE FROM books WHERE book_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $book_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $message = "Book deleted successfully!";
                logAdminActivity($conn, 'DELETE_BOOK', "Deleted book ID: $book_id");
            } else {
                $error = "Error deleting book: " . mysqli_error($conn);
            }
        }
    }
    
    // Update study room rules
    if (isset($_POST['update_rules'])) {
        $max_duration = intval($_POST['max_duration']);
        $booking_window = intval($_POST['booking_window']);
        $cancellation_hours = intval($_POST['cancellation_hours']);
        $allow_recurring = isset($_POST['allow_recurring']) ? 1 : 0;
        
        // Store in session or create settings table
        $_SESSION['library_rules'] = [
            'max_duration' => $max_duration,
            'booking_window' => $booking_window,
            'cancellation_hours' => $cancellation_hours,
            'allow_recurring' => $allow_recurring
        ];
        
        $message = "Booking rules updated successfully!";
        logAdminActivity($conn, 'UPDATE_RULES', "Updated library booking rules");
    }
    
    // Add study room
    if (isset($_POST['add_room'])) {
        $room_name = mysqli_real_escape_string($conn, $_POST['room_name']);
        $capacity = intval($_POST['capacity']);
        $has_projector = isset($_POST['has_projector']) ? 1 : 0;
        $has_whiteboard = isset($_POST['has_whiteboard']) ? 1 : 0;
        
        $sql = "INSERT INTO study_rooms (room_name, capacity, has_projector, has_whiteboard) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "siii", $room_name, $capacity, $has_projector, $has_whiteboard);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Study room added successfully!";
            logAdminActivity($conn, 'ADD_ROOM', "Added study room: $room_name");
        } else {
            $error = "Error adding study room: " . mysqli_error($conn);
        }
    }
    
    // Create membership form field
    if (isset($_POST['add_form_field'])) {
        $field_label = mysqli_real_escape_string($conn, $_POST['field_label']);
        $field_type = mysqli_real_escape_string($conn, $_POST['field_type']);
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $field_options = mysqli_real_escape_string($conn, $_POST['field_options']);
        $display_order = intval($_POST['display_order']);
        
        // Store in custom table or session
        if (!isset($_SESSION['form_fields'])) {
            $_SESSION['form_fields'] = [];
        }
        $_SESSION['form_fields'][] = [
            'label' => $field_label,
            'type' => $field_type,
            'required' => $is_required,
            'options' => $field_options,
            'order' => $display_order
        ];
        
        $message = "Form field added successfully!";
    }
}

// Get all books
$books_sql = "SELECT * FROM books ORDER BY category, title";
$books_result = mysqli_query($conn, $books_sql);

// Get borrowed books
$borrowed_sql = "SELECT bb.*, b.title, b.author, u.Name as user_name, u.StudentID 
                 FROM borrowed_books bb
                 JOIN books b ON bb.book_id = b.book_id
                 JOIN Users u ON bb.user_id = u.UserID
                 WHERE bb.status = 'borrowed'
                 ORDER BY bb.due_date ASC";
$borrowed_result = mysqli_query($conn, $borrowed_sql);

// Get study rooms
$rooms_sql = "SELECT * FROM study_rooms ORDER BY room_name";
$rooms_result = mysqli_query($conn, $rooms_sql);

// Get room bookings
$room_bookings_sql = "SELECT rb.*, u.Name as user_name, sr.room_name 
                      FROM room_bookings rb
                      JOIN Users u ON rb.user_id = u.UserID
                      JOIN study_rooms sr ON rb.room_id = sr.room_id
                      WHERE rb.booking_date >= CURDATE()
                      ORDER BY rb.booking_date, rb.time_slot";
$room_bookings_result = mysqli_query($conn, $room_bookings_sql);

// Get library rules from session
$library_rules = $_SESSION['library_rules'] ?? [
    'max_duration' => 2,
    'booking_window' => 7,
    'cancellation_hours' => 2,
    'allow_recurring' => true
];

// Categories for books
$categories = ['Fiction', 'Non-Fiction', 'Academic', 'Reference', 'Science', 'Technology', 'Arts', 'History', 'Biography'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .library-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .library-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .library-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .library-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 14px;
        }
        
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .book-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        
        .book-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .book-author {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .book-meta {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            font-size: 13px;
            color: #475569;
        }
        
        .book-meta i {
            color: #667eea;
            width: 20px;
        }
        
        .availability-bar {
            margin: 10px 0;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .availability-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 3px;
        }
        
        .book-actions {
            display: flex;
            gap: 5px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .borrowed-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .borrowed-info {
            flex: 1;
            min-width: 250px;
        }
        
        .borrowed-user {
            font-weight: 600;
            color: #1e293b;
        }
        
        .borrowed-book {
            color: #667eea;
        }
        
        .due-date {
            font-size: 12px;
            color: #ef4444;
        }
        
        .room-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 15px;
        }
        
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .room-name {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .room-capacity {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .room-features {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        
        .feature-tag {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #475569;
        }
        
        .feature-tag i {
            color: #667eea;
        }
        
        .booking-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
        }
        
        .booking-datetime {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .booking-user {
            font-weight: 600;
            color: #1e293b;
        }
        
        .rules-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .rule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .rule-item:last-child {
            border-bottom: none;
        }
        
        .rule-label {
            font-weight: 500;
            color: #1e293b;
        }
        
        .rule-value {
            color: #667eea;
            font-weight: 600;
        }
        
        .form-builder {
            background: white;
            border-radius: 12px;
            padding: 20px;
        }
        
        .field-preview {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .field-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: move;
            flex-wrap: wrap;
        }
        
        .field-drag {
            color: #94a3b8;
            cursor: grab;
        }
        
        .field-label {
            flex: 1;
            font-weight: 500;
            min-width: 150px;
        }
        
        .field-required {
            color: #ef4444;
            font-size: 12px;
        }
        
        .field-actions {
            display: flex;
            gap: 5px;
        }
        
        .add-field-form {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .alert-danger {
            background: #fee;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
        }
        
        .btn-icon:hover {
            background: #f1f5f9;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-success {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-warning {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #64748b;
        }
        
        .checkbox-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .borrowed-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .rule-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Content -->
            <div class="content">
                <h1 class="page-title">
                    <i class="fa-solid fa-book"></i> Library Management
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <?php
                $total_books = mysqli_num_rows($books_result);
                $total_borrowed = mysqli_num_rows($borrowed_result);
                $total_rooms = mysqli_num_rows($rooms_result);
                $overdue = 0;
                mysqli_data_seek($borrowed_result, 0);
                while($borrowed = mysqli_fetch_assoc($borrowed_result)) {
                    if(strtotime($borrowed['due_date']) < time()) $overdue++;
                }
                ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_books; ?></div>
                        <div class="stat-label">Total Books</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_borrowed; ?></div>
                        <div class="stat-label">Currently Borrowed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $overdue; ?></div>
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_rooms; ?></div>
                        <div class="stat-label">Study Rooms</div>
                    </div>
                </div>
                
                <!-- Tabs with URL parameters -->
                <div class="library-tabs">
                    <a href="library_management.php?tab=books" class="library-tab <?php echo $active_tab == 'books' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-book"></i> Books
                    </a>
                    <a href="library_management.php?tab=borrowed" class="library-tab <?php echo $active_tab == 'borrowed' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-hand-holding"></i> Borrowed
                    </a>
                    <a href="library_management.php?tab=rooms" class="library-tab <?php echo $active_tab == 'rooms' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-door-open"></i> Study Rooms
                    </a>
                    <a href="library_management.php?tab=rules" class="library-tab <?php echo $active_tab == 'rules' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-ruler"></i> Booking Rules
                    </a>
                    <a href="library_management.php?tab=membership" class="library-tab <?php echo $active_tab == 'membership' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-id-card"></i> Membership Form
                    </a>
                </div>
                
                <!-- Tab: Books -->
                <div id="tab-books" class="tab-content <?php echo $active_tab == 'books' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="showAddBook()">
                            <i class="fa-solid fa-plus"></i> Add New Book
                        </button>
                    </div>
                    
                    <div class="books-grid">
                        <?php if(mysqli_num_rows($books_result) > 0): ?>
                            <?php while($book = mysqli_fetch_assoc($books_result)): 
                                $available_percent = ($book['available'] / $book['quantity']) * 100;
                            ?>
                            <div class="book-card">
                                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="book-author">by <?php echo htmlspecialchars($book['author'] ?: 'Unknown'); ?></div>
                                
                                <div class="book-meta">
                                    <div><i class="fa-solid fa-hashtag"></i> <?php echo $book['isbn'] ?: 'N/A'; ?></div>
                                    <div><i class="fa-solid fa-tag"></i> <?php echo $book['category']; ?></div>
                                </div>
                                
                                <div class="availability-bar">
                                    <div class="availability-fill" style="width: <?php echo $available_percent; ?>%;"></div>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                    <span>Available: <strong><?php echo $book['available']; ?>/<?php echo $book['quantity']; ?></strong></span>
                                    <span><?php echo round($available_percent); ?>%</span>
                                </div>
                                
                                <?php if($book['description']): ?>
                                <p style="font-size: 12px; color: #64748b; margin-top: 10px;">
                                    <?php echo substr(htmlspecialchars($book['description']), 0, 100); ?>...
                                </p>
                                <?php endif; ?>
                                
                                <div class="book-actions">
                                    <button class="btn btn-primary btn-sm" onclick="editBook(<?php echo $book['book_id']; ?>)">
                                        <i class="fa-regular fa-pen-to-square"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this book?')">
                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                        <button type="submit" name="delete_book" class="btn btn-danger btn-sm">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #64748b;">
                                <i class="fa-solid fa-book" style="font-size: 48px; margin-bottom: 20px;"></i>
                                <h3>No books found</h3>
                                <p>Click "Add New Book" to add your first book.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Add/Edit Book Modal -->
                    <div class="modal" id="bookModal">
                        <div class="modal-content" style="max-width: 600px;">
                            <div class="modal-header">
                                <h3 id="bookModalTitle">Add New Book</h3>
                                <button class="modal-close" onclick="closeBookModal()">&times;</button>
                            </div>
                            <form method="POST" id="bookForm">
                                <div class="modal-body">
                                    <input type="hidden" name="book_id" id="book_id">
                                    
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="title" id="book_title" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Author</label>
                                            <input type="text" name="author" id="book_author">
                                        </div>
                                        <div class="form-group">
                                            <label>ISBN</label>
                                            <input type="text" name="isbn" id="book_isbn">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Category</label>
                                            <select name="category" id="book_category">
                                                <?php foreach($categories as $cat): ?>
                                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Quantity</label>
                                            <input type="number" name="quantity" id="book_quantity" min="1" value="1" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Available Copies</label>
                                        <input type="number" name="available" id="book_available" min="0" value="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" id="book_description" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="closeBookModal()">Cancel</button>
                                    <button type="submit" name="add_book" class="btn btn-primary">Save Book</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Borrowed Books -->
                <div id="tab-borrowed" class="tab-content <?php echo $active_tab == 'borrowed' ? 'active' : ''; ?>">
                    <h3>Currently Borrowed Books</h3>
                    
                    <?php if(mysqli_num_rows($borrowed_result) > 0): ?>
                        <?php while($borrowed = mysqli_fetch_assoc($borrowed_result)): 
                            $is_overdue = strtotime($borrowed['due_date']) < time();
                        ?>
                        <div class="borrowed-item" style="border-left: 4px solid <?php echo $is_overdue ? '#ef4444' : '#10b981'; ?>;">
                            <div class="borrowed-info">
                                <div class="borrowed-user"><?php echo htmlspecialchars($borrowed['user_name']); ?> (<?php echo $borrowed['StudentID']; ?>)</div>
                                <div class="borrowed-book"><?php echo htmlspecialchars($borrowed['title']); ?> by <?php echo htmlspecialchars($borrowed['author']); ?></div>
                                <div class="due-date">
                                    <i class="fa-regular fa-calendar"></i> 
                                    Due: <?php echo date('M d, Y', strtotime($borrowed['due_date'])); ?>
                                    <?php if($is_overdue): ?>
                                        (<?php echo floor((time() - strtotime($borrowed['due_date'])) / (60*60*24)); ?> days overdue)
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-success btn-sm" onclick="markReturned(<?php echo $borrowed['borrow_id']; ?>, <?php echo $borrowed['book_id']; ?>)">
                                    <i class="fa-solid fa-rotate-left"></i> Return
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fa-solid fa-book-open" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>No books currently borrowed</h3>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Study Rooms -->
                <div id="tab-rooms" class="tab-content <?php echo $active_tab == 'rooms' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="showAddRoom()">
                            <i class="fa-solid fa-plus"></i> Add Study Room
                        </button>
                    </div>
                    
                    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_rooms; ?></div>
                            <div class="stat-label">Total Rooms</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <?php 
                                $available_rooms = 0;
                                mysqli_data_seek($rooms_result, 0);
                                while($room = mysqli_fetch_assoc($rooms_result)) {
                                    if($room['is_available']) $available_rooms++;
                                }
                                echo $available_rooms;
                                ?>
                            </div>
                            <div class="stat-label">Available Now</div>
                        </div>
                    </div>
                    
                    <h3>Study Rooms</h3>
                    <div class="books-grid">
                        <?php 
                        if(mysqli_num_rows($rooms_result) > 0):
                            mysqli_data_seek($rooms_result, 0);
                            while($room = mysqli_fetch_assoc($rooms_result)): 
                        ?>
                        <div class="room-card">
                            <div class="room-header">
                                <span class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></span>
                                <span class="room-capacity">
                                    <i class="fa-solid fa-users"></i> <?php echo $room['capacity']; ?>
                                </span>
                            </div>
                            
                            <div class="room-features">
                                <?php if($room['has_projector']): ?>
                                <span class="feature-tag"><i class="fa-solid fa-video"></i> Projector</span>
                                <?php endif; ?>
                                <?php if($room['has_whiteboard']): ?>
                                <span class="feature-tag"><i class="fa-solid fa-chalkboard"></i> Whiteboard</span>
                                <?php endif; ?>
                                <span class="feature-tag"><i class="fa-solid fa-wifi"></i> WiFi</span>
                            </div>
                            
                            <div style="margin: 10px 0;">
                                <span class="status-badge status-<?php echo $room['is_available'] ? 'success' : 'warning'; ?>">
                                    <?php echo $room['is_available'] ? 'Available' : 'Booked'; ?>
                                </span>
                            </div>
                            
                            <div class="book-actions">
                                <button class="btn btn-primary btn-sm" onclick="editRoom(<?php echo $room['room_id']; ?>)">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="viewRoomBookings(<?php echo $room['room_id']; ?>)">
                                    <i class="fa-regular fa-calendar"></i> Bookings
                                </button>
                            </div>
                        </div>
                        <?php endwhile; 
                        else: ?>
                            <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #64748b;">
                                <i class="fa-solid fa-door-open" style="font-size: 48px; margin-bottom: 20px;"></i>
                                <h3>No study rooms found</h3>
                                <p>Click "Add Study Room" to add your first room.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 style="margin-top: 30px;">Upcoming Bookings</h3>
                    <?php if(mysqli_num_rows($room_bookings_result) > 0): ?>
                        <?php while($booking = mysqli_fetch_assoc($room_bookings_result)): ?>
                        <div class="booking-item">
                            <div class="booking-datetime">
                                <span class="booking-user"><?php echo htmlspecialchars($booking['user_name']); ?></span>
                                <span><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> • <?php echo $booking['time_slot']; ?></span>
                            </div>
                            <div style="font-size: 12px; color: #667eea;">
                                <i class="fa-solid fa-door-open"></i> <?php echo $booking['room_name']; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #64748b; text-align: center; padding: 20px;">No upcoming bookings.</p>
                    <?php endif; ?>
                    
                    <!-- Add Room Modal -->
                    <div class="modal" id="roomModal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3>Add Study Room</h3>
                                <button class="modal-close" onclick="closeRoomModal()">&times;</button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Room Name</label>
                                        <input type="text" name="room_name" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Capacity</label>
                                        <input type="number" name="capacity" min="1" value="4" required>
                                    </div>
                                    
                                    <div class="checkbox-group">
                                        <div class="checkbox-item">
                                            <input type="checkbox" name="has_projector" id="has_projector">
                                            <label for="has_projector">Has Projector</label>
                                        </div>
                                        <div class="checkbox-item">
                                            <input type="checkbox" name="has_whiteboard" id="has_whiteboard" checked>
                                            <label for="has_whiteboard">Has Whiteboard</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="closeRoomModal()">Cancel</button>
                                    <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Booking Rules -->
                <div id="tab-rules" class="tab-content <?php echo $active_tab == 'rules' ? 'active' : ''; ?>">
                    <div class="rules-panel">
                        <h3>Study Room Booking Rules</h3>
                        
                        <form method="POST">
                            <div class="rule-item">
                                <span class="rule-label">Maximum Booking Duration</span>
                                <span class="rule-value">
                                    <input type="number" name="max_duration" value="<?php echo $library_rules['max_duration']; ?>" min="1" max="4" style="width: 60px;"> hours
                                </span>
                            </div>
                            
                            <div class="rule-item">
                                <span class="rule-label">Booking Window (days in advance)</span>
                                <span class="rule-value">
                                    <input type="number" name="booking_window" value="<?php echo $library_rules['booking_window']; ?>" min="1" max="30"> days
                                </span>
                            </div>
                            
                            <div class="rule-item">
                                <span class="rule-label">Cancellation Deadline (hours before)</span>
                                <span class="rule-value">
                                    <input type="number" name="cancellation_hours" value="<?php echo $library_rules['cancellation_hours']; ?>" min="0" max="24"> hours
                                </span>
                            </div>
                            
                            <div class="rule-item">
                                <span class="rule-label">Allow Recurring Bookings</span>
                                <span class="rule-value">
                                    <input type="checkbox" name="allow_recurring" <?php echo $library_rules['allow_recurring'] ? 'checked' : ''; ?>>
                                </span>
                            </div>
                            
                            <button type="submit" name="update_rules" class="btn btn-primary">Save Rules</button>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Membership Form Builder -->
                <div id="tab-membership" class="tab-content <?php echo $active_tab == 'membership' ? 'active' : ''; ?>">
                    <div class="form-builder">
                        <h3>Library Membership Form Builder</h3>
                        <p>Drag and drop to reorder form fields</p>
                        
                        <div id="formFields" class="sortable-fields">
                            <?php 
                            $form_fields = $_SESSION['form_fields'] ?? [
                                ['label' => 'Full Name', 'type' => 'text', 'required' => true],
                                ['label' => 'Student ID', 'type' => 'text', 'required' => true],
                                ['label' => 'Email', 'type' => 'email', 'required' => true],
                                ['label' => 'Phone', 'type' => 'tel', 'required' => true],
                                ['label' => 'Date of Birth', 'type' => 'date', 'required' => false],
                                ['label' => 'Course', 'type' => 'text', 'required' => false],
                                ['label' => 'Year of Study', 'type' => 'select', 'required' => false, 'options' => '1,2,3,4,5']
                            ];
                            
                            foreach($form_fields as $index => $field): 
                            ?>
                            <div class="field-item" data-index="<?php echo $index; ?>">
                                <div class="field-drag"><i class="fa-solid fa-grip-vertical"></i></div>
                                <div class="field-label"><?php echo $field['label']; ?></div>
                                <div class="field-type">(<?php echo $field['type']; ?>)</div>
                                <?php if($field['required']): ?>
                                <div class="field-required">Required</div>
                                <?php endif; ?>
                                <div class="field-actions">
                                    <button class="btn-icon" onclick="editField(<?php echo $index; ?>)"><i class="fa-regular fa-pen-to-square"></i></button>
                                    <button class="btn-icon" onclick="deleteField(<?php echo $index; ?>)"><i class="fa-regular fa-trash-can"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="add-field-form">
                            <h4>Add New Field</h4>
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Field Label</label>
                                        <input type="text" name="field_label" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Field Type</label>
                                        <select name="field_type" id="field_type" onchange="toggleFieldOptions()">
                                            <option value="text">Text</option>
                                            <option value="email">Email</option>
                                            <option value="tel">Phone</option>
                                            <option value="number">Number</option>
                                            <option value="date">Date</option>
                                            <option value="select">Dropdown</option>
                                            <option value="checkbox">Checkbox</option>
                                            <option value="radio">Radio</option>
                                            <option value="textarea">Text Area</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div id="options_field" style="display: none;">
                                    <div class="form-group">
                                        <label>Options (comma separated)</label>
                                        <input type="text" name="field_options" placeholder="Option 1, Option 2, Option 3">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Display Order</label>
                                        <input type="number" name="display_order" value="<?php echo count($form_fields) + 1; ?>" min="1">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div class="checkbox-item">
                                            <input type="checkbox" name="is_required" id="is_required" checked>
                                            <label for="is_required">Required Field</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_form_field" class="btn btn-primary">Add Field</button>
                            </form>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <h4>Form Preview</h4>
                            <div class="field-preview">
                                <?php foreach($form_fields as $field): ?>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                                        <?php echo $field['label']; ?>
                                        <?php if($field['required']): ?><span style="color: #ef4444;">*</span><?php endif; ?>
                                    </label>
                                    
                                    <?php if($field['type'] == 'textarea'): ?>
                                        <textarea style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px;" rows="3" disabled></textarea>
                                    <?php elseif($field['type'] == 'select'): ?>
                                        <select style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px;" disabled>
                                            <option>Select option</option>
                                            <?php if(isset($field['options'])): 
                                                $options = explode(',', $field['options']);
                                                foreach($options as $opt): ?>
                                                <option><?php echo trim($opt); ?></option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                    <?php elseif($field['type'] == 'checkbox' || $field['type'] == 'radio'): ?>
                                        <div style="display: flex; gap: 15px;">
                                            <label><input type="<?php echo $field['type']; ?>" name="sample" disabled> Option 1</label>
                                            <label><input type="<?php echo $field['type']; ?>" name="sample" disabled> Option 2</label>
                                        </div>
                                    <?php else: ?>
                                        <input type="<?php echo $field['type']; ?>" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px;" disabled>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        // Initialize Sortable for form fields
        new Sortable(document.getElementById('formFields'), {
            animation: 150,
            handle: '.field-drag',
            onEnd: function() {
                saveFieldOrder();
            }
        });
        
        // Book modal functions
        function showAddBook() {
            document.getElementById('bookModalTitle').textContent = 'Add New Book';
            document.getElementById('bookForm').reset();
            document.getElementById('book_id').value = '0';
            document.getElementById('book_quantity').value = '1';
            document.getElementById('book_available').value = '1';
            document.getElementById('bookModal').classList.add('show');
        }
        
        function closeBookModal() {
            document.getElementById('bookModal').classList.remove('show');
        }
        
        function editBook(id) {
            fetch('get_book.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('bookModalTitle').textContent = 'Edit Book';
                    document.getElementById('book_id').value = data.book_id;
                    document.getElementById('book_title').value = data.title;
                    document.getElementById('book_author').value = data.author || '';
                    document.getElementById('book_isbn').value = data.isbn || '';
                    document.getElementById('book_category').value = data.category || '';
                    document.getElementById('book_quantity').value = data.quantity;
                    document.getElementById('book_available').value = data.available;
                    document.getElementById('book_description').value = data.description || '';
                    document.getElementById('bookModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading book data');
                });
        }
        
        // Room modal functions
        function showAddRoom() {
            document.getElementById('roomModal').classList.add('show');
        }
        
        function closeRoomModal() {
            document.getElementById('roomModal').classList.remove('show');
        }
        
        function editRoom(id) {
            alert('Edit room feature - ID: ' + id);
            // Implement edit room functionality
        }
        
        function viewRoomBookings(id) {
            alert('View bookings for room ID: ' + id);
            // Implement view bookings functionality
        }
        
        // Mark book as returned
        function markReturned(borrowId, bookId) {
            if (confirm('Mark this book as returned?')) {
                fetch('return_book_admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'borrow_id=' + borrowId + '&book_id=' + bookId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Book marked as returned!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error processing return');
                });
            }
        }
        
        // Toggle field options
        function toggleFieldOptions() {
            const type = document.getElementById('field_type').value;
            const optionsField = document.getElementById('options_field');
            optionsField.style.display = (type === 'select' || type === 'radio') ? 'block' : 'none';
        }
        
        // Save field order
        function saveFieldOrder() {
            const fields = [];
            document.querySelectorAll('.field-item').forEach((item, index) => {
                fields.push({
                    index: item.dataset.index,
                    order: index
                });
            });
            
            fetch('save_form_fields.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({fields: fields})
            });
        }
        
        // Edit field
        function editField(index) {
            alert('Edit field - Index: ' + index);
            // Implement edit field functionality
        }
        
        // Delete field
        function deleteField(index) {
            if (confirm('Delete this field?')) {
                fetch('delete_form_field.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'index=' + index
                })
                .then(() => location.reload())
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting field');
                });
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>