<?php

return [

    // Auth
    'login_failed' => 'El email o la contraseña son incorrectos.',
    'password_incorrect' => 'La contraseña no es correcta.',
    'session_expired' => 'Tu sesión expiró. Volvé a iniciar sesión.',
    'social_login_expired' => 'Este enlace ya no es válido. Iniciá sesión de nuevo.',

    // Verificación de email
    'email_already_verified' => 'Tu email ya está verificado.',
    'email_send_failed' => 'No pudimos enviar el email en este momento. Intentá de nuevo en un rato.',
    'email_verification_resent' => 'Te mandamos un nuevo enlace. Revisá tu bandeja de entrada (y spam).',

    // Recuperación de contraseña
    'password_reset_throttled' => 'Ya enviamos un enlace hace poco. Esperá un minuto antes de volver a intentarlo.',
    'password_reset_link_sent' => 'Si existe una cuenta con ese email, vas a recibir un enlace en los próximos minutos.',

    // Registros del diario
    'meal_entry_duplicate_date' => 'Ya existe un registro para esa fecha.',
    'meal_entry_empty' => 'Tenés que completar al menos una comida o recuerdo antes de guardar.',

    // Emails transaccionales
    'mail_greeting' => '¡Hola! 🍂',
    'mail_salutation' => "Con cariño, 🤎\nEl equipo de CapyMeal",
    'mail_link_expire' => 'Este enlace vence en :minutes minutos.',
    'mail_reset_subject' => 'Recuperá tu contraseña de CapyMeal 🍂',
    'mail_reset_line' => 'Capi recibió un pedido para restablecer la contraseña de tu cuenta de CapyMeal.',
    'mail_reset_action' => 'Elegir nueva contraseña',
    'mail_reset_ignore' => 'Si vos no pediste esto, podés ignorar este email tranquilamente. Tu contraseña sigue siendo la misma.',
    'mail_verify_subject' => 'Confirmá tu email de CapyMeal 🍂',
    'mail_verify_line' => 'Capi quiere confirmar que este email es realmente tuyo antes de guardar tu diario de comidas.',
    'mail_verify_action' => 'Confirmar mi email',
    'mail_verify_ignore' => 'Si vos no creaste esta cuenta, podés ignorar este email tranquilamente.',

    // Export a PDF
    'pdf_tagline' => 'Las comidas pasan. Los recuerdos quedan.',
    'pdf_all_records' => 'Todos los registros',
    'pdf_today' => 'hoy',
    'pdf_breakfast' => 'Desayuno',
    'pdf_lunch' => 'Almuerzo',
    'pdf_snack' => 'Merienda',
    'pdf_dinner' => 'Cena',
    'pdf_not_recorded' => 'No registrado',
    'pdf_notes_label' => '📝 Recuerdo del día',
    'pdf_no_records' => 'No hay registros para ese rango.',
    'pdf_footer' => 'CapyMeal · Generado el :date',

];
