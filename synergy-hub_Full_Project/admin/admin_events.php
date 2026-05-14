<?php
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$message = '';
$error = '';

// Handle Event CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_event']) || isset($_POST['edit_event'])) {
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $organizer_id = intval($_POST['organizer_id']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $ticket_price = floatval($_POST['ticket_price']);
        $max_capacity = intval($_POST['max_capacity']);
        
        // Handle image upload
        $event_image = '';
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['event_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = 'uploads/events/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $new_filename = 'event_' . time() . '_' . uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['event_image']['tmp_name'], $upload_path)) {
                    $event_image = $upload_path;
                }
            }
        }
        
        if ($event_id > 0) {
            if ($event_image) {
                $sql = "UPDATE Events SET Title=?, Description=?, Location=?, StartTime=?, EndTime=?, OrganizerID=?, Category=?, ticket_price=?, max_capacity=?, event_image=? WHERE EventID=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssisdiss", $title, $description, $location, $start_time, $end_time, $organizer_id, $category, $ticket_price, $max_capacity, $event_image, $event_id);
            } else {
                $sql = "UPDATE Events SET Title=?, Description=?, Location=?, StartTime=?, EndTime=?, OrganizerID=?, Category=?, ticket_price=?, max_capacity=? WHERE EventID=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssisdi", $title, $description, $location, $start_time, $end_time, $organizer_id, $category, $ticket_price, $max_capacity, $event_id);
            }
            if (mysqli_stmt_execute($stmt)) {
                $message = "Event updated successfully!";
                logAdminActivity($conn, 'UPDATE_EVENT', "Updated event: $title");
            } else {
                $error = "Error updating event: " . mysqli_error($conn);
            }
        } else {
            $sql = "INSERT INTO Events (Title, Description, Location, StartTime, EndTime, OrganizerID, Category, ticket_price, max_capacity, event_image, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Upcoming')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssisiss", $title, $description, $location, $start_time, $end_time, $organizer_id, $category, $ticket_price, $max_capacity, $event_image);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Event created successfully!";
                logAdminActivity($conn, 'ADD_EVENT', "Added event: $title");
            } else {
                $error = "Error creating event: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['delete_event'])) {
        $event_id = intval($_POST['event_id']);
        $delete_sql = "DELETE FROM Events WHERE EventID = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $event_id);
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = "Event deleted successfully!";
            logAdminActivity($conn, 'DELETE_EVENT', "Deleted event ID: $event_id");
        } else {
            $error = "Error deleting event";
        }
    }
    
    if (isset($_POST['mark_used'])) {
        $booking_id = intval($_POST['booking_id']);
        $sql = "UPDATE EventBookings SET Status='Used', CheckInTime=NOW() WHERE BookingID=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $booking_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Ticket marked as used!";
        } else {
            $error = "Error marking ticket";
        }
    }
}

// Get all events
$events_sql = "SELECT e.*, u.Name as OrganizerName,
               (SELECT COUNT(*) FROM EventBookings WHERE EventID = e.EventID) as total_bookings,
               (SELECT COUNT(*) FROM EventLikes WHERE EventID = e.EventID) as total_likes
               FROM Events e
               LEFT JOIN Users u ON e.OrganizerID = u.UserID
               ORDER BY e.StartTime DESC";
$events_result = mysqli_query($conn, $events_sql);

// Get users for organizer dropdown
$users_sql = "SELECT UserID, Name FROM Users WHERE Role IN ('Admin', 'User') ORDER BY Name";
$users_result = mysqli_query($conn, $users_sql);

// Get bookings
$bookings_sql = "SELECT eb.*, e.Title as EventTitle, u.Name as UserName, u.Email 
                 FROM EventBookings eb
                 JOIN Events e ON eb.EventID = e.EventID
                 JOIN Users u ON eb.UserID = u.UserID
                 ORDER BY eb.BookingDate DESC";
$bookings_result = mysqli_query($conn, $bookings_sql);

