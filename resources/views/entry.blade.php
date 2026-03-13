<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Memuat...</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      text-align: center;
      }
      .spinner {
      border: 4px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top: 4px solid white;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 20px auto;
      }
      @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
      }
      </style>
      <script src="https://telegram.org/js/telegram-web-app.js?59"></script>
      </head>
      <body>
      <div>
      <div class="spinner" id="spinner"></div>
      @if(session("error"))
      <p>{{ session("error") }}</p>
      @else
      <p id="message">Memverifikasi akses Telegram...</p>
      @endif
      </div>

      <script>
      (async function() {
      // Ambil initData dari Telegram WebApp
      const initData = window.Telegram?.WebApp?.initData;

      if (!initData) {
      // Jika tidak ada initData (diakses dari web biasa), redirect ke halaman utama
      window.location.href = '{{ config("app.url") }}';
      return;
      }

      fetch('{{ secure_url(config("app.url"))}}/api/telegram/auth', {
      method: "POST",
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ initData })
      }).then(res => res.json()).then(data => {
      if(data.token) {
      window.Telegram.WebApp.DeviceStorage.setItem("telegram_token", data.token, function(error, isStored) {
      if(error) {
      alert(data.error);
      window.location.href = '{{ route("telegram.not-conncted") }}';
      }

      window.location.href = "{{ route('telegram.home') }}?token="+ data.token +"&initData="+ encodeURIComponent(initData);
      });
      } else {
      window.location.href = '{{ route("telegram.not-connected") }}';
      }
      });
      })();
      </script>
      </body>
      </html>