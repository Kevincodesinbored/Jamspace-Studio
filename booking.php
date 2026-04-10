<?php
require_once 'config/database.php';
if (!isLoggedIn()) { redirect('login.php'); }
$user = getCurrentUser();
$conn = getDBConnection();

$studios = [];
$result = $conn->query("SELECT * FROM studios ORDER BY id");
while ($row = $result->fetch_assoc()) { $studios[] = $row; }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $studio_id = sanitize($_POST['studio']);
    $booking_date = $_POST['date'];
    $booking_time = $_POST['time'];
    $duration = intval($_POST['duration']);
    
    $stmt = $conn->prepare("SELECT price_per_hour FROM studios WHERE id = ?");
    $stmt->bind_param("s", $studio_id);
    $stmt->execute();
    $studio = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($studio) {
        $total_price = $studio['price_per_hour'] * $duration;
        if (strlen($fullname) < 3) { $error = "Name must be at least 3 characters"; } 
        elseif (!preg_match('/^[0-9+\-\s()]{8,}$/', $phone)) { $error = "Invalid phone number"; } 
        elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) { $error = "Please select a future date"; } 
        elseif ($duration < 1 || $duration > 12) { $error = "Duration must be between 1 and 12 hours"; } 
        else {
            $stmt = $conn->prepare("SELECT id FROM bookings WHERE studio_id = ? AND booking_date = ? AND status != 'cancelled' AND ((booking_time <= ? AND DATE_ADD(CONCAT(booking_date, ' ', booking_time), INTERVAL duration HOUR) > ?) OR (booking_time < DATE_ADD(?, INTERVAL ? HOUR) AND booking_time >= ?))");
            $stmt->bind_param("sssssss", $studio_id, $booking_date, $booking_time, $booking_time, $booking_time, $duration, $booking_time);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { $error = "Studio is not available at this time."; } 
            else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, studio_id, fullname, phone, booking_date, booking_time, duration, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssid", $user['id'], $studio_id, $fullname, $phone, $booking_date, $booking_time, $duration, $total_price);
                if ($stmt->execute()) { $_SESSION['success_message'] = "Added to cart!"; redirect('cart.php'); } 
                else { $error = "Failed to add booking."; }
            }
            $stmt->close();
        }
    }
}

include 'config/header.php'; 
?>

<div class="container">
    <div class="modern-card" style="max-width: 700px; margin: 0 auto;">
        <h2 style="margin-bottom: 20px; color: var(--secondary);"><i class="fa-solid fa-calendar-check"></i> Book a Studio</h2>
        
        <?php if ($error): ?> <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div> <?php endif; ?>
        
        <form method="POST" id="bookingForm">
            <label>Full Name</label>
            <input type="text" name="fullname" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : htmlspecialchars($user['fullname']); ?>">
            
            <label>Phone Number</label>
            <input type="tel" name="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            
            <label>Choose Studio</label>
            <select name="studio" id="studio" required>
                <option value="">-- Select Studio --</option>
                <?php foreach ($studios as $studio): ?>
                <option value="<?php echo $studio['id']; ?>" data-price="<?php echo $studio['price_per_hour']; ?>" <?php echo (isset($_GET['studio']) && $_GET['studio'] == $studio['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($studio['name']); ?> - $<?php echo number_format($studio['price_per_hour'], 2); ?>/hr
                </option>
                <?php endforeach; ?>
            </select>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div><label>Date</label><input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>"></div>
                <div><label>Time</label><input type="time" name="time" required></div>
            </div>

            <label>Duration (Hours)</label>
            <input type="number" name="duration" id="duration" min="1" max="12" value="1" required>

            <div id="pricePreview" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: var(--radius); text-align: center; border: 1px dashed var(--primary);">
                <span style="color: var(--text-gray);">Estimated Total: </span>
                <strong style="font-size: 1.5rem; color: var(--primary);" id="totalPrice">$0</strong>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;"><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
      const studioSelect = document.getElementById('studio');
      const durationInput = document.getElementById('duration');
      const totalPriceDisplay = document.getElementById('totalPrice');
      function updatePrice() {
        const selectedOption = studioSelect.options[studioSelect.selectedIndex];
        const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const duration = parseInt(durationInput.value) || 0;
        totalPriceDisplay.textContent = '$' + (price * duration).toFixed(2);
      }
      studioSelect.addEventListener('change', updatePrice);
      durationInput.addEventListener('input', updatePrice);
    });
</script>
<?php include 'config/footer.php'; ?>
