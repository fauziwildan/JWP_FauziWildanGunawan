<?php
/**
 * File: todo_list.php
 * Aplikasi To-Do List sederhana dengan:
 * - Penyimpanan data dalam file JSON
 * - Fitur tambah, edit, hapus, dan tandai selesai
 * - Menggunakan Bootstrap 5 untuk struktur dan tampilan kustom
 */

// ==================== KONFIGURASI & INISIALISASI ====================
class TodoApp {
    private $tasks = [];
    private $dataFile = 'todo_data.json';

    public function __construct() {
        $this->loadTasks();
    }

    // ==================== MANAJEMEN PENYIMPANAN ====================
    private function loadTasks() {
        if (file_exists($this->dataFile)) {
            $data = file_get_contents($this->dataFile);
            $this->tasks = json_decode($data, true) ?: [];
        }
    }

    private function saveTasks() {
        $this->tasks = array_values($this->tasks);
        file_put_contents($this->dataFile, json_encode($this->tasks, JSON_PRETTY_PRINT));
    }

    // ==================== OPERASI TUGAS ====================
    public function addTask($title, $description = '') {
        if (empty(trim($title))) return false;

        $newTask = [
            'id' => 'task-' . uniqid(),
            'title' => htmlspecialchars(trim($title)),
            'description' => htmlspecialchars(trim($description)),
            'created_at' => date('Y-m-d H:i:s'),
            'completed' => false
        ];

        array_unshift($this->tasks, $newTask);
        $this->saveTasks();
        return true;
    }

    public function toggleTask($taskId) {
        foreach ($this->tasks as &$task) {
            if ($task['id'] === $taskId) {
                $task['completed'] = !$task['completed'];
                $this->saveTasks();
                return true;
            }
        }
        return false;
    }

    public function deleteTask($taskId) {
        foreach ($this->tasks as $key => $task) {
            if ($task['id'] === $taskId) {
                unset($this->tasks[$key]);
                $this->saveTasks();
                return true;
            }
        }
        return false;
    }

    public function updateTask($taskId, $newTitle, $newDescription) {
        if (empty(trim($newTitle))) return false;

        foreach ($this->tasks as &$task) {
            if ($task['id'] === $taskId) {
                $task['title'] = htmlspecialchars(trim($newTitle));
                $task['description'] = htmlspecialchars(trim($newDescription));
                $this->saveTasks();
                return true;
            }
        }
        return false;
    }

    public function getTasks() {
        return $this->tasks;
    }
}

// ==================== PROSES REQUEST ====================
$todoApp = new TodoApp();
$message = null;
$edit_id = $_GET['edit_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            if ($todoApp->addTask($title, $description)) {
                $message = ['type' => 'success', 'text' => 'Tugas berhasil ditambahkan!'];
            } else {
                $message = ['type' => 'danger', 'text' => 'Judul tugas tidak boleh kosong!'];
            }
            break;

        case 'toggle':
            $taskId = $_POST['task_id'] ?? '';
            $todoApp->toggleTask($taskId);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();

        case 'delete':
            $taskId = $_POST['task_id'] ?? '';
            if ($todoApp->deleteTask($taskId)) {
                $message = ['type' => 'success', 'text' => 'Tugas berhasil dihapus!'];
            }
            break;

        case 'update':
            $taskId = $_POST['task_id'] ?? '';
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            if ($todoApp->updateTask($taskId, $title, $description)) {
                $message = ['type' => 'success', 'text' => 'Tugas berhasil diperbarui!'];
            } else {
                $message = ['type' => 'danger', 'text' => 'Gagal memperbarui, judul tidak boleh kosong.'];
            }
            // Kosongkan edit_id setelah update berhasil
            if ($message['type'] === 'success') {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
            break;
    }
}

$tasks = $todoApp->getTasks();
$completed_count = count(array_filter($tasks, fn($task) => $task['completed']));
$total_count = count($tasks);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi To-Do List Modern</title>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS (File Terpisah) -->
    <link rel="stylesheet" href="style.css">

</head>
<body>

    <div class="container main-container">
        <div class="todo-card">
            <div class="card-header">
                <h1 class="h3 mb-0"><i class="bi bi-journal-check me-2"></i>My To-Do List</h1>
            </div>
            
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?= htmlspecialchars($message['type']) ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message['text']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Form Tambah/Edit Tugas -->
                <div class="add-task-form mb-4 pb-4 border-bottom">
                    <h2 class="h5 mb-3">Tambah Tugas Baru</h2>
                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="title" class="form-label visually-hidden">Judul Tugas</label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="Apa yang akan Anda lakukan?">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label visually-hidden">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Tambahkan deskripsi (opsional)..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg me-2"></i>Tambah
                        </button>
                    </form>
                </div>
                
                <!-- Daftar Tugas -->
                <h2 class="h5 mb-3 task-list-header">Daftar Tugas Anda</h2>
                
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <i class="bi bi-moon-stars"></i>
                        <p>Semua tugas selesai! Saatnya istirahat.</p>
                    </div>
                <?php else: ?>
                    <div class="task-list">
                        <?php foreach ($tasks as $task): ?>
                            
                            <?php if ($edit_id === $task['id']): ?>
                                <!-- Form Edit -->
                                <div class="edit-form">
                                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id']) ?>">
                                        <div class="mb-2">
                                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($task['title']) ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($task['description']) ?></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Simpan</button>
                                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-sm btn-secondary">Batal</a>
                                    </form>
                                </div>
                            <?php else: ?>
                                <!-- Tampilan Tugas Normal -->
                                <div class="task-item <?= $task['completed'] ? 'completed' : '' ?>">
                                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="d-inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id']) ?>">
                                        <input class="form-check-input" type="checkbox" id="task-<?= htmlspecialchars($task['id']) ?>" <?= $task['completed'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                    </form>

                                    <div class="task-content">
                                        <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                        <?php if (!empty($task['description'])): ?>
                                            <div class="task-description"><?= nl2br(htmlspecialchars($task['description'])) ?></div>
                                        <?php endif; ?>
                                        <div class="task-meta mt-2">
                                            <i class="bi bi-calendar3 me-1"></i> Dibuat: <?= date('j F Y', strtotime($task['created_at'])) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <a href="?edit_id=<?= htmlspecialchars($task['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus tugas ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-footer text-center">
                <span class="text-muted">
                    <strong><?= $completed_count ?></strong> dari <strong><?= $total_count ?></strong> tugas selesai
                </span>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
