@extends('coreui::layouts.mini-app')

@section('content')
<!-- Logo Lingkaran -->
<div class="app-logo d-flex justify-content-center align-items-center text-center p-4">
  <img src="{{ config('telegram.logo_url') }}" alt="Logo Aplikasi" class="img-fluid rounded-circle" style="width: 100px; height: 100px;">
</div>

<!-- Nama Aplikasi -->
<div class="app-name h4 fw-bold text-center">
  {{ config('app.name') }} App
</div>

<!-- Deskripsi -->
<div class="app-description text-center pb-4">
  <small>
    Satu aplikasi untuk semua fitur tersedia.
  </small>
</div>

<!-- Menu Utama -->
<div class="container text-center mt-4 p-3">
  <div class="row">
    @hook('main-apps')
  </div>
</div>
<div class="row mt-4 py-3 fixed-bottom">
  <div class="col-12">
    <div class="d-inline gap-2">
      @hook('main-footer')
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener("DOMContentLoaded", function() {
  const initData = window.Telegram?.WebApp?.initData || @json(request()->get("initData", ""));
  if(!initData) return;

  let token = localStorage.getItem("telegram_token") || '{{ request()->get("token") }}';
  if(!token) return;

  const menus = document.querySelectorAll('.menu-item');
  menus.forEach(function(menu) {
  const urlObj = new URL(menu.href, window.location.origin);
  urlObj.searchParams.set("initData", initData);
  urlObj.searchParams.set("token", token);
  menu.href = urlObj.toString();
  });
  });
</script>
@endpush