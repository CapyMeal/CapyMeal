<?php

return [

    // Auth
    'login_failed' => 'The email or password is incorrect.',
    'password_incorrect' => 'The password is incorrect.',
    'session_expired' => 'Your session expired. Please log in again.',
    'social_login_expired' => 'This link is no longer valid. Please log in again.',

    // Email verification
    'email_already_verified' => 'Your email is already verified.',
    'email_send_failed' => "We couldn't send the email right now. Try again in a bit.",
    'email_verification_resent' => 'We sent you a new link. Check your inbox (and spam folder).',

    // Password reset
    'password_reset_throttled' => 'We already sent a link a moment ago. Wait a minute before trying again.',
    'password_reset_link_sent' => "If there's an account with that email, you'll get a link in the next few minutes.",
    'password_reset_success' => 'Your password was updated.',

    // Diary entries
    'meal_entry_duplicate_date' => 'There is already an entry for that date.',
    'meal_entry_empty' => 'Add at least one meal or a memory before saving.',

    // Transactional emails
    'mail_greeting' => 'Hi there! 🍂',
    'mail_salutation' => "With love, 🤎\nThe CapyMeal team",
    'mail_link_expire' => 'This link expires in :minutes minutes.',
    'mail_reset_subject' => 'Reset your CapyMeal password 🍂',
    'mail_reset_line' => 'Capi got a request to reset the password for your CapyMeal account.',
    'mail_reset_action' => 'Choose a new password',
    'mail_reset_ignore' => "If you didn't request this, feel free to ignore this email. Your password stays the same.",
    'mail_verify_subject' => 'Confirm your CapyMeal email 🍂',
    'mail_verify_line' => 'Capi wants to make sure this email is really yours before saving your meal diary.',
    'mail_verify_action' => 'Confirm my email',
    'mail_verify_ignore' => "If you didn't create this account, feel free to ignore this email.",

    // PDF export
    'pdf_tagline' => 'Meals come and go. Memories stay.',
    'pdf_all_records' => 'All entries',
    'pdf_today' => 'today',
    'pdf_breakfast' => 'Breakfast',
    'pdf_lunch' => 'Lunch',
    'pdf_snack' => 'Snack',
    'pdf_dinner' => 'Dinner',
    'pdf_not_recorded' => 'Not recorded',
    'pdf_notes_label' => "📝 Today's memory",
    'pdf_no_records' => 'There are no entries for that range.',
    'pdf_footer' => 'CapyMeal · Generated on :date',

    // Two-factor authentication
    'two_factor_setup_not_started' => "You haven't generated a new QR code yet. Start over.",
    'two_factor_invalid_code' => "That code isn't valid. Try again.",
    'two_factor_challenge_expired' => 'This expired or was already used. Log in again.',
    'two_factor_already_enabled' => 'Two-factor authentication is already on. Turn it off first if you want to generate a new QR code.',

];
