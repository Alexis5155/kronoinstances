<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Notification;

class Notifications extends Controller {

    private $notifModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        $this->notifModel = new Notification();
    }

    /**
     * Historique complet avec pagination
     */
    public function index() {
        $limit = 15; 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $userId = $_SESSION['user_id'];

        $allNotifs = $this->notifModel->getForUser($userId, $limit, false, $offset); 

        $totalNotifs = $this->notifModel->count($userId, 'all'); 
        $totalPages = ceil($totalNotifs / $limit);

        $this->render('user/notifications', [
            'title'         => 'Mes Notifications',
            'notifications' => $allNotifs,
            'page'          => $page,
            'total_pages'   => $totalPages
        ]);
    }

    /**
     * Lit une notification et redirige
     */
    public function read($id) {
        $userId = $_SESSION['user_id'];
        
        // On récupère le lien avant de marquer comme lu
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT link FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $notif = $stmt->fetch();

        if ($notif) {
            $this->notifModel->markAsRead($id, $userId);
            
            if (!empty($notif['link'])) {
                $target = (strpos($notif['link'], 'http') === 0) ? $notif['link'] : URLROOT . '/' . $notif['link'];
                header('Location: ' . $target);
                exit;
            }
        }
        $this->redirect('notifications');
    }

    public function markAllRead() {
        $this->notifModel->markAllRead($_SESSION['user_id']);
        $referer = $_SERVER['HTTP_REFERER'] ?? URLROOT . '/dashboard';
        header('Location: ' . $referer);
        exit;
    }

    public function check($id) {
        $this->notifModel->markAsRead($id, $_SESSION['user_id']);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public function unread($id) {
        $this->notifModel->markAsUnread($id, $_SESSION['user_id']);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public function delete($id) {
        $this->notifModel->delete($id, $_SESSION['user_id']);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * Point d'entrée de l'API pour la vérification AJAX des notifications
     */
    public function apiCheck() {
        $userId = $_SESSION['user_id'];
        $unreadCount = $this->notifModel->count($userId, 'unread');
        
        // Récupérer les 5 dernières notifications pour reconstruire la liste
        $recentNotifs = $this->notifModel->getForUser($userId, 5, true);
        $latestNotif = !empty($recentNotifs) ? $recentNotifs[0] : null;

        // Générer le HTML de la liste
        $htmlList = '';
        if(empty($recentNotifs)) {
            $htmlList = '
                <div class="text-center py-3">
                    <p class="small text-muted mb-0">Aucune nouvelle notification</p>
                </div>';
        } else {
            foreach($recentNotifs as $notif) {
                $type = htmlspecialchars($notif['type'] ?? 'info');
                $message = htmlspecialchars($notif['message']);
                $date = date('d/m H:i', strtotime($notif['created_at']));
                $unreadClass = !$notif['is_read'] ? 'h-notif-unread' : '';
                $url = URLROOT . '/notifications/read/' . $notif['id'];

                $htmlList .= "
                    <a href=\"{$url}\" class=\"h-notif-item {$unreadClass}\">
                        <div class=\"d-flex align-items-start\">
                            <i class=\"bi bi-info-circle-fill text-{$type} me-2 mt-1\"></i>
                            <div>
                                <div class=\"small fw-bold mb-0 text-dark\">{$message}</div>
                                <div class=\"notif-time opacity-75\">{$date}</div>
                            </div>
                        </div>
                    </a>
                ";
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'unreadCount' => (int)$unreadCount,
            'latestNotif' => $latestNotif,
            'htmlList'    => $htmlList
        ]);
        exit;
    }

    /**
     * API - Marquer toutes les notifications comme lues (via AJAX)
     */
    public function apiMarkAllRead() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Non connecté']);
            exit;
        }

        $this->notifModel->markAllRead($_SESSION['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

}
