<?php
require_once 'config/database.php';
if (!isLoggedIn()) { redirect('login.php'); }
$conn = getDBConnection();
$studios = [];
$result = $conn->query("SELECT * FROM studios ORDER BY id");
while ($row = $result->fetch_assoc()) { $studios[] = $row; }
$conn->close();

include 'config/header.php'; 
?>

<div class="container">
    <section style="text-align: center; padding: 60px 0; margin-bottom: 50px;">
        <h1 style="font-size: 3rem; color: var(--secondary); margin-bottom: 10px;">Find Your Perfect <span style="color: var(--primary);">Jamming Space</span></h1>
        <p style="color: var(--text-gray); font-size: 1.1rem; margin-bottom: 30px;">Professional studios for band practice and recording.</p>
        <a href="booking.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 15px 40px;">Book Now <i class="fa-solid fa-arrow-right"></i></a>
    </section>

    <h2 style="margin-bottom: 25px; border-left: 5px solid var(--primary); padding-left: 15px;">Available Studios</h2>
    
    <div class="studio-list">
        <?php foreach ($studios as $studio): ?>
        <div class="studio-card">
            <img src="<?php echo htmlspecialchars($studio['image_path']); ?>" alt="Studio">
            <div class="studio-card-content">
                <h3><?php echo htmlspecialchars($studio['name']); ?></h3>
                <div style="color: #ffc107; margin: 5px 0; font-size: 0.9rem;">
                    <?php for($i=1; $i<=5; $i++) echo $i <= round($studio['average_rating']) ? '★' : '☆'; ?>
                    <span style="color: #999; margin-left: 5px;">(<?php echo $studio['total_reviews']; ?>)</span>
                </div>
                <p class="price-tag" style="font-size: 1.3rem; font-weight: 700; color: var(--primary); margin: 10px 0;">$<?php echo number_format($studio['price_per_hour'], 2); ?>/hour</p>
                <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 20px; height: 40px; overflow: hidden;">
                    <?php echo htmlspecialchars($studio['description']); ?>
                </p>
                <div style="display: flex; gap: 10px;">
                    <a href="studio_details.php?id=<?php echo $studio['id']; ?>" class="btn btn-outline" style="flex: 1;">Details</a>
                    <a href="booking.php?studio=<?php echo $studio['id']; ?>" class="btn btn-primary" style="flex: 1;">Book</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'config/footer.php'; ?>
