Abdou Bk
abdou___bk_51633
En partage d'écran

Abdou Bk — 17:59
aaa ey ey
le lhak
behi taw nzid fazet voir plus
abaath
ghaithoues — 18:00
https://docs.google.com/document/d/1E4aaSUhO3NBnsnhGA4C35BAxKS-u_sjyFBQL92PhDNo/edit?usp=sharing
Google Docs
ds web
📌 1️⃣ Contexte du projet 👉 Tu dois développer une application web dynamique complète avec : PHP orienté objet uniquement MySQL Environnement : WAMP / XAMPP / EasyPHP ⚠️ Très important : ❌ Interdit d’utiliser un framework comme : Laravel Symfony CodeIgniter Donc tu dois tout coder à la m...
Image
Abdou Bk — 18:01
thb nchoufou ena
behi  abaathli msg mela
ey ey
koli ken fama haja neksa
w malak kolha tekhou page teii wtzid aliha fazet badge
khtaer ena geded html w css teihom
behi mouhem kolha ken thb thezz khedmti
mriglin aya
ey ey cbon
aychou
ghaithoues — 20:53
abdoo raw khdmtk mtbdltch hay kdima jit chnkhou fichier te3 vote nlkak khadem b  asemi mch nfsha fi base w table participant mafmch thama table submission
salhou w pushih khli nkhdm bih
ahawa fichier shih mte3 vote.php khter hchti bih w enty bdel lbeki
<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'challenge_hub'); // ✅
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); exit;
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            jsonError('Connexion BDD impossible : ' . $e->getMessage(), 500);
        }
    }
    return $pdo;
}

function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    jsonResponse(['success' => false, 'message' => $message], $code);
}

// ✅ user_id devient un INT
function sanitizeUserId(string $uid): int {
    return (int)$uid;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = '';

    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
    } elseif ($method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $body['action'] ?? '';
    } else {
        jsonError('Méthode HTTP non supportée.', 405);
    }

    match ($action) {
        'participations' => handleParticipations(),
        'classement'     => handleClassement(),
        'stats'          => handleStats(),
        'voter'          => handleVoter($body ?? []),
        default          => jsonError("Action inconnue : {$action}", 400),
    };

} catch (PDOException $e) {
    jsonError('Erreur base de données : ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    jsonError('Erreur serveur : ' . $e->getMessage(), 500);
}

// ── GET participations ────────────────────────────────────────
function handleParticipations(): void {
    $userId = sanitizeUserId($_GET['user_id'] ?? '0');
    if ($userId === 0) jsonError('user_id manquant ou invalide.');

    $db  = getDB();
    // ✅ submissions + id_submission + id_user + jointure challenge
    $sql = "
        SELECT
            s.id_submission AS id,
            s.description,
            s.link,
            s.created_at,
            c.titre         AS challenge_titre,
            u.nom,
            u.prenom,
            ROUND(AVG(v.note), 2)  AS note_moyenne,
            COUNT(v.id)            AS nb_votes,
            COALESCE(uv.note, 0)   AS user_note
        FROM submissions s
        LEFT JOIN challenge c ON c.id_challenge = s.id_challenge
        LEFT JOIN user u      ON u.id_user = s.id_user
        LEFT JOIN votes v     ON v.participation_id = s.id_submission
        LEFT JOIN votes uv    ON uv.participation_id = s.id_submission AND uv.id_user = :uid
        GROUP BY s.id_submission
... (92lignes restantes)

message.txt
8 Ko
﻿
ghaithoues
ghaith0135
<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'challenge_hub'); // ✅
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); exit;
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            jsonError('Connexion BDD impossible : ' . $e->getMessage(), 500);
        }
    }
    return $pdo;
}

function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    jsonResponse(['success' => false, 'message' => $message], $code);
}

