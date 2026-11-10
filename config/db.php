<?php
$host = "localhost";
$user = "root";
$pass = "";        // isi password MySQL root jika ada
$db   = "simlab";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
mysqli_set_charset($conn, 'utf8mb4');

/* ================================
   FUNGSI HELPERS (AMAN, CHECK EXIST)
================================ */
if (!function_exists('deleteAlat')) {
    function deleteAlat($id) {
        global $conn;
        $id = intval($id);
        $sql = "DELETE FROM alat_laboratorium WHERE id = $id";
        return mysqli_query($conn, $sql);
    }
}
?>
