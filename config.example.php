<?php
// Copy to config.local.php for LOCAL development; never commit the copy.
// For the built-in server use APP_URL=http://127.0.0.1:8080 and APP_BASE_PATH=''.
return [
    'APP_ENV' => 'local',
    'APP_URL' => 'http://localhost/vitalize',
    'APP_BASE_PATH' => '/vitalize',
    'APP_KEY' => '', // Generate: php -r "echo bin2hex(random_bytes(32));"
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'vitalize',
    'DB_USER' => 'root',
    'DB_PASSWORD' => '',
    'REQUIRE_EMAIL_VERIFICATION' => '0', // Set to 1 in production.
    'MAIL_TRANSPORT' => 'resend',
    'RESEND_API_KEY' => '',
    'MAIL_FROM' => '',
    'GROQ_API_KEY' => '',
    'GROQ_MODEL' => 'openai/gpt-oss-20b',
    'UPLOADS_ENABLED' => '0',
    'CLOUDINARY_CLOUD_NAME' => '',
    'CLOUDINARY_API_KEY' => '',
    'CLOUDINARY_API_SECRET' => '',
    'PRIVACY_CONTACT' => '',
];
