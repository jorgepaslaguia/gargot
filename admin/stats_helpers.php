<?php
function fetchAll($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchOne($pdo, $sql, $params = [], $default = []) {
    $rows = fetchAll($pdo, $sql, $params);
    return $rows[0] ?? $default;
}
