<?php
function fetchAll($conexion, $sql, $types = '', $params = []) {
    $stmt = $conexion->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function fetchOne($conexion, $sql, $types = '', $params = [], $default = []) {
    $rows = fetchAll($conexion, $sql, $types, $params);
    return $rows[0] ?? $default;
}
