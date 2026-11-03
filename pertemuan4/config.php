<?php

$koneksi = mysqli_connect(
    "localhost",
    "root",
    "",
    "akaemik_db"
);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}