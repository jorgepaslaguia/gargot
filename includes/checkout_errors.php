<?php if (!empty($checkoutIssues)): ?>
    <div class="admin-errors">
        <ul>
            <?php foreach ($checkoutIssues as $msg): ?>
                <li><?php echo htmlspecialchars($msg); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
