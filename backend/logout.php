<?php
require_once 'config.php';

session_unset();
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logout berhasil']);
