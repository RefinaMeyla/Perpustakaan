
<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'perpustakaan';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $user,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$pesan = '';
$jenisPesan = 'success';

// PROSES TAMBAH DAN EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? '';
    $judul = trim($_POST['judul'] ?? '');
    $penulis = trim($_POST['penulis'] ?? '');
    $tahun = $_POST['terbit'] ?? '';
    $stok = $_POST['stok'] ?? '';

    if ($judul === '' || $penulis === '' ||
        !is_numeric($tahun) || !is_numeric($stok) ||
        $stok < 0) {

        $pesan = "Semua data harus diisi dengan benar.";
        $jenisPesan = 'danger';

    } else {

        if ($id !== '') {
            // EDIT DATA
            $stmt = $conn->prepare(
                "UPDATE buku SET judul=:judul, penulis=:penulis,
                tahun=:tahun, stok=:stok WHERE id=:id"
            );

            $stmt->execute([
                ':judul' => $judul,
                ':penulis' => $penulis,
                ':tahun' => $tahun,
                ':stok' => $stok,
                ':id' => $id
            ]);

            header("Location: index.php?status=edit");
            exit();

        } else {
            // TAMBAH DATA
            $stmt = $conn->prepare(
                "INSERT INTO buku (judul, penulis, tahun, stok, created_at)
                VALUES (:judul, :penulis, :tahun, :stok, NOW())"
            );

            $stmt->execute([
                ':judul' => $judul,
                ':penulis' => $penulis,
                ':tahun' => $tahun,
                ':stok' => $stok
            ]);

            header("Location: index.php?status=success");
            exit();
        }
    }
}

// PROSES DELETE
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    if (ctype_digit((string)$id)) {
        $stmt = $conn->prepare("DELETE FROM buku WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    header("Location: index.php?status=hapus");
    exit();
}

// PESAN NOTIFIKASI
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $pesan = "Data buku berhasil ditambahkan.";
    } elseif ($_GET['status'] === 'edit') {
        $pesan = "Data buku berhasil diperbarui.";
    } elseif ($_GET['status'] === 'hapus') {
        $pesan = "Data buku berhasil dihapus.";
    }
}

// FITUR SEARCH BUKU
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $query = $conn->prepare(
        "SELECT * FROM buku
        WHERE judul LIKE :cari OR penulis LIKE :cari
        ORDER BY created_at DESC"
    );

    $query->execute([
        ':cari' => '%' . $cari . '%'
    ]);
} else {
    $query = $conn->query(
        "SELECT * FROM buku ORDER BY created_at DESC"
    );
}

$list_buku = $query->fetchAll(PDO::FETCH_ASSOC);

// AMBIL DATA UNTUK EDIT
$editBuku = null;

if (isset($_GET['edit']) && ctype_digit((string)$_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM buku WHERE id=:id");
    $stmt->execute([':id' => $_GET['edit']]);
    $editBuku = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <title>Perpustakaan</title>
</head>

<body>

    <header class="bg-primary text-white p-3">
        <h1 class="h3 m-0">Website Praktik</h1>
    </header>

    <!-- NAVBAR DENGAN FITUR SEARCH -->
    <nav class="navbar navbar-dark bg-info p-2">
        <div class="container-fluid">

            <a class="navbar-brand" href="index.php">
                Perpustakaan
            </a>

            <form action="index.php" method="GET" class="d-flex" role="search">

                <input
                    class="form-control me-2"
                    type="search"
                    name="cari"
                    placeholder="Cari judul atau penulis..."
                    value="<?= htmlspecialchars($cari) ?>"
                    aria-label="Search">

                <button class="btn btn-light" type="submit">
                    Search
                </button>

                <?php if ($cari !== ''): ?>
                    <a href="index.php" class="btn btn-outline-light ms-2">
                        Reset
                    </a>
                <?php endif; ?>

            </form>

        </div>
    </nav>

    <div class="container-fluid my-4">
        <div class="row">

            <aside class="col-md-3">
                <div class="bg-light border rounded p-3">
                    <h2>Menu</h2>

                    <ul class="list-group">
                        <li class="list-group-item">
                            <a href="dashboard.html" class="text-decoration-none text-black">
                                Dashboard
                            </a>
                        </li>

                        <li class="list-group-item">
                            <a href="index.php" class="text-decoration-none text-black">
                                Daftar Buku
                            </a>
                        </li>

                        <li class="list-group-item">
                            Form Peminjaman
                        </li>
                    </ul>
                </div>
            </aside>

            <main class="col-md-9">

                <!-- FORM TAMBAH DAN EDIT -->
                <section class="border rounded p-3">

                    <h2>
                        <?= $editBuku ? 'Edit Buku' : 'Form Tambah Buku' ?>
                    </h2>

                    <?php if ($pesan !== ''): ?>
                        <div class="alert alert-<?= $jenisPesan ?>">
                            <?= htmlspecialchars($pesan) ?>
                        </div>
                    <?php endif; ?>

                    <form action="index.php" method="POST">

                        <input type="hidden" name="id"
                            value="<?= $editBuku['id'] ?? '' ?>">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Judul Buku</label>
                                <input type="text" class="form-control"
                                    name="judul" required minlength="3"
                                    value="<?= htmlspecialchars($editBuku['judul'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Penulis</label>
                                <input type="text" class="form-control"
                                    name="penulis" required
                                    value="<?= htmlspecialchars($editBuku['penulis'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Terbit</label>
                                <input type="number" class="form-control"
                                    name="terbit" min="1900" max="2100" required
                                    value="<?= htmlspecialchars($editBuku['tahun'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok</label>
                                <input type="number" class="form-control"
                                    name="stok" min="0" required
                                    value="<?= htmlspecialchars($editBuku['stok'] ?? '') ?>">
                            </div>

                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editBuku ? 'Simpan Perubahan' : 'Tambah Buku' ?>
                        </button>

                        <?php if ($editBuku): ?>
                            <a href="index.php" class="btn btn-secondary">
                                Batal
                            </a>
                        <?php endif; ?>

                    </form>
                </section>

                <!-- TABEL DAFTAR BUKU -->
                <section class="border rounded p-3 mt-4">

                    <h2>Daftar Buku</h2>

                    <table class="table table-striped table-hover">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Tahun Terbit</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($list_buku)): ?>

                                <tr>
                                    <td colspan="6" class="text-center">
                                        <?= $cari !== ''
                                            ? 'Buku yang dicari tidak ditemukan.'
                                            : 'Tidak ada data buku.' ?>
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($list_buku as $index => $buku): ?>

                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td><?= htmlspecialchars($buku['judul']) ?></td>

                                        <td><?= htmlspecialchars($buku['penulis']) ?></td>

                                        <td><?= htmlspecialchars($buku['tahun']) ?></td>

                                        <td><?= htmlspecialchars($buku['stok']) ?></td>

                                        <td>
                                            <a href="index.php?edit=<?= $buku['id'] ?>"
                                                class="btn btn-sm btn-warning">
                                                Edit
                                            </a>

                                            <a href="index.php?hapus=<?= $buku['id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Yakin ingin menghapus buku ini?')">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>
                </section>

            </main>
        </div>
    </div>

    <footer class="bg-info text-black text-center p-2 m-4">
        <p class="mb-0">
            &copy; 2026 Website Praktik. All rights reserved.
        </p>
    </footer>

</body>
</html>
