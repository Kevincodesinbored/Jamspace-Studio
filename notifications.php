<?php
require_once 'config/database.php';
if (!isLoggedIn()) { redirect('login.php'); }
$user = getCurrentUser();
$conn = getDBConnection();

if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", intval($_GET['id']), $user['id']);
    $stmt->execute();
    redirect('notifications.php');
}

if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    redirect('notifications.php');
}

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", intval($_GET['id']), $user['id']);
    $stmt->execute();
    redirect('notifications.php');
}

$stmt = $conn->prepare("SELECT n.*, s.name as studio_name, b.booking_date, b.booking_time FROM notifications n LEFT JOIN bookings b ON n.booking_id = b.id LEFT JOIN studios s ON b.studio_id = s.id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 50");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function getNotifColor($type) {
    $colors = ['booking_confirmed' => '#28a745', 'booking_reminder' => '#ffc107', 'booking_completed' => '#17a2b8', 'payment_confirmed' => '#28a745', 'booking_cancelled' => '#dc3545'];
    return $colors[$type] ?? '#6c757d';
}
function getNotifIcon($type) {
    $icons = ['booking_confirmed' => 'fa-check-circle', 'booking_reminder' => 'fa-clock', 'booking_completed' => 'fa-flag-checkered', 'payment_confirmed' => 'fa-credit-card', 'booking_cancelled' => 'fa-times-circle'];
    return $icons[$type] ?? 'fa-bell';
}

include 'config/header.php'; 
?>

<div class="container">
    <div class="modern-card" style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2><i class="fa-solid fa-bell"></i> Notifications</h2>
            <?php if (count($notifications) > 0): ?>
                <a href="notifications.php?mark_all_read=1" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.8rem;">Mark All Read</a>
            <?php endif; ?>
        </div>
        
        <?php if (count($notifications) == 0): ?>
            <div style="text-align: center; padding: 50px 0; color: #ccc;">
                <i class="fa-solid fa-bell-slash" style="font-size: 4rem; display: block; margin-bottom: 15px;"></i>
                <p>No notifications yet</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
            <div class="notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>" style="border-left-color: <?php echo getNotifColor($notif['type']); ?>;">
                <div style="font-size: 1.5rem; color: <?php echo getNotifColor($notif['type']); ?>;">
                    <i class="fa-solid <?php echo getNotifIcon($notif['type']); ?>"></i>
                </div>
                <div style="flex: 1;">
                    <h4 style="margin: 0;"><?php echo htmlspecialchars($notif['title']); ?></h4>
                    <p style="font-size: 0.9rem; color: var(--text-gray);"><?php echo htmlspecialchars($notif['message']); ?></p>
                    <small style="color: #999;"><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></small>
                </div>
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <?php if (!$notif['is_read']): ?>
                        <a href="notifications.php?mark_read=1&id=<?php echo $notif['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.7rem;">Read</a>
                    <?php endif; ?>
                    <a href="notifications.php?delete=1&id=<?php echo $notif['id']; ?>" class="btn btn-remove" style="padding: 5px 10px; font-size: 0.7rem;">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include 'config/footer.php'; ?>
