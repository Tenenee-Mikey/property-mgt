<?php
// FILE: includes/audit.php - Fixed (no session start needed)
// Audit functions don't need to start session themselves

function logAction($pdo, $userId, $username, $action, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    if ($details && strlen($details) > 500) {
        $details = substr($details, 0, 500) . '...';
    }
    
    $stmt = $pdo->prepare('
        INSERT INTO audit_logs (user_id, username, action, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    
    return $stmt->execute([$userId, $username, $action, $details, $ip, $userAgent]);
}

function getAuditLogs($pdo, $limit = 100, $userId = null) {
    if ($userId) {
        $stmt = $pdo->prepare('
            SELECT * FROM audit_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ');
        $stmt->execute([$userId, $limit]);
    } else {
        $stmt = $pdo->prepare('
            SELECT * FROM audit_logs 
            ORDER BY created_at DESC 
            LIMIT ?
        ');
        $stmt->execute([$limit]);
    }
    return $stmt->fetchAll();
}

function getUserAuditLogs($pdo, $userId, $limit = 50) {
    return getAuditLogs($pdo, $limit, $userId);
}
?>