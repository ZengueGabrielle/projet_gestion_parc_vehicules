<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET lu = 1 WHERE utilisateur_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_envoi DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section" id="notifications">
    <h2>Notifications</h2>
    <form method="POST">
        <button type="submit" name="mark_read" style="padding: 10px; background: #dc3545; color: #fff; border: none; border-radius: 5px; margin-bottom: 20px;">Marquer tout comme lu</button>
    </form>
    <?php if (empty($notifications)): ?>
        <p>Aucune notification.</p>
    <?php else: ?>
        <ul style="list-style: none; padding: 0;">
            <?php foreach ($notifications as $notification): ?>
                <li style="background: <?php echo $notification['lu'] ? '#f8f9fa' : '#fff'; ?>; padding: 15px; margin-bottom: 10px; border: 1px solid #dc3545; border-radius: 5px; color: #333;">
                    <strong><?php echo htmlspecialchars($notification['type_notification']); ?></strong>: 
                    <?php echo htmlspecialchars($notification['message']); ?> 
                    <br><small><?php echo date('d/m/Y H:i', strtotime($notification['date_envoi'])); ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>