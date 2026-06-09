<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Telegram')</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
:root {
    --tg-blue: #2AABEE;
    --tg-blue-hover: #1c93d4;
    --tg-bg: #f5f5f5;
    --tg-card: #ffffff;
    --tg-text: #000000;
    --tg-text-secondary: #707579;
  }
    body {
      background-color: var(--tg-bg);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: var(--tg-text);
    }
    .telegram-container {
      max-width: 600px;
      margin: 0 auto;
    }
    .telegram-header {
      background-color: var(--tg-card);
      padding: 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      /* title ke kanan */
      border-bottom: 1px solid #e0e0e0;
    }
    .telegram-header .back-link {
      color: var(--tg-blue);
      text-decoration: none;
      font-weight: 500;
      margin-right: 1rem;
      font-size: 0.9rem;
    }
    .telegram-header .back-link:hover {
      color: var(--tg-blue-hover);
    }
    .telegram-header .page-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--tg-text);
      margin: 0;
    }
    .telegram-card {
      background-color: var(--tg-card);
      border-radius: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      padding: 1.5rem;
      margin-bottom: 1rem;
    }
    .telegram-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--tg-blue);
    }
    .telegram-btn {
      background-color: var(--tg-blue);
      color: white;
      border: none;
      padding: 0.5rem 1.5rem;
      border-radius: 20px;
      font-weight: 500;
      transition: 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }
    .telegram-btn:hover {
      background-color: var(--tg-blue-hover);
      color: white;
    }
    .telegram-btn-outline {
      background-color: transparent;
      color: var(--tg-blue);
      border: 1px solid var(--tg-blue);
    }
    .telegram-btn-outline:hover {
      background-color: var(--tg-blue);
      color: white;
    }
    .telegram-btn-danger {
      background-color: #dc3545;
      color: white;
      border: none;
      padding: 0.5rem 1.5rem;
      border-radius: 20px;
      font-weight: 500;
      transition: 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }
    .telegram-btn-danger:hover {
      background-color: #c82333;
      color: white;
    }
    .list-group-item {
      background-color: transparent;
      border: none;
      border-bottom: 1px solid #f0f0f0;
    }
    .text-secondary-telegram {
      color: var(--tg-text-secondary);
    }
  </style>
</head>
<body>
  <div class="telegram-container py-4">
    @yield('content')
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>