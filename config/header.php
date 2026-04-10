<?php 
// Periksa session di database.php
$conn = getDBConnection(); 
$user = getCurrentUser();

// Ambil jumlah notifikasi belum dibaca secara otomatis
$stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread_count'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <title>JamSpace - Studio Booking</title>
</head>
<body>
    <header class="main-header">
        <div class="nav-container">
            <a href="home.php" class="logo">Jam<span>Space</span> 🎶</a>
            <nav>
                <div class="user-welcome">
                    <i class="fa-solid fa-circle-user"></i> 
                    <span><?php echo htmlspecialchars($user['fullname']); ?></span>
                </div>
                <ul class="nav-links">
                    <li><a href="home.php"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="booking.php"><i class="fa-solid fa-calendar-plus"></i> Book</a></li>
                    <li><a href="reviews.php"><i class="fa-solid fa-star"></i> Reviews</a></li>
                    <li>
                        <a href="notifications.php" class="notif-link">
                            <i class="fa-solid fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="unread-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="cart.php" class="cart-btn"><i class="fa-solid fa-cart-shopping"></i> Cart</a></li>
                    <li><a href="logout.php" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a></li>
                </ul>
            </nav>
        </div>
    </header>
