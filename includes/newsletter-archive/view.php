<?php

include '../../../wp-load.php';

$email_id = (int) $_GET['email_id'] ?? 0;
if (empty($email_id)) {
    die('Wrong email ID');
}

$email = $wpdb->get_row($wpdb->prepare("select id, subject, message from " . NEWSLETTER_EMAILS_TABLE . " where private=0 and id=%d and type<>'followup' and status='sent'", $email_id));

if (empty($email))
    die('Email not found');


// Force the UTF-8 charset
header('Content-Type: text/html;charset=UTF-8');
$message = do_shortcode(NewsletterArchive::$instance->replace($email->message), true);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $message;