// ✅ user_id devient un INT
function sanitizeUserId(string $uid): int {
    return (int)$uid;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = '';

    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
    } elseif ($method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $body['action'] ?? '';
    } else {
        jsonError('Méthode HTTP non supportée.', 405);
    }

    match ($action) {
        'participations' => handleParticipations(),
        'classement'     => handleClassement(),
        'stats'          => handleStats(),
        'voter'          => handleVoter($body ?? []),
        default          => jsonError("Action inconnue : {$action}", 400),
    };

} catch (PDOException $e) {
    jsonError('Erreur base de données : ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    jsonError('Erreur serveur : ' . $e->getMessage(), 500);
}

// ── GET participations ────────────────────────────────────────
function handleParticipations(): void {
    $userId = sanitizeUserId($_GET['user_id'] ?? '0');
    if ($userId === 0) jsonError('user_id manquant ou invalide.');

    $db  = getDB();
    // ✅ submissions + id_submission + id_user + jointure challenge
    $sql = "
        SELECT
            s.id_submission AS id,
            s.description,
            s.link,
            s.created_at,
            c.titre         AS challenge_titre,
            u.nom,
            u.prenom,
            ROUND(AVG(v.note), 2)  AS note_moyenne,
            COUNT(v.id)            AS nb_votes,
            COALESCE(uv.note, 0)   AS user_note
        FROM submissions s
        LEFT JOIN challenge c ON c.id_challenge = s.id_challenge
        LEFT JOIN user u      ON u.id_user = s.id_user
        LEFT JOIN votes v     ON v.participation_id = s.id_submission
        LEFT JOIN votes uv    ON uv.participation_id = s.id_submission AND uv.id_user = :uid
        GROUP BY s.id_submission
        ORDER BY s.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    jsonResponse($stmt->fetchAll());
}

// ── GET classement ────────────────────────────────────────────
function handleClassement(): void {
    $db  = getDB();
    // ✅ submissions + challenge + user
    $sql = "
        SELECT
            s.id_submission AS id,
            c.titre         AS challenge_titre,
            u.nom,
            u.prenom,
            ROUND(AVG(v.note), 2) AS note_moyenne,
            COUNT(v.id)           AS nb_votes
        FROM submissions s
        INNER JOIN votes v    ON v.participation_id = s.id_submission
        LEFT JOIN challenge c ON c.id_challenge = s.id_challenge
        LEFT JOIN user u      ON u.id_user = s.id_user
        GROUP BY s.id_submission
        HAVING nb_votes >= 1
        ORDER BY note_moyenne DESC, nb_votes DESC
        LIMIT 50
    ";
    jsonResponse($db->query($sql)->fetchAll());
}

// ── GET stats ─────────────────────────────────────────────────
function handleStats(): void {
    $db    = getDB();
    // ✅ vraies stats de challenge_hub
    $stats = $db->query("
        SELECT
            (SELECT COUNT(*) FROM user)        AS total_users,
            (SELECT COUNT(*) FROM challenge)   AS total_defis,
            (SELECT COUNT(*) FROM submissions) AS total_participations,
            (SELECT COUNT(*) FROM votes)       AS total_votes,
            (SELECT ROUND(AVG(note), 2) FROM votes) AS avg_score
    ")->fetch();
    jsonResponse($stats);
}

// ── POST voter ────────────────────────────────────────────────
function handleVoter(array $body): void {
    $participationId = filter_var($body['participation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $userId          = sanitizeUserId($body['user_id'] ?? '0'); // ✅ INT
    $note            = filter_var($body['note'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);

    if (!$participationId) jsonError('participation_id invalide.');
    if ($userId === 0)     jsonError('user_id invalide.');
    if ($note === false)   jsonError('La note doit être entre 1 et 5.');

    $db = getDB();

    // Vérifier que la submission existe
    $exists = $db->prepare("SELECT id_submission FROM submissions WHERE id_submission = ?");
    $exists->execute([$participationId]);
    if (!$exists->fetch()) jsonError('Participation introuvable.', 404);

    // Vérifier si déjà voté
    $check = $db->prepare("SELECT id FROM votes WHERE participation_id = ? AND id_user = ?");
    $check->execute([$participationId, $userId]);
    if ($check->fetch()) jsonError('Vous avez déjà voté pour cette participation.');

    // Insérer le vote ✅ id_user INT
    $insert = $db->prepare("
        INSERT INTO votes (participation_id, id_user, note)
        VALUES (?, ?, ?)
    ");
    $insert->execute([$participationId, $userId, $note]);

    // Retourner la nouvelle moyenne
    $avg = $db->prepare("
        SELECT ROUND(AVG(note), 2) AS note_moyenne, COUNT(*) AS nb_votes
        FROM votes WHERE participation_id = ?
    ");
    $avg->execute([$participationId]);
    $result = $avg->fetch();

    jsonResponse([
        'success'      => true,
        'message'      => 'Vote enregistré avec succès.',
        'note'         => $note,
        'note_moyenne' => $result['note_moyenne'],
        'nb_votes'     => $result['nb_votes'],
    ], 201);
}
