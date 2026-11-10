<?php
// Start session kalau belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 |---------------------------------------------------------
 |  Halaman publik (boleh diakses tanpa login)
 |---------------------------------------------------------
*/
$currentFile  = basename($_SERVER['PHP_SELF']);
$publicPages  = ['login.php'];

if (!isset($_SESSION['user_id']) && !in_array($currentFile, $publicPages, true)) {
    header("Location: /mavis/login.php");
    exit;
}

/*
 |---------------------------------------------------------
 |  Helper user & role (ANTI REDECLARE)
 |---------------------------------------------------------
*/

if (!function_exists('currentUserId')) {
    function currentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

if (!function_exists('currentUsername')) {
    function currentUsername(): ?string
    {
        return $_SESSION['username'] ?? null;
    }
}

if (!function_exists('currentUserRole')) {
    function currentUserRole(): string
    {
        return $_SESSION['role'] ?? '';
    }
}

if (!function_exists('requireLogin')) {
    /**
     * Pastikan user sudah login.
     * (Sekarang jarang dipakai karena dicek di atas, tapi berguna kalau dipanggil manual)
     */
    function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /mavis/login.php");
            exit;
        }
    }
}

if (!function_exists('requireAdmin')) {
    /**
     * Hanya untuk admin
     */
    function requireAdmin(): void
    {
        if (strtolower(currentUserRole()) !== 'admin') {
            die("Akses ditolak. Halaman ini hanya untuk Administrator.");
        }
    }
}

if (!function_exists('canManageAlat')) {
    /**
     * Admin boleh semua, guru & siswa hanya view data alat.
     * Helper ini dipakai di modul alat.
     */
    function canManageAlat(): bool
    {
        return (strtolower(currentUserRole()) === 'admin');
    }
}

if (!function_exists('canViewAlat')) {
    function canViewAlat(): bool
    {
        $role = strtolower(currentUserRole());
        // kalau masih ada user lama dengan role 'laboran', tetap diizinkan view
        return in_array($role, ['admin', 'guru', 'siswa', 'laboran'], true);
    }
}
