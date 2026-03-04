<?php
// Sessions sécurisées
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$root = 'C:/xampp/htdocs/creatizone';
require_once $root . '/config.php';
require_once $root . '/model/submissionmod.php';
require_once $root . '/controller/submissioncon.php';

$page   = $_GET['page']   ?? $_POST['page']   ?? 'submission';
$action = $_GET['action'] ?? $_POST['action'] ?? 'index';

switch ($page) {
    case 'submission':
    default:
        $ctrl = new SubmissionController();
        switch ($action) {
            case 'store':
                $ctrl->store();
                break;
            case 'edit':
                $ctrl->edit((int)($_GET['id'] ?? 0));
                break;
            case 'update':
                $ctrl->update((int)($_POST['id'] ?? 0));
                break;
            case 'delete':
                $ctrl->delete((int)($_POST['id'] ?? 0));
                break;
            case 'index':
            default:
                $ctrl->index((int)($_GET['challenge_id'] ?? 1));
                break;
        }
        break;
}