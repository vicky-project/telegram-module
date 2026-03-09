<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login dengan Telegram</title>
  <script src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('telegram.bot.username') }}" data-size="large" data-auth-url="" data-request-access="write" data-onauth="onTelegramAuth(user)"></script>
  <script type="text/javascript">
    function onTelegramAuth(user) {
      // Kirim data user ke server via AJAX
      fetch('{{ secure_url(config("app.url")) }}/telegram/login/process', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ data: user })
      })
      .then(response => response.json())
      .then(data => {
      if (data.success) {
      window.location.href = data.redirect;
      } else {
      alert(data.error || 'Gagal login');
      }
      })
      .catch(error => {
      alert('Terjadi kesalahan');
      });
    }
  </script>
</head>
<body>
  <div style="display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column;">
    <h2>Login dengan Telegram</h2>
    <div id="telegram-login"></div>
    <p style="margin-top: 20px;">
      Klik tombol di atas untuk melanjutkan.
    </p>
  </div>
</body>
</html>