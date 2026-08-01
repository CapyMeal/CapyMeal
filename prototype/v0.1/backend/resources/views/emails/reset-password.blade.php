<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperá tu contraseña – CapyMeal</title>
  <style>
    body { margin: 0; padding: 0; background: #F5F5F7; font-family: Arial, sans-serif; color: #3F3F46; }
    .wrapper { max-width: 520px; margin: 40px auto; background: #FFFFFF; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.07); }
    .header { background: linear-gradient(135deg, #F4B6D7 0%, #DCCCF4 100%); padding: 32px 40px 24px; text-align: center; }
    .header img { width: 80px; margin-bottom: 10px; }
    .header__title { font-size: 26px; font-weight: 700; color: #3F3F46; margin: 0; }
    .header__tagline { font-size: 13px; color: #6B6B72; margin: 6px 0 0; }
    .body { padding: 32px 40px; }
    .body p { font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
    .btn { display: block; margin: 28px auto; padding: 14px 32px; background: #F4B6D7; color: #3F3F46; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 999px; text-align: center; width: fit-content; }
    .note { font-size: 13px; color: #6B6B72; background: #FDF6FB; border: 1px solid #F4B6D7; border-radius: 10px; padding: 12px 16px; margin-top: 8px; }
    .footer { padding: 20px 40px; text-align: center; font-size: 12px; color: #B0AABF; border-top: 1px solid #EDE9F2; }
    .url-fallback { font-size: 12px; color: #B0AABF; word-break: break-all; margin-top: 20px; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <img src="https://capymeal.onrender.com/images/Chef.png" alt="Capi el chef">
      <div class="header__title">CapyMeal</div>
      <div class="header__tagline">Las comidas pasan. Los recuerdos quedan.</div>
    </div>
    <div class="body">
      <p>¡Hola, <strong>{{ $userName }}</strong>! 🌸</p>
      <p>Nos llegó una solicitud para restablecer la contraseña de tu cuenta. ¡No te preocupes, le pasa a cualquiera!</p>
      <p>Hacé clic en el botón de abajo y en menos de un minuto vas a poder volver a registrar tus comidas:</p>

      <a href="{{ $url }}" class="btn">✨ Restablecer mi contraseña</a>

      <p class="note">⏰ Este enlace es válido por <strong>60 minutos</strong>. Si se venció, podés pedir uno nuevo desde la app.</p>
      <p>Si no fuiste vos quien lo pidió, ignorá este email. Tu contraseña no va a cambiar.</p>

      <p class="url-fallback">¿El botón no funciona? Copiá este enlace en tu navegador:<br>{{ $url }}</p>
    </div>
    <div class="footer">
      Con cariño, el equipo de CapyMeal 🐾<br>
      <span style="font-size:11px;">Este es un mensaje automático, por favor no respondas este email.</span>
    </div>
  </div>
</body>
</html>
