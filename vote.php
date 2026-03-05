<?php
/**
 * vote.php — Backend API du Système de Vote
 * Classe E : Système de vote des participations
 *
 * Endpoints :
 *   GET  ?action=participations&user_id=xxx  → liste des participations + note de l'utilisateur
 *   GET  ?action=classement                  → classement trié par note moyenne
 *   GET  ?action=stats                       → statistiques globales
 *   POST {action:"voter", participation_id, user_id, note}  → enregistrer un vote
 */

// ─── CONFIG BDD ──────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'challenge_hub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── HEADERS ─────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── CONNEXION PDO ────────────────────────────────────────────────────────────
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

// ─── HELPERS ─────────────────────────────────────────────────────────────────
function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    jsonResponse(['success' => false, 'message' => $message], $code);
}

function sanitizeUserId(string $uid): string {
    // Accepter uniquement alphanumérique + underscore, max 64 chars
    return substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $uid), 0, 64);
}

// ─── INITIALISATION DES TABLES (si elles n'existent pas encore) ──────────────
function initTables(): void {
    $db = getDB();

    $db->exec("
        CREATE TABLE IF NOT EXISTS participations (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            titre           VARCHAR(255)    NOT NULL,
            auteur          VARCHAR(100)    NOT NULL,
            description     TEXT,
            date_soumission DATE            NOT NULL DEFAULT (CURDATE()),
            created_at      DATETIME        DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS votes (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            participation_id  INT         NOT NULL,
            user_id           VARCHAR(64) NOT NULL,
            note              TINYINT     NOT NULL CHECK (note BETWEEN 1 AND 5),
            voted_at          DATETIME    DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (participation_id, user_id),
            FOREIGN KEY (participation_id) REFERENCES participations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // ─── DONNÉES DE DÉMO (insérées une seule fois) ───────────────────────────
    $count = (int) $db->query("SELECT COUNT(*) FROM participations")->fetchColumn();
    if ($count === 0) {
        $db->exec("
            INSERT INTO participations (titre, auteur, description, date_soumission) VALUES
            ('API REST avec authentification JWT',  'Alice Martin',  'Implémentation complète d\'une API REST sécurisée avec JWT et refresh tokens.',  '2026-02-24'),
            ('Dashboard temps réel avec WebSocket', 'Jean Dupont',   'Tableau de bord interactif avec mises à jour en temps réel via WebSocket.',       '2026-02-23'),
            ('Moteur de recherche fulltext',        'Sara M.',        'Moteur de recherche avancé avec indexation fulltext et suggestions.',              '2026-02-22'),
            ('Pipeline CI/CD automatisé',           'Marc Lefèvre',  'Chaîne CI/CD complète avec Docker, GitHub Actions et déploiement automatique.',   '2026-02-21'),
            ('Application mobile PWA',              'Léa Rousseau',  'Progressive Web App avec mode hors-ligne et notifications push.',                   '2026-02-20')
        ");
    }
}

// ─── ROUTER ──────────────────────────────────────────────────────────────────
try {
    initTables();

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

// ─── HANDLERS ────────────────────────────────────────────────────────────────

/**
 * GET ?action=participations&user_id=xxx
 * Retourne toutes les participations avec la note de l'utilisateur (0 si pas voté)
 */
function handleParticipations(): void {
    $userId = sanitizeUserId($_GET['user_id'] ?? '');
    if ($userId === '') {
        jsonError('user_id manquant ou invalide.');
    }

    $db  = getDB();
    $sql = "
        SELECT
            p.id,
            p.titre,
            p.auteur,
            p.description,
            DATE_FORMAT(p.date_soumission, '%d %b %Y') AS date_soumission,
            ROUND(AVG(v.note), 2)                       AS note_moyenne,
            COUNT(v.id)                                 AS nb_votes,
            COALESCE(uv.note, 0)                        AS user_note
        FROM participations p
        LEFT JOIN votes v  ON v.participation_id = p.id
        LEFT JOIN votes uv ON uv.participation_id = p.id AND uv.user_id = :uid
        GROUP BY p.id
        ORDER BY p.date_soumission DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);

    jsonResponse($stmt->fetchAll());
}

/**
 * GET ?action=classement
 * Retourne les participations triées par note moyenne décroissante (min. 1 vote)
 */
function handleClassement(): void {
    $db  = getDB();
    $sql = "
        SELECT
            p.id,
            p.titre,
            p.auteur,
            ROUND(AVG(v.note), 2) AS note_moyenne,
            COUNT(v.id)           AS nb_votes
        FROM participations p
        INNER JOIN votes v ON v.participation_id = p.id
        GROUP BY p.id
        HAVING nb_votes >= 1
        ORDER BY note_moyenne DESC, nb_votes DESC
        LIMIT 50
    ";

    jsonResponse($db->query($sql)->fetchAll());
}

/**
 * GET ?action=stats
 * Statistiques globales (total participations, total votes, note moyenne globale)
 */
function handleStats(): void {
    $db = getDB();

    $stats = $db->query("
        SELECT
            (SELECT COUNT(*) FROM participations)            AS total_participations,
            (SELECT COUNT(*) FROM votes)                     AS total_votes,
            (SELECT ROUND(AVG(note), 2) FROM votes)          AS avg_score
    ")->fetch();

    jsonResponse($stats);
}

/**
 * POST {action:"voter", participation_id, user_id, note}
 * Enregistre un vote (une seule fois par utilisateur par participation)
 */
function handleVoter(array $body): void {
    // ─── Validation ──────────────────────────────────────────────────────────
    $participationId = filter_var($body['participation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $userId          = sanitizeUserId($body['user_id'] ?? '');
    $note            = filter_var($body['note'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);

    if (!$participationId) jsonError('participation_id invalide.');
    if ($userId === '')    jsonError('user_id invalide.');
    if ($note === false)   jsonError('La note doit être un entier entre 1 et 5.');

    $db = getDB();

    // ─── Vérifier que la participation existe ─────────────────────────────────
    $exists = $db->prepare("SELECT id FROM participations WHERE id = ?");
    $exists->execute([$participationId]);
    if (!$exists->fetch()) {
        jsonError('Participation introuvable.', 404);
    }

    // ─── Vérifier si l'utilisateur a déjà voté ───────────────────────────────
    $check = $db->prepare("SELECT id FROM votes WHERE participation_id = ? AND user_id = ?");
    $check->execute([$participationId, $userId]);
    if ($check->fetch()) {
        jsonError('Vous avez déjà voté pour cette participation.');
    }

    // ─── Insérer le vote ──────────────────────────────────────────────────────
    $insert = $db->prepare("
        INSERT INTO votes (participation_id, user_id, note)
        VALUES (?, ?, ?)
    ");
    $insert->execute([$participationId, $userId, $note]);

    // ─── Retourner la nouvelle moyenne ───────────────────────────────────────
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