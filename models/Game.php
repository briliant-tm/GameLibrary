<?php
class Game {
    private $conn;
    private $table = "games";

    public $id;
    public $user_id;
    public $title;
    public $genre;
    public $platform;
    public $cover_image;
    public $notes;

    public function __construct($db) {
        $this->conn = $db;
    }

    // READ — semua game milik user, support filter & search
    public function read($genre = '', $platform = '', $search = '') {
        $sql    = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$this->user_id];

        if ($genre)    { $sql .= " AND genre = ?";        $params[] = $genre; }
        if ($platform) { $sql .= " AND platform = ?";     $params[] = $platform; }
        if ($search)   { $sql .= " AND title LIKE ?";     $params[] = "%{$search}%"; }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // READ ONE — ambil satu game berdasarkan id + user_id (ownership check)
    public function readOne() {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$this->id, $this->user_id]);
        return $stmt->fetch();
    }

    // GET CATEGORIES — daftar genre & platform unik milik user
    public function getCategories() {
        $genres = $this->conn->prepare(
            "SELECT DISTINCT genre FROM {$this->table} WHERE user_id = ? ORDER BY genre"
        );
        $genres->execute([$this->user_id]);

        $platforms = $this->conn->prepare(
            "SELECT DISTINCT platform FROM {$this->table} WHERE user_id = ? ORDER BY platform"
        );
        $platforms->execute([$this->user_id]);

        return [
            "genres"    => array_column($genres->fetchAll(), "genre"),
            "platforms" => array_column($platforms->fetchAll(), "platform"),
        ];
    }

    // CREATE
    public function create() {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (user_id, title, genre, platform, cover_image, notes)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $this->user_id,
            $this->title,
            $this->genre,
            $this->platform,
            $this->cover_image,
            $this->notes,
        ]);
    }

    // UPDATE
    public function update() {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET title = ?, genre = ?, platform = ?, cover_image = ?, notes = ?
             WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([
            $this->title,
            $this->genre,
            $this->platform,
            $this->cover_image,
            $this->notes,
            $this->id,
            $this->user_id,
        ]);
    }

    // DELETE
    public function delete() {
        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$this->id, $this->user_id]);
    }

    // HELPER — upload cover, kembalikan path relatif atau false
    public static function handleUpload($file) {
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed_mime)) return false;
        if ($file['size'] > 2 * 1024 * 1024)         return false;

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('cover_') . '.' . $ext;
        $dir      = '../assets/uploads/covers/';
        $dest     = $dir . $filename;

        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'assets/uploads/covers/' . $filename;
        }
        return false;
    }

    // HELPER — hapus file cover dari disk
    public static function deleteCover($path) {
        if ($path && file_exists('../' . $path)) {
            unlink('../' . $path);
        }
    }
}
?>
