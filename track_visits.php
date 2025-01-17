<?php
function track_page_visit($db, $page_name, $user_data) {
    try {
        $stmt = $db->prepare("
            INSERT INTO page_visits 
                (page_name, user_id, ip_address, user_agent) 
            VALUES 
                (:page_name, :user_id, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            'page_name' => $page_name,
            'user_id' => $user_data ? $user_data['user_id'] : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
        
    } catch (PDOException $e) {
        error_log("Error tracking page visit: " . $e->getMessage());
    }
}
?>