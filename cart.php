<?php
require_once 'config/database.php';
require_once 'config/notification_helper.php';
if (!isLoggedIn()) { redirect('login.php'); }
$user = getCurrentUser();
$conn = getDBConnection();

if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", intval($_GET['id']), $user['id']);
    $stmt->execute();
    redirect('cart.php');
}

if (isset($_POST['checkout'])) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($cart_items) > 0) {
            $total_amount = 0;
            $stmt_book = $conn->prepare("INSERT INTO bookings (user_id, studio_id, fullname, phone, booking_date, booking_time, duration, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
            foreach ($cart_items as $item) {
                $stmt_book->bind_param("isssssid", $user['id'], $item['studio_id'], $item['fullname'], $item['phone'], $item['booking_date'], $item['booking_time'], $item['duration'], $item['total_price']);
                $stmt_book->execute();
                $booking_id = $conn->insert_id;
                
                $s_stmt = $conn->prepare("SELECT name FROM studios WHERE id = ?");
                $s_stmt->bind_param("s", $item['studio_id']);
                $s_stmt->execute();
                $studio_name = $s_stmt->get_result()->fetch_assoc()['name'];
                sendBookingConfirmationNotification($conn, $booking_id, $user['id'], $studio_name, $item['booking_date'], $item['booking_time']);
                $total_amount += $item['total_price'];
            }
            sendPaymentConfirmationNotification($conn, $conn->insert_id, $user['id'], $total_amount);
            $conn->query("DELETE FROM cart WHERE user_id = {$user['id']}");
            $conn->commit();
            echo "<script>alert('Checkout successful!'); window.location.href = 'notifications.php';</script>";
        }
    } catch (Exception $e) { $conn->rollback(); }
}

$stmt = $conn->prepare("SELECT c.*, s.name as studio_name, s.image_path FROM cart c JOIN studios s ON c.studio_id = s.id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$grand_total = array_sum(array_column($cart_items, 'total_price'));

include 'config/header.php'; 
?>

<div class="container">
    <div class="modern-card" style="max-width: 900px; margin: 0 auto;">
        <h2 style="margin-bottom: 30px;"><i class="fa-solid fa-cart-shopping"></i> Your Reservations</h2>
        
        <?php if (count($cart_items) == 0): ?>
            <div style="text-align: center; padding: 50px 0; color: var(--text-gray);">
                <i class="fa-solid fa-cart-shopping" style="font-size: 4rem; color: #ddd; margin-bottom: 20px; display: block;"></i>
                <p>Your cart is empty</p>
                <a href="booking.php" class="btn btn-primary" style="margin-top: 20px;">Book a Studio</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                <div class="notification-item" style="border-left-color: var(--primary);">
                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" style="width: 100px; height: 100px; border-radius: 8px; object-fit: cover;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0;"><?php echo htmlspecialchars($item['studio_name']); ?></h4>
                        <p style="font-size: 0.85rem; color: var(--text-gray);">
                            <i class="fa-solid fa-calendar"></i> <?php echo date('M j, Y', strtotime($item['booking_date'])); ?> | 
                            <i class="fa-solid fa-clock"></i> <?php echo date('g:i A', strtotime($item['booking_time'])); ?> 
                            (<?php echo $item['duration']; ?>h)
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <p style="font-weight: 700; color: var(--primary); font-size: 1.2rem; margin-bottom: 10px;">$<?php echo number_format($item['total_price'], 2); ?></p>
                        <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" class="btn btn-remove" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fa-solid fa-trash"></i> Remove
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 1.2rem;">Total: <strong style="color: var(--primary); font-size: 1.5rem;">$<?php echo number_format($grand_total, 2); ?></strong></div>
                <div style="display: flex; gap: 15px;">
                    <a href="booking.php" class="btn btn-outline">Add More</a>
                    <form method="POST">
                        <button type="submit" name="checkout" class="btn btn-primary">Checkout Now <i class="fa-solid fa-credit-card"></i></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'config/footer.php'; ?>
