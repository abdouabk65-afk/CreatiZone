<?php
class SubmissionController
{
    private SubmissionModel $model;

    public function __construct()
    {
        $db          = Database::getInstance()->getConnection();
        $this->model = new SubmissionModel($db);
    }

    // ── Helpers privés ────────────────────────────────────────
    private function loadChallenges(int $challenge_id): array
    {
        $challenge_name = 'CreatiZone';
        $allChallenges  = [];
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT titre FROM challenge WHERE id_challenge = :id");
            $stmt->execute([':id' => $challenge_id]);
            $ch   = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($ch) $challenge_name = $ch['titre'];

            $stmtAll       = $db->query("SELECT id_challenge, titre FROM challenge ORDER BY id_challenge ASC");
            $allChallenges = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        return [$challenge_name, $allChallenges];
    }

    // ── Index ─────────────────────────────────────────────────
    public function index(int $challenge_id): void
    {
        $sort     = $_GET['sort'] ?? 'date';
        $page_num = max(1, (int)($_GET['p'] ?? 1));
        $perPage  = 20;
        $offset   = ($page_num - 1) * $perPage;

        if ($challenge_id > 0) {
            $submissions = $this->model->findByChallengeId($challenge_id, $sort, $perPage, $offset);
            $total       = $this->model->count($challenge_id);
        } else {
            $submissions = $this->model->findAll($sort, $perPage, $offset);
            $total       = $this->model->count();
        }

        $totalPages = max(1, (int) ceil($total / $perPage));
        $errors     = [];

        [$challenge_name, $allChallenges] = $this->loadChallenges($challenge_id);

        if (!empty($_SESSION['already_submitted'])) {
            $errors[] = $_SESSION['already_submitted'];
            unset($_SESSION['already_submitted']);
        }

        require __DIR__ . '/../view/submission.php';
    }

    // ── Store ─────────────────────────────────────────────────
    public function store(): void
    {
        $challenge_id = (int)($_POST['challenge_id'] ?? 1);
        $user_id      = (int)($_SESSION['user_id']   ?? 1);
        $description  = trim($_POST['description']   ?? '');
        $link         = trim($_POST['link']           ?? '') ?: null;
        $errors       = [];

        // ✅ Vérification CSRF
        if (empty($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['flash_error'] = "Requête invalide. Veuillez réessayer.";
            header("Location: index.php?page=submission&action=index&challenge_id=$challenge_id");
            exit;
        }

        // ✅ Déjà soumis ?
        if ($this->model->alreadySubmitted($challenge_id, $user_id)) {
            $_SESSION['submitted_challenge_' . $challenge_id] = true;
            header("Location: index.php?page=submission&action=index&challenge_id=$challenge_id");
            exit;
        }

        // ✅ Validation
        if (empty($description))         $errors[] = "La description est obligatoire.";
        if (strlen($description) > 2000) $errors[] = "Description trop longue (max 2000 caractères).";
        if ($link && !filter_var($link, FILTER_VALIDATE_URL)) $errors[] = "Le lien n'est pas valide.";

        // ✅ Upload image
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = $this->handleUpload($_FILES['image']);
            if ($image === false) $errors[] = "Image invalide (JPG, PNG, GIF, WEBP – max 5 Mo).";
        }

        // Erreurs → réafficher le formulaire
        if (!empty($errors)) {
            $sort       = 'date';
            $page_num   = 1;
            $totalPages = 1;
            $submissions = $this->model->findByChallengeId($challenge_id);
            [$challenge_name, $allChallenges] = $this->loadChallenges($challenge_id);
            require __DIR__ . '/../view/submission.php';
            return;
        }

        // ✅ Enregistrement
        $id = $this->model->create($challenge_id, $user_id, $description, $image, $link);

        if ($id) {
            // Régénérer le token CSRF après soumission réussie
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['flash'] = "Participation publiée avec succès !";
            $_SESSION['submitted_challenge_' . $challenge_id] = true;
        } else {
            $_SESSION['flash_error'] = "Erreur lors de la publication, veuillez réessayer.";
        }

        header("Location: index.php?page=submission&action=index&challenge_id=$challenge_id");
        exit;
    }

