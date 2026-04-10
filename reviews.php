<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
$conn = getDBConnection();

$success = '';
$error = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $booking_id = intval($_POST['booking_id']);
    $studio_id = sanitize($_POST['studio_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5";
    } else {
        $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'confirmed'");
        $stmt->bind_param("ii", $booking_id, $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, studio_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisis", $booking_id, $user['id'], $studio_id, $rating, $comment);
            
            if ($stmt->execute()) {
                updateStudioRating($conn, $studio_id);
                $success = "Review submitted successfully!";
            } else {
                $error = "Review already submitted or failed.";
            }
        } else {
            $error = "Invalid booking.";
        }
        $stmt->close();
    }
}

// Get bookings that can be reviewed
$stmt = $conn->prepare("
    SELECT b.id, b.studio_id, s.name as studio_name, b.booking_date, b.booking_time,
           r.id as review_id
    FROM bookings b
    JOIN studios s ON b.studio_id = s.id
    LEFT JOIN reviews r ON b.id = r.booking_id
    WHERE b.user_id = ? 
    AND b.status = 'confirmed'
    AND CONCAT(b.booking_date, ' ', b.booking_time) < NOW()
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's submitted reviews
$stmt = $conn->prepare("
    SELECT r.*, s.name as studio_name, b.booking_date, b.booking_time
    FROM reviews r
    JOIN studios s ON r.studio_id = s.id
    JOIN bookings b ON r.booking_id = b.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$my_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function updateStudioRating($conn, $studio_id) {
    $stmt = $conn->prepare("UPDATE studios SET average_rating = (SELECT AVG(rating) FROM reviews WHERE studio_id = ?), total_reviews = (SELECT COUNT(*) FROM reviews WHERE studio_id = ? ) WHERE id = ?");
    $stmt->bind_param("sss", $studio_id, $studio_id, $studio_id);
    $stmt->execute();
    $stmt->close();
}

include 'config/header.php'; 
?>

<style>
    /* Custom Style khusus untuk Review & Modal agar tetap Modern */
    .review-card {
        background: #fcfcfc;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 15px;
        border: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: var(--transition);
    }
    .review-card:hover { border-color: var(--primary); background: #fff; }

    .star-rating { color: #ffc107; font-size: 1rem; }

    /* MODAL MODERN */
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
    }
    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: var(--radius);
        width: 90%;
        max-width: 500px;
        position: relative;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    .close-modal {
        position: absolute;
        right: 20px; top: 20px;
        font-size: 24px; cursor: pointer; color: #aaa;
    }
    .rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 5px;
        margin: 15px 0;
    }
    .rating-input input { display: none; }
    .rating-input label {
        font-size: 30px; color: #ddd; cursor: pointer; transition: var(--transition);
    }
    .rating-input input:checked ~ label,
    .rating-input label:hover,
    .rating-input label:hover ~ label { color: #ffc107; }
</style>

<div class="container">
    <?php if ($error): ?> <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div> <?php endif; ?>
    <?php if ($success): ?> <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success; ?></div> <?php endif; ?>

    <!-- Section 1: Bookings to Review -->
    <div class="modern-card" style="margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px; color: var(--secondary);"><i class="fa-solid fa-star" style="color: var(--primary);"></i> Bookings to Review</h2>
        
        <?php if (count($bookings) == 0): ?>
            <p style="color: var(--text-gray); text-align: center; padding: 30px 0;">No completed bookings to review.</p>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <?php if (!$booking['review_id']): ?>
                <div class="review-card">
                    <div>
                        <h4 style="margin: 0;"><?php echo htmlspecialchars($booking['studio_name']); ?></h4>
                        <p style="font-size: 0.85rem; color: var(--text-gray); margin: 5px 0;">
                            <i class="fa-solid fa-calendar"></i> <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> at <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                        </p>
                    </div>
                    <button class="btn btn-primary" onclick="openReviewModal(<?php echo $booking['id']; ?>, '<?php echo $booking['studio_id']; ?>', '<?php echo htmlspecialchars($booking['studio_name']); ?>')">
                        <i class="fa-solid fa-pen"></i> Write Review
                    </button>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Section 2: My Reviews -->
    <div class="modern-card">
        <h2 style="margin-bottom: 20px; color: var(--secondary);"><i class="fa-solid fa-list"></i> My Reviews</h2>
        
        <?php if (count($my_reviews) == 0): ?>
            <p style="color: var(--text-gray); text-align: center; padding: 30px 0;">You haven't submitted any reviews yet.</p>
        <?php else: ?>
            <?php foreach ($my_reviews as $review): ?>
            <div style="padding: 20px 0; border-bottom: 1px solid #eee; display: flex; gap: 20px;">
                <div style="font-size: 2rem; color: #ddd;"><i class="fa-solid fa-quote-left" style="color: var(--primary); opacity: 0.3;"></i></div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0;"><?php echo htmlspecialchars($review['studio_name']); ?></h4>
                        <div class="star-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa-solid fa-star" style="color: <?php echo $i <= $review['rating'] ? '#ffc107' : '#ddd'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p style="color: var(--text-gray); font-style: italic; margin-bottom: 10px;">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                    <small style="color: #bbb;"><i class="fa-regular fa-clock"></i> Reviewed on <?php echo date('F j, Y', strtotime($review['created_at'])); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeReviewModal()">&times;</span>
        <h2 style="color: var(--secondary); margin-bottom: 10px;">Write a Review</h2>
        <h3 id="modalStudioName" style="color: var(--primary); font-size: 1.2rem; margin-bottom: 20px;"></h3>
        
        <form method="POST">
            <input type="hidden" name="booking_id" id="modalBookingId">
            <input type="hidden" name="studio_id" id="modalStudioId">
            
            <label>Your Rating</label>
            <div class="rating-input">
                <input type="radio" name="rating" value="5" id="star5" required><label for="star5">★</label>
                <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
            </div>
            
            <label>Your Experience</label>
            <textarea name="comment" id="comment" rows="4" placeholder="Tell us how your jam session went..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; margin-top: 10px;"></textarea>
            
            <button type="submit" name="submit_review" class="btn btn-primary" style="width: 100%; margin-top: 20px; padding: 15px;">
                <i class="fa-solid fa-paper-plane"></i> Submit Review
            </button>
        </form>
    </div>
</div>

<script>
    function openReviewModal(bookingId, studioId, studioName) {
        document.getElementById('modalBookingId').value = bookingId;
        document.getElementById('modalStudioId').value = studioId;
        document.getElementById('modalStudioName').textContent = studioName;
        document.getElementById('reviewModal').style.display = 'block';
    }
    function closeReviewModal() {
        document.getElementById('reviewModal').style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == document.getElementById('reviewModal')) {
            closeReviewModal();
        }
    }
</script>

<?php include 'config/footer.php'; ?>
