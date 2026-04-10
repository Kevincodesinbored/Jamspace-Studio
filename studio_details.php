<?php
require_once 'config/database.php';
if (!isLoggedIn()) { redirect('login.php'); }
$user = getCurrentUser();
$conn = getDBConnection();
$studio_id = isset($_GET['id']) ? sanitize($_GET['id']) : '';
if (empty($studio_id)) { redirect('home.php'); }

$stmt = $conn->prepare("SELECT * FROM studios WHERE id = ?");
$stmt->bind_param("s", $studio_id);
$stmt->execute();
$studio = $stmt->get_result()->fetch_assoc();
if (!$studio) { redirect('home.php'); }

$stmt = $conn->prepare("SELECT r.*, u.fullname FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.studio_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param("s", $studio_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'config/header.php'; 
?>

<div class="container">
    <div class="modern-card" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px;">
        <div>
            <img src="<?php echo htmlspecialchars($studio['image_path']); ?>" style="width: 100%; border-radius: var(--radius); object-fit: cover; height: 400px;">
        </div>
        <div>
            <h1 style="color: var(--secondary); margin-bottom: 10px;"><?php echo htmlspecialchars($studio['name']); ?></h1>
            <div style="color: #ffc107; margin-bottom: 20px;">
                <?php for($i=1; $i<=5; $i++) echo $i <= round($studio['average_rating']) ? '★' : '☆'; ?>
                <span style="color: var(--text-gray); font-size: 0.9rem; margin-left: 10px;">(<?php echo $studio['total_reviews']; ?> reviews)</span>
            </div>
            <p style="color: var(--text-gray); margin-bottom: 30px; font-size: 1.1rem;"><?php echo htmlspecialchars($studio['description']); ?></p>
            <div style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 30px;">
                $<?php echo number_format($studio['price_per_hour'], 2); ?><span style="font-size: 1rem; color: var(--text-gray);"> /hour</span>
            </div>
            <a href="booking.php?studio=<?php echo $studio['id']; ?>" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">Book Now <i class="fa-solid fa-calendar-plus"></i></a>
        </div>
    </div>

    <div class="modern-card">
        <h2 style="margin-bottom: 20px;"><i class="fa-solid fa-comments"></i> Customer Reviews</h2>
        <?php if (count($reviews) == 0): ?>
            <p style="text-align: center; color: #ccc; padding: 30px;">No reviews yet.</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div style="padding: 20px 0; border-bottom: 1px solid #eee;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <strong><?php echo htmlspecialchars($review['fullname']); ?></strong>
                    <small style="color: #999;"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                </div>
                <div style="color: #ffc107; margin-bottom: 10px;">
                    <?php for($i=1; $i<=5; $i++) echo $i <= $review['rating'] ? '★' : '☆'; ?>
                </div>
                <p style="color: var(--text-gray); font-style: italic;">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include 'config/footer.php'; ?>
