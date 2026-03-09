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
      <p id="message">Memverifikasi akses Telegram...</p>
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

      alert(initData);

      try {
      // Kirim initData ke server
      const response = await fetch('{{ secure_url(config("app.url")) }}/telegram/auth', {
      method: 'POST',
      headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ initData })
      });

      const result = await response.json();

      if (result.success) {
      // Autentikasi sukses, redirect ke dashboard mini app
      window.location.href = '{{ route("telegram.dashboard") }}';
      } else {
      // Gagal, redirect ke halaman utama dengan pesan (opsional)
      document.getElementById("spinner").style.display = "none";
      document.getElementById("message").innerHTML = '<span class="error">' + result.message + '</span>';
      }
      } catch (error) {
      alert(error.message);
      document.getElementById("spinner").style.display = "none";
      document.getElementById("message").innerHTML = '<span class="error">Gagal terhubung ke server</span><br><a href="{{ config("app.url") }}" style="color: white;">Kembali ke beranda</a>';
      console.error('Error:', error);
      }
      })();
      </script>
      </body>
      </html>