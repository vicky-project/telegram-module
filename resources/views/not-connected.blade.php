<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Akun Belum Terhubung</title>
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
      .container {
      max-width: 400px;
      padding: 20px;
      }
      h1 {
      font-size: 2rem;
      margin-bottom: 20px;
      }
      p {
      margin-bottom: 20px;
      line-height: 1.6;
      }
      a {
      color: white;
      text-decoration: underline;
      }
      .btn {
      display: inline-block;
      background: white;
      color: #667eea;
      padding: 10px 30px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: bold;
      margin-top: 20px;
      }
      </style>
      </head>
      <body>
      <div class="container">
      <h1>Akun Belum Terhubung</h1>
      <p>Akun telegram anda belum terhubung dengan akun pengguna di aplikasi kami.</p>
      <p>Silakan login melalui website terlebih dahulu, lalu hubungkan Akun Telegram anda dihalaman profile.</p>
      <a href="{{ config('app.url') }}" class="btn">Ke Beranda</a>
      </div>
      </body>
      </html>