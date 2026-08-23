<?php
/**
 * RALegal — SMTP config (NOT committed to git).
 * Loaded by contact.php. Keep this file private on the server.
 */
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'secure' => 'tls',            // 'tls' or 'ssl'
    'username' => 'Mdaj.secretariat@gmail.com',
    'password' => '',             // app-specific password
    'from_email' => 'Mdaj.secretariat@gmail.com',
    'from_name' => 'RALegal Site',
    'to_email'   => 'rajmi@ralegal.ch',   // firm inbox
    'to_name'    => 'Étude RALegal',
];
