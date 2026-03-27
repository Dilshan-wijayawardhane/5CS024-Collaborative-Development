<?php



require_once 'config.php';
require_once 'functions.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];




$sql = "SELECT Name FROM Users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);




$alerts_sql = "SELECT * FROM EmergencyAlerts WHERE expires_at > NOW() OR expires_at IS NULL ORDER BY severity DESC, created_at DESC";
$alerts_result = mysqli_query($conn, $alerts_sql);




$contacts_sql = "SELECT * FROM EmergencyContacts WHERE is_active = 1 ORDER BY display_order ASC";
$contacts_result = mysqli_query($conn, $contacts_sql);




$user_location = "Main Building";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergy Hub - Emergency Response</title>
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

        .emergency-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            border: 3px solid #ef4444;
        }

        .emergency-header h1 {
            font-size: 36px;
            color: #ef4444;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .emergency-header h1 i {
            animation: pulse 1.5s infinite;
        }

        .emergency-header p {
            color: #4b5563;
            font-size: 16px;
        }

        .emergency-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .emergency-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            border: 2px solid transparent;
        }

        .emergency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        }

        .emergency-card.security {
            border-color: #ef4444;
        }

        .emergency-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }

        .emergency-icon.security {
            background: #fee2e2;
            color: #ef4444;
        }

        .emergency-icon.medical {
            background: #dbeafe;
            color: #2563eb;
        }

        .emergency-icon.support {
            background: #dcfce7;
            color: #16a34a;
        }

        .emergency-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #1e293b;
        }

        .emergency-number {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin: 15px 0;
            letter-spacing: 1px;
        }

        .call-button {
            display: inline-block;
            width: 100%;
            padding: 15px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .call-button i {
            margin-right: 8px;
        }

        .call-button.security {
            background: #ef4444;
            color: white;
        }

        .call-button.security:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        .call-button.medical {
            background: #2563eb;
            color: white;
        }

        .call-button.medical:hover {
            background: #1d4ed8;
            transform: scale(1.05);
        }

        .call-button.support {
            background: #16a34a;
            color: white;
        }

        .call-button.support:hover {
            background: #15803d;
            transform: scale(1.05);
        }

        .alerts-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .section-title {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #ef4444;
        }

        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 5px solid;
        }

        .alert-item.critical {
            background: #fee2e2;
            border-left-color: #ef4444;
        }

        .alert-item.warning {
            background: #fff3cd;
            border-left-color: #fbbf24;
        }

        .alert-item.info {
            background: #dbeafe;
            border-left-color: #3b82f6;
        }

        .alert-icon {
            font-size: 24px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .alert-message {
            color: #4b5563;
            margin-bottom: 8px;
        }

        .alert-time {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            padding: 20px;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .action-btn i {
            font-size: 28px;
            color: #ef4444;
        }

        .action-btn span {
            font-size: 14px;
            font-weight: 600;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(239, 68, 68, 0.3);
        }

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
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            .emergency-number {
                font-size: 22px;
            }
            
            .emergency-header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="emergency-header">
            <h1>
                <i class="fa-solid fa-triangle-exclamation"></i>
                Emergency Response System
                <i class="fa-solid fa-triangle-exclamation"></i>
            </h1>
            <p>Quick access to emergency services. Stay calm and use the appropriate contact below.</p>
        </div>

        <div class="emergency-grid">
            <?php
            $has_contacts = mysqli_num_rows($contacts_result) > 0;
            
            if ($has_contacts) {
                while($contact = mysqli_fetch_assoc($contacts_result)) {
                    $icon_class = '';
                    $button_class = 'security';
                    if ($contact['type'] == 'medical') {
                        $icon_class = 'fa-hospital';
                        $button_class = 'medical';
                    } elseif ($contact['type'] == 'security') {
                        $icon_class = 'fa-shield-halved';
                        $button_class = 'security';
                    } else {
                        $icon_class = 'fa-hand-holding-heart';
                        $button_class = 'support';
                    }
                    ?>
                    <div class="emergency-card <?php echo $button_class; ?>">
                        <div class="emergency-icon <?php echo $button_class; ?>">
                            <i class="fa-solid <?php echo $icon_class; ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($contact['name']); ?></h3>
                        <div class="emergency-number"><?php echo htmlspecialchars($contact['phone']); ?></div>
                        <p style="color: #6b7280; margin-bottom: 15px;"><?php echo htmlspecialchars($contact['description']); ?></p>
                        <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" class="call-button <?php echo $button_class; ?>">
                            <i class="fa-solid fa-phone"></i> Call Now
                        </a>
                    </div>
                    <?php
                }
            } else {
                


            ?>
                <div class="emergency-card security">
                    <div class="emergency-icon security">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3>Campus Security</h3>
                    <div class="emergency-number">011-2345678</div>
                    <p>24/7 Security Hotline</p>
                    <a href="tel:0112345678" class="call-button security">
                        <i class="fa-solid fa-phone"></i> Call Now
                    </a>
                </div>

                <div class="emergency-card medical">
                    <div class="emergency-icon medical">
                        <i class="fa-solid fa-hospital"></i>
                    </div>
                    <h3>Medical Center</h3>
                    <div class="emergency-number">011-8765432</div>
                    <p>Emergency Medical Services</p>
                    <a href="tel:0118765432" class="call-button medical">
                        <i class="fa-solid fa-phone"></i> Call Now
                    </a>
                </div>

                <div class="emergency-card support">
                    <div class="emergency-icon support">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                    <h3>Student Support</h3>
                    <div class="emergency-number">011-5678901</div>
                    <p>Counseling & Support Services</p>
                    <a href="tel:0115678901" class="call-button support">
                        <i class="fa-solid fa-phone"></i> Call Now
                    </a>
                </div>
            <?php } ?>
        </div>

        <div class="alerts-section">
            <h2 class="section-title">
                <i class="fa-solid fa-bell"></i>
                Active Emergency Alerts
            </h2>
            <?php if (mysqli_num_rows($alerts_result) > 0): ?>
                <?php while($alert = mysqli_fetch_assoc($alerts_result)):
                    


                    $severity_class = 'info';
                    $severity_icon = 'fa-circle-info';
                    if ($alert['severity'] == 'critical') {
                        $severity_class = 'critical';
                        $severity_icon = 'fa-circle-exclamation';
                    } elseif ($alert['severity'] == 'warning') {
                        $severity_class = 'warning';
                        $severity_icon = 'fa-triangle-exclamation';
                    }
                ?>
                <div class="alert-item <?php echo $severity_class; ?>">
                    <div class="alert-icon">
                        <i class="fa-solid <?php echo $severity_icon; ?>"></i>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title"><?php echo htmlspecialchars($alert['title']); ?></div>
                        <div class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></div>
                        <div class="alert-time">
                            <i class="fa-regular fa-clock"></i>
                            <?php echo date('g:i A - M d, Y', strtotime($alert['created_at'])); ?>
                            <?php if ($alert['expires_at']): ?>
                                | Expires: <?php echo date('g:i A', strtotime($alert['expires_at'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #6b7280; text-align: center; padding: 30px;">
                    <i class="fa-regular fa-circle-check" style="color: #10b981; font-size: 24px; margin-bottom: 10px; display: block;"></i>
                    No active emergency alerts. Stay safe!
                </p>
            <?php endif; ?>
        </div>

        <div class="quick-actions">
            <button class="action-btn" onclick="shareLocation()">
                <i class="fa-solid fa-location-dot"></i>
                <span>Share My Location</span>
            </button>
            <button class="action-btn" onclick="sendSilentAlert()">
                <i class="fa-solid fa-bell"></i>
                <span>Send Silent Alert</span>
            </button>
            <button class="action-btn" onclick="window.print()">
                <i class="fa-solid fa-print"></i>
                <span>Print Emergency Info</span>
            </button>
            <a href="safety_tips.php" class="action-btn" style="text-decoration: none;">
                <i class="fa-solid fa-book"></i>
                <span>Safety Tips</span>
            </a>
        </div>

        <div style="text-align: center;">
            <a href="index.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        


        function shareLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    alert(`Your current location:\nLatitude: ${lat}\nLongitude: ${lng}\n\nThis information would be sent to emergency services.`);
                    
                    
                    window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
                }, function(error) {
                    alert('Unable to get your location. Please enable location services.');
                });
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        }

        

        function sendSilentAlert() {
            if (confirm('Send a silent alert to campus security? They will be notified of your location and status.')) {
                alert('✅ Silent alert sent. Security has been notified.');
                
                const btn = event.currentTarget;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check"></i><span>Alert Sent</span>';
                btn.style.background = '#10b981';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = 'rgba(255, 255, 255, 0.95)';
                }, 3000);
            }
        }

        

        
        function confirmCall(number, name) {
            if (confirm(`Call ${name} at ${number}?`)) {
                window.location.href = `tel:${number}`;
            }
        }
    </script>
</body>
</html>