// Stats
$stats_sql = "SELECT 
              COUNT(*) as total_events,
              SUM(CASE WHEN Status IN ('Upcoming', 'Ongoing') THEN 1 ELSE 0 END) as active_events,
              (SELECT COUNT(*) FROM EventBookings) as total_bookings,
              (SELECT SUM(ticket_price) FROM EventBookings eb JOIN Events e ON eb.EventID = e.EventID WHERE eb.Status='Confirmed') as total_revenue
              FROM Events";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'events';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
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
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        .stat-info h3 { font-size: 24px; color: #1e293b; margin: 0; }
        .stat-info p { color: #64748b; margin: 5px 0 0; }
        
        .event-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .event-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
        }
        .event-tab:hover { background: #f1f5f9; }
        .event-tab.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .event-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        .event-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .event-title { font-size: 18px; font-weight: 600; color: #1e293b; }
        .event-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-Upcoming { background: #e0f2fe; color: #0284c7; }
        .status-Ongoing { background: #dcfce7; color: #16a34a; }
        .status-Completed { background: #f1f5f9; color: #64748b; }
        .event-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0; font-size: 13px; color: #475569; }
        .event-details i { color: #667eea; width: 20px; }
        
        .booking-table { width: 100%; border-collapse: collapse; }
        .booking-table th, .booking-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .booking-table th { background: #f8fafc; color: #64748b; font-weight: 600; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
        
        .form-container { background: white; border-radius: 12px; padding: 25px; max-width: 700px; margin: 0 auto; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #475569; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; }
        
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-danger { background: #fee; color: #991b1b; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content">
                <h1 class="page-title"><i class="fa-solid fa-calendar-alt"></i> Event Management</h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-calendar"></i></div><div class="stat-info"><h3><?php echo $stats['total_events']; ?></h3><p>Total Events</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-play"></i></div><div class="stat-info"><h3><?php echo $stats['active_events']; ?></h3><p>Active Events</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-ticket"></i></div><div class="stat-info"><h3><?php echo $stats['total_bookings']; ?></h3><p>Total Bookings</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-dollar-sign"></i></div><div class="stat-info"><h3>Rs. <?php echo number_format($stats['total_revenue'], 2); ?></h3><p>Total Revenue</p></div></div>
                </div>
                
                <!-- Tabs -->
                <div class="event-tabs">
                    <a href="?tab=events" class="event-tab <?php echo $active_tab == 'events' ? 'active' : ''; ?>"><i class="fa-solid fa-list"></i> Events</a>
                    <a href="?tab=add" class="event-tab <?php echo $active_tab == 'add' ? 'active' : ''; ?>"><i class="fa-solid fa-plus"></i> Add Event</a>
                    <a href="?tab=bookings" class="event-tab <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>"><i class="fa-solid fa-ticket"></i> Bookings</a>
                    <a href="?tab=scanner" class="event-tab <?php echo $active_tab == 'scanner' ? 'active' : ''; ?>"><i class="fa-solid fa-qrcode"></i> QR Scanner</a>
                </div>
                
                <!-- Events Tab -->
                <div id="tab-events" class="tab-content <?php echo $active_tab == 'events' ? 'active' : ''; ?>">
                    <?php while($event = mysqli_fetch_assoc($events_result)): ?>
                    <div class="event-card">
                        <div class="event-header">
                            <span class="event-title"><?php echo htmlspecialchars($event['Title']); ?></span>
                            <span class="event-status status-<?php echo $event['Status']; ?>"><?php echo $event['Status']; ?></span>
                        </div>
                        <div class="event-details">
                            <div><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y - h:i A', strtotime($event['StartTime'])); ?></div>
                            <div><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($event['Location']); ?></div>
                            <div><i class="fa-solid fa-user"></i> Organizer: <?php echo htmlspecialchars($event['OrganizerName'] ?: 'N/A'); ?></div>
                            <div><i class="fa-solid fa-tag"></i> Category: <?php echo $event['Category']; ?></div>
                            <div><i class="fa-solid fa-ticket"></i> Capacity: <?php echo $event['booked_count']; ?>/<?php echo $event['max_capacity']; ?></div>
                            <div><i class="fa-solid fa-heart"></i> Likes: <?php echo $event['total_likes']; ?></div>
                            <div><i class="fa-solid fa-star"></i> Price: <?php echo $event['ticket_price'] > 0 ? 'Rs. ' . number_format($event['ticket_price'], 2) : 'FREE'; ?></div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-sm" onclick="editEvent(<?php echo $event['EventID']; ?>)">Edit</button>
                            <button class="btn btn-secondary btn-sm" onclick="viewEventBookings(<?php echo $event['EventID']; ?>)">View Bookings</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this event? This will delete all bookings.');">
                                <input type="hidden" name="event_id" value="<?php echo $event['EventID']; ?>">
                                <button type="submit" name="delete_event" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Add/Edit Event Tab -->
                <div id="tab-add" class="tab-content <?php echo $active_tab == 'add' ? 'active' : ''; ?>">
                    <div class="form-container">
                        <h3 id="formTitle">Add New Event</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="event_id" id="event_id" value="0">
                            
                            <div class="form-group">
                                <label>Event Title</label>
                                <input type="text" name="title" id="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" id="description" rows="4" required></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Start Date & Time</label>
                                    <input type="datetime-local" name="start_time" id="start_time" required>
                                </div>
                                <div class="form-group">
                                    <label>End Date & Time</label>
                                    <input type="datetime-local" name="end_time" id="end_time" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Location</label>
                                    <input type="text" name="location" id="location" required>
                                </div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category" id="category">
                                        <option value="SU">SU Event</option>
                                        <option value="Club">Club Event</option>
                                        <option value="Workshop">Workshop</option>
                                        <option value="Sports">Sports Event</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Organizer</label>
                                    <select name="organizer_id" id="organizer_id">
                                        <option value="0">None</option>
                                        <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                        <option value="<?php echo $user['UserID']; ?>"><?php echo htmlspecialchars($user['Name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Max Capacity</label>
                                    <input type="number" name="max_capacity" id="max_capacity" min="1" value="100" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ticket Price (Rs.)</label>
                                    <input type="number" name="ticket_price" id="ticket_price" min="0" step="0.01" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Event Image</label>
                                    <input type="file" name="event_image" accept="image/*">
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="add_event" class="btn btn-primary">Save Event</button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Bookings Tab -->
                <div id="tab-bookings" class="tab-content <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>">
                    <div class="table-container">
                        <table class="booking-table">
                            <thead>
                                <tr><th>Booking ID</th><th>Event</th><th>User</th><th>Ticket #</th><th>Status</th><th>Booking Date</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                <tr>
                                    <td>#<?php echo $booking['BookingID']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['EventTitle']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['UserName']); ?></td>
                                    <td><?php echo $booking['TicketNumber']; ?></td>
                                    <td><span class="status-badge status-<?php echo $booking['Status']; ?>"><?php echo $booking['Status']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['BookingDate'])); ?></td>
                                    <td>
                                        <?php if($booking['Status'] == 'Confirmed'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['BookingID']; ?>">
                                            <button type="submit" name="mark_used" class="btn btn-success btn-sm">Mark Used</button>
                                        </form>
                                        <?php endif; ?>
                                     </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- QR Scanner Tab -->
                <div id="tab-scanner" class="tab-content <?php echo $active_tab == 'scanner' ? 'active' : ''; ?>">
                    <div class="form-container" style="text-align: center;">
                        <h3>QR Code Scanner</h3>
                        <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                        <div id="scanResult" style="margin-top: 20px; padding: 15px; border-radius: 8px; display: none;"></div>
                        <input type="text" id="manualTicket" placeholder="Or enter ticket number manually" style="width: 100%; margin-top: 20px; padding: 12px;">
                        <button class="btn btn-primary" onclick="manualCheckIn()" style="margin-top: 10px;">Check In</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script>
        function editEvent(id) {
            fetch('get_event.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('event_id').value = data.EventID;
                    document.getElementById('title').value = data.Title;
                    document.getElementById('description').value = data.Description;
                    document.getElementById('location').value = data.Location;
                    document.getElementById('start_time').value = data.StartTime.replace(' ', 'T');
                    document.getElementById('end_time').value = data.EndTime.replace(' ', 'T');
                    document.getElementById('category').value = data.Category;
                    document.getElementById('organizer_id').value = data.OrganizerID;
                    document.getElementById('max_capacity').value = data.max_capacity;
                    document.getElementById('ticket_price').value = data.ticket_price;
                    document.getElementById('formTitle').textContent = 'Edit Event';
                    document.querySelector('a[href="?tab=add"]').click();
                });
        }
        
        function resetForm() {
            document.getElementById('event_id').value = '0';
            document.getElementById('formTitle').textContent = 'Add New Event';
            document.querySelector('form').reset();
        }
        
        function viewEventBookings(eventId) {
            alert('Event bookings - Coming soon!');
        }
        
        // QR Scanner
        let html5QrCode;
        function startScanner() {
            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanError);
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            html5QrCode.stop();
            validateTicket(decodedText);
        }
        
        function onScanError(errorMessage) { console.log(errorMessage); }
        
        function validateTicket(ticketData) {
            fetch('validate_ticket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ticket_data=' + encodeURIComponent(ticketData)
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('scanResult');
                resultDiv.style.display = 'block';
                if(data.success) {
                    resultDiv.innerHTML = `<div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px;">
                        <i class="fa-solid fa-check-circle"></i> ${data.message}<br>
                        Event: ${data.event_title}<br>User: ${data.user_name}
                    </div>`;
                    setTimeout(() => { resultDiv.style.display = 'none'; startScanner(); }, 3000);
                } else {
                    resultDiv.innerHTML = `<div style="background: #fee; color: #991b1b; padding: 15px; border-radius: 8px;">
                        <i class="fa-solid fa-times-circle"></i> ${data.message}
                    </div>`;
                    setTimeout(() => { resultDiv.style.display = 'none'; startScanner(); }, 3000);
                }
            });
        }
        
        function manualCheckIn() {
            const ticketNumber = document.getElementById('manualTicket').value;
            if(ticketNumber) {
                validateTicket(ticketNumber);
                document.getElementById('manualTicket').value = '';
            }
        }
        
        if(document.getElementById('tab-scanner').classList.contains('active')) {
            startScanner();
        }
    </script>
</body>
</html>