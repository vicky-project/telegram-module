<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Mini App</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 2rem; }
        .container { max-width: 500px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Data Pengguna Telegram</h5>
            </div>
            <div class="card-body" id="user-info">
                <p class="text-muted">Memuat...</p>
            </div>
            <div class="card-footer text-end">
                <button class="btn btn-success" id="send-data">Kirim Data ke Bot</button>
            </div>
        </div>
    </div>
    
    <!-- Toast container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto">Notifikasi</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body"></div>
  </div>
</div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Telegram Web App SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js?59"></script>
    <script>
      function showToast(message, type = 'success') {
        const toastEl = document.getElementById('liveToast');
        const toastBody = toastEl.querySelector('.toast-body');
        toastBody.textContent = message;

        // Reset kelas warna
        toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');
        if (type === 'success') {
          toastEl.classList.add('bg-success', 'text-white');
        } else {
          toastEl.classList.add('bg-danger', 'text-white');
        }

        const toast = new bootstrap.Toast(toastEl);
        toast.show();
      }
        
        (function() {
            // Inisialisasi Telegram WebApp
            const tg = window.Telegram.WebApp;
            tg.expand(); // Memperluas ke layar penuh

            // Ambil data user dari initDataUnsafe (client-side)
            const user = tg.initDataUnsafe?.user;

            if (user) {
                document.getElementById('user-info').innerHTML = `
                    <p><strong>ID:</strong> ${user.id}</p>
                    <p><strong>Nama:</strong> ${user.first_name} ${user.last_name || ''}</p>
                    <p><strong>Username:</strong> ${user.username || '-'}</p>
                    <p><strong>Bahasa:</strong> ${user.language_code}</p>
                `;
            } else {
                document.getElementById('user-info').innerHTML = '<p class="text-danger">Tidak dapat mengambil data pengguna.</p>';
            }

            // Contoh mengirim data ke server Laravel
            document.getElementById('send-data').addEventListener('click', function() {
                const data = {
                    initData: tg.initData,
                    user_id: user?.id,
                    action: 'button_clicked',
                    timestamp: Date.now()
                };

                fetch('{{ secure_url(config("app.url")) }}/telegram/mini-app/data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}', // jika pakai web route
                        'X-Telegram-Init-Data': tg.initData,
                        'X-Telegram-User-Id': user?.id
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                console.log(result)
                    tg.showAlert('Data terkirim!');
                })
                .catch(error => {
                console.log(error)
                    tg.showAlert('Gagal mengirim data.');
                });
            });

            // Beri tahu Telegram bahwa halaman sudah siap
            tg.ready();
        })();
    </script>
</body>
</html>