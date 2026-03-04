<?php
class SubmissionModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ── Create ────────────────────────────────────────────────
    public function create(int $challenge_id, int $user_id, string $description, ?string $image, ?string $link): int|false
    {
        $sql = "INSERT INTO submissions (challenge_id, user_id, description, image, link, created_at, updated_at)
                VALUES (:challenge_id, :user_id, :description, :image, :link, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':challenge_id', $challenge_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id',      $user_id,      PDO::PARAM_INT);
        $stmt->bindValue(':description',  $description,  PDO::PARAM_STR);
        $stmt->bindValue(':image',        $image,        PDO::PARAM_STR);
        $stmt->bindValue(':link',         $link,         PDO::PARAM_STR);
        if ($stmt->execute()) {
            return (int) $this->db->lastInsertId();
        }
        return false;
    }

    // ── Find by ID ────────────────────────────────────────────
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT *, id_submission AS id FROM submissions WHERE id_submission = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── Find by challenge ─────────────────────────────────────
    public function findByChallengeId(int $challenge_id, string $sort = 'date', int $limit = 20, int $offset = 0): array
    {
        $order = ($sort === 'votes') ? 'created_at DESC' : 'created_at DESC';
        $sql   = "SELECT *, id_submission AS id
                  FROM submissions
                  WHERE challenge_id = :challenge_id
                  ORDER BY $order
                  LIMIT :limit OFFSET :offset";
        $stmt  = $this->db->prepare($sql);
        $stmt->bindValue(':challenge_id', $challenge_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit',        $limit,        PDO::PARAM_INT);
        $stmt->bindValue(':offset',       $offset,       PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Find all ──────────────────────────────────────────────
    public function findAll(string $sort = 'date', int $limit = 20, int $offset = 0): array
    {
        $sql  = "SELECT *, id_submission AS id
                 FROM submissions
                 ORDER BY created_at DESC
                 LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Update ────────────────────────────────────────────────
    public function update(int $id, int $user_id, string $description, ?string $image, ?string $link): bool
    {
        $sql  = "UPDATE submissions
                 SET description=:description, image=:image, link=:link, updated_at=NOW()
                 WHERE id_submission=:id AND user_id=:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':image',       $image,       PDO::PARAM_STR);
        $stmt->bindValue(':link',        $link,        PDO::PARAM_STR);
        $stmt->bindValue(':id',          $id,          PDO::PARAM_INT);
        $stmt->bindValue(':user_id',     $user_id,     PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    // ── Delete ────────────────────────────────────────────────
    public function delete(int $id, int $user_id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM submissions WHERE id_submission=:id AND user_id=:user_id"
        );
        $stmt->bindValue(':id',      $id,      PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    // ── Already submitted ─────────────────────────────────────
    public function alreadySubmitted(int $challenge_id, int $user_id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM submissions
             WHERE challenge_id = :challenge_id AND user_id = :user_id"
        );
        $stmt->bindValue(':challenge_id', $challenge_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id',      $user_id,      PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Count ─────────────────────────────────────────────────
    public function count(int $challenge_id = 0): int
    {
        if ($challenge_id > 0) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM submissions WHERE challenge_id = :id"
            );
            $stmt->bindValue(':id', $challenge_id, PDO::PARAM_INT);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM submissions");
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}