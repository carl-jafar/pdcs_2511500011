<?php
require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
    case 'GET':
        // Ambil data mahasiswa berdasarkan ID
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j ON m.jurusan_id = j.id WHERE m.id = $id";
            $r = mysqli_query($koneksi, $q);

            if (!$r) sendResponse(false, "query gagal: " . mysqli_error($koneksi), null, 500);

            $data = mysqli_fetch_assoc($r);
            if (!$data) sendResponse(false, "mahasiswa tidak ditemukan", null, 404);

            sendResponse(true, "berhasil", $data, 200);
        }

        // Cari mahasiswa berdasarkan nama atau NIM
        if (isset($_GET['search'])) {
            $search = mysqli_real_escape_string($koneksi, trim($_GET['search']));
            $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j ON m.jurusan_id = j.id WHERE m.nama LIKE '%$search%' OR m.nim LIKE '%$search%' ORDER BY m.id DESC";
            $r = mysqli_query($koneksi, $q);

            if (!$r) sendResponse(false, "query gagal: " . mysqli_error($koneksi), null, 500);

            $data = [];
            while ($row = mysqli_fetch_assoc($r)) {
                $data[] = $row;
            }

            sendResponse(true, "berhasil", $data, 200);
        }

        // Ambil data mahasiswa dengan pagination
        if (isset($_GET['page']) && isset($_GET['limit'])) {
            $page = max(1, (int) $_GET['page']);
            $limit = max(1, (int) $_GET['limit']);
            $offset = ($page - 1) * $limit;
            $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j ON m.jurusan_id = j.id ORDER BY m.id DESC LIMIT $limit OFFSET $offset";
            $r = mysqli_query($koneksi, $q);

            if (!$r) sendResponse(false, "query gagal: " . mysqli_error($koneksi), null, 500);

            $data = [];
            while ($row = mysqli_fetch_assoc($r)) {
                $data[] = $row;
            }

            sendResponse(true, "berhasil", $data, 200);
        }

        // Ambil semua data mahasiswa
        $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j ON m.jurusan_id = j.id ORDER BY m.id DESC";
        $r = mysqli_query($koneksi, $q);

        if (!$r) sendResponse(false, "query gagal: " . mysqli_error($koneksi), null, 500);

        $data = [];
        while ($row = mysqli_fetch_assoc($r)) {
            $data[] = $row;
        }

        sendResponse(true, "berhasil", $data, 200);
        break;

    case 'POST':
        // Tambahkan data mahasiswa baru
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            sendResponse(false, "body harus berupa JSON object", null, 400);
        }

        if (!isset($input['nama'], $input['nim'], $input['jurusan_id'])) {
            sendResponse(false, "field nama, nim, dan jurusan_id wajib", null, 400);
        }

        $nama = trim((string) $input['nama']);
        $nim = trim((string) $input['nim']);
        $jurusan_id = (int) $input['jurusan_id'];

        if ($nama === '' || $nim === '' || $jurusan_id <= 0) {
            sendResponse(false, "nama, nim, dan jurusan_id tidak valid", null, 400);
        }

        // Pastikan jurusan yang dipilih tersedia
        $jurusanExists = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id = $jurusan_id");
        if (mysqli_num_rows($jurusanExists) === 0) {
            sendResponse(false, "jurusan_id tidak ditemukan", null, 404);
        }

        // Pastikan NIM belum digunakan mahasiswa lain
        $nimCheck = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim = '" . mysqli_real_escape_string($koneksi, $nim) . "'");
        if (mysqli_num_rows($nimCheck) > 0) {
            sendResponse(false, "nim sudah terdaftar", null, 400);
        }

        // Simpan data mahasiswa ke database
        $q = "INSERT INTO mahasiswa (nama, nim, jurusan_id, created_at) VALUES ('" . mysqli_real_escape_string($koneksi, $nama) . "', '" . mysqli_real_escape_string($koneksi, $nim) . "', $jurusan_id, CURDATE())";
        if (mysqli_query($koneksi, $q)) {
            sendResponse(true, "data tersimpan", ["id" => mysqli_insert_id($koneksi)], 201);
        }

        sendResponse(false, mysqli_error($koneksi), null, 500);
        break;

    default:
        // Tolak method HTTP yang belum didukung
        sendResponse(false, "method tidak diijinkan", null, 405);
        break;
}
