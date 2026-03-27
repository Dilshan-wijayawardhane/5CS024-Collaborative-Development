<?php

if (!isset($admin)) {
    $admin_id = $_SESSION['user_id'];
    $admin_sql = "SELECT Name, Email, StudentID FROM Users WHERE UserID = ?";
    $admin_stmt = mysqli_prepare($conn, $admin_sql);
    mysqli_stmt_bind_param($admin_stmt, "i", $admin_id);
    mysqli_stmt_execute($admin_stmt);
    $admin_result = mysqli_stmt_get_result($admin_stmt);
    $admin = mysqli_fetch_assoc($admin_result);
}
?>
<div class="top-bar">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </button>
    
    <div class="top-bar-right">
        
        <div class="admin-profile">
            <div class="profile-img" onclick="toggleProfileMenu()">
                <i class="fa-solid fa-user-circle" style="font-size: 40px; color: #667eea;"></i>
            </div>
            <div class="profile-menu" id="profileMenu">
                <div class="profile-header">
                    <i class="fa-solid fa-user-circle" style="font-size: 50px; color: #667eea;"></i>
                    <div>
                        <h4><?php echo htmlspecialchars($admin['Name']); ?></h4>
                        <p><?php echo htmlspecialchars($admin['Email']); ?></p>
                        <p style="font-size: 11px;">Student ID: <?php echo htmlspecialchars($admin['StudentID']); ?></p>
                    </div>
                </div>
                <div class="profile-menu-items">
                    <a href="../index.php" target="_blank">
                        <i class="fa-regular fa-eye"></i> View Site
                    </a>
                    <a href="settings.php">
                        <i class="fa-regular fa-gear"></i> Settings
                    </a>
                    <hr>
                    <a href="logout.php" class="text-danger">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
}


function toggleProfileMenu() {
    document.getElementById('profileMenu').classList.toggle('show');
}


document.addEventListener('click', function(e) {
    if (!e.target.closest('.admin-profile')) {
        document.getElementById('profileMenu')?.classList.remove('show');
    }
});
</script>