<?php
/**
 * AES encryption / decryption helpers.
 * Uses AES-256-CBC with key and IV from config.json.
 */

if (!defined('AES_KEY') || !defined('AES_IV')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Encrypt a plain-text string using AES-256-CBC.
 */
function aes_encrypt(string $plainText): string {
    $cipher = 'aes-256-cbc';
    $key    = AES_KEY;
    // Ensure IV is exactly 16 bytes
    $iv = substr(str_pad(AES_IV, 16, "\0"), 0, 16);

    $encrypted = openssl_encrypt($plainText, $cipher, $key, 0, $iv);

    if ($encrypted === false) {
        throw new RuntimeException('AES encryption failed.');
    }

    return $encrypted;
}

/**
 * Decrypt an AES-256-CBC encrypted string back to plain text.
 */
function aes_decrypt(string $encryptedText): string {
    $cipher = 'aes-256-cbc';
    $key    = AES_KEY;
    $iv     = substr(str_pad(AES_IV, 16, "\0"), 0, 16);

    $decrypted = openssl_decrypt($encryptedText, $cipher, $key, 0, $iv);

    if ($decrypted === false) {
        throw new RuntimeException('AES decryption failed.');
    }

    return $decrypted;
}
