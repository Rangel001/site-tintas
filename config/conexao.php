<?php

try {

    $pdo = new PDO("sqlite:" . __DIR__ . "/../sql.db");

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    $pdo->exec("PRAGMA foreign_keys = ON");
    $pdo->exec("PRAGMA busy_timeout = 5000");

} catch (PDOException $e) {

    die("Erro na conexão: " . $e->getMessage());

}