    // ── Edit ──────────────────────────────────────────────────
    public function edit(int $id): void
    {
        $submission = $this->model->findById($id);

        if (!$submission) {
            $_SESSION['flash_error'] = "Participation introuvable.";
            header("Location: index.php?page=submission&action=index&challenge_id=1");
            exit;
        }

        $challenge_id = (int)($submission['challenge_id'] ?? 1);
        $submissions  = $this->model->findByChallengeId($challenge_id);
        $sort         = 'date';
        $page_num     = 1;
        $totalPages   = 1;
        $errors       = [];

        [$challenge_name, $allChallenges] = $this->loadChallenges($challenge_id);

        require __DIR__ . '/../view/submission.php';
    }

    // ── Update ────────────────────────────────────────────────
    public function update(int $id): void
    {
        $user_id      = (int)($_SESSION['user_id']    ?? 1);
        $challenge_id = (int)($_POST['challenge_id']  ?? 1);
        $description  = trim($_POST['description']    ?? '');
        $link         = trim($_POST['link']            ?? '') ?: null;
        $errors       = [];

        // ✅ Vérification CSRF
        if (empty($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['flash_error'] = "Requête invalide. Veuillez réessayer.";
            header("Location: index.php?page=submission&action=index&challenge_id=$challenge_id");
            exit;
        }

        // ✅ Validation
        if (empty($description))         $errors[] = "La description est obligatoire.";
        if (strlen($description) > 2000) $errors[] = "Description trop longue.";
        if ($link && !filter_var($link, FILTER_VALIDATE_URL)) $errors[] = "Lien invalide.";

        // ✅ Image
        $submission = $this->model->findById($id);
        $image      = $submission['image'] ?? null;

        if (!empty($_FILES['image']['name'])) {
            $newImage = $this->handleUpload($_FILES['image']);
            if ($newImage === false) {
                $errors[] = "Image invalide.";
            } else {
                if ($image && file_exists($_SERVER['DOCUMENT_ROOT'] . '/creatizone/' . $image)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/creatizone/' . $image);
                }
                $image = $newImage;
            }
        }

        // Erreurs → réafficher le formulaire
        if (!empty($errors)) {
            $sort        = 'date';
            $page_num    = 1;
            $totalPages  = 1;
            $submissions = $this->model->findByChallengeId($challenge_id);
            [$challenge_name, $allChallenges] = $this->loadChallenges($challenge_id);
            require __DIR__ . '/../view/submission.php';
            return;
        }

        $this->model->update($id, $user_id, $description, $image, $link);

        // Régénérer le token CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['flash']      = "Participation mise à jour avec succès.";
        header("Location: index.php?page=submission&action=index&challenge_id=$challenge_id");
        exit;
    }

    // ── Delete ────────────────────────────────────────────────
    public function delete(int $id): void
    {
        $challenge_id = (int)($_POST['challenge_id'] ?? 1);
        $user_id      = (int)($_SESSION['user_id']   ?? 1);

        // ✅ Vérification CSRF
        if (empty($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['flash_error'] = "Requête invalide. Veuillez réessayer.";
            header("Location: index.php?page=submission&action=index&challenge_id=$challenge_id");
            exit;
        }

        $submission = $this->model->findById($id);

        if ($submission && !empty($submission['image'])) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/creatizone/' . $submission['image'];
            if (file_exists($path)) unlink($path);
        }

        $this->model->delete($id, $user_id);

        // Régénérer le token CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['flash']      = "Participation supprimée.";
        header("Location: index.php?page=submission&action=index&challenge_id=$challenge_id");
        exit;
    }

    // ── Upload ────────────────────────────────────────────────
    private function handleUpload(array $file): string|false
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;
        $dir     = $_SERVER['DOCUMENT_ROOT'] . '/creatizone/uploads/';

        if ($file['error'] !== UPLOAD_ERR_OK) return false;
        if ($file['size']  > $maxSize)        return false;

        // ✅ Vérification MIME réelle (pas juste l'extension)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if (!in_array($finfo->file($file['tmp_name']), $allowed)) return false;

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('sub_', true) . '.' . $ext;
        $dest     = $dir . $filename;

        return move_uploaded_file($file['tmp_name'], $dest) ? 'uploads/' . $filename : false;
    }
}