@extends('telegram::layouts.app')

@section('title', 'Telegram - ' . ($telegramUser->first_name ?? ''))

@section('content')
<div class="telegram-header">
  <a href="{{ config('telegram.back_url') ?? url()->previous() }}" class="back-link">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
  <h2 class="page-title">Profil</h2>
</div>

<div class="telegram-card">
  <div class="text-center mb-4">
    @if($telegramUser->photo_url)
    <a href="https://t.me/{{ $telegramUser->username }}" target="_blank" title="Buka di Telegram">
      <img src="{{ $telegramUser->photo_url }}" alt="{{ $telegramUser->first_name }}" class="telegram-avatar mb-3">
    </a>
    @else
    <div class="telegram-avatar d-inline-flex align-items-center justify-content-center bg-light mb-3" style="font-size: 2rem; color: var(--tg-blue);">
      <i class="bi bi-person-circle"></i>
    </div>
    @endif
    <h3 class="fw-bold mb-1">{{ $telegramUser->first_name }} {{ $telegramUser->last_name }}</h3>
    @if($telegramUser->username)
    <p class="text-secondary-telegram mb-2">
      @ {{ $telegramUser->username }}
    </p>
    <a href="https://t.me/{{ $telegramUser->username }}" target="_blank" class="telegram-btn mb-3">
      <i class="bi bi-send"></i> Buka di Telegram
    </a>
    @endif
  </div>

  <hr>
  <h5 class="fw-bold">Informasi Akun</h5>
  <ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between">
      <span class="text-secondary-telegram">ID Telegram</span>
      <span>{{ $telegramUser->telegram_id }}</span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
      <span class="text-secondary-telegram">Bahasa</span>
      <span>{{ $telegramUser->language_code ?? 'Tidak diketahui' }}</span>
    </li>
  </ul>

  @if(!empty($telegramUser->data))
  @php
  $data = $telegramUser->data;
  // Ambil bagian prayer & weather
  $prayer = $data['prayer'] ?? null;
  $weather = $data['weather'] ?? null;
  @endphp

  @if($prayer)
  <hr>
  <h5 class="fw-bold"><i class="bi bi-clock me-2"></i>Pengaturan Sholat</h5>
  <div class="mb-2">
    <span class="badge bg-primary">{{ $prayer['notifications_enabled'] ? 'Notifikasi Aktif' : 'Notifikasi Mati' }}</span>
    @if($prayer['notifications_enabled'])
    <span class="badge bg-secondary ms-1">Reminder {{ $prayer['reminder_minutes'] }} menit</span>
    @endif
  </div>
  @if(!empty($prayer['default_location']))
  <p class="small text-secondary-telegram mb-2">
    <i class="bi bi-geo-alt"></i>
    Lokasi default: {{ number_format($prayer['default_location']['latitude'], 4) }}, {{ number_format($prayer['default_location']['longitude'], 4) }}
  </p>
  @endif
  @if(!empty($prayer['notifications_sent']))
  <p class="fw-bold small mb-1">
    Riwayat Notifikasi Sholat (hari terakhir):
  </p>
  <ul class="list-unstyled small">
    @foreach(array_slice($prayer['notifications_sent'], -7, 7, true) as $date => $prayers)
    <li>
      <strong>{{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}</strong>:
      {{ implode(', ', array_map(function($p) { return ucfirst($p); }, $prayers)) }}
    </li>
    @endforeach
  </ul>
  @endif
  @endif

  @if($weather)
  <hr>
  <h5 class="fw-bold"><i class="bi bi-cloud-sun me-2"></i>Pengaturan Cuaca</h5>
  <div class="mb-2">
    <span class="badge bg-success">{{ $weather['notifications_enabled'] ? 'Notifikasi Aktif' : 'Notifikasi Mati' }}</span>
  </div>
  @if(!empty($weather['default_location']))
  <p class="small text-secondary-telegram mb-2">
    <i class="bi bi-geo-alt"></i>
    Lokasi default: {{ number_format($weather['default_location']['latitude'], 4) }}, {{ number_format($weather['default_location']['longitude'], 4) }}
  </p>
  @endif
  @if(!empty($weather['notifications_sent']))
  <p class="fw-bold small mb-1">
    Notifikasi Cuaca Terkirim (hari terakhir):
  </p>
  <ul class="list-unstyled small">
    @foreach(array_slice($weather['notifications_sent'], -7, 7, true) as $date => $times)
    <li>
      <strong>{{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}</strong>:
      {{ isset($times['morning']) ? 'Pagi' : '' }}
      {{ isset($times['morning']) && isset($times['evening']) ? '&' : '' }}
      {{ isset($times['evening']) ? 'Sore' : '' }}
    </li>
    @endforeach
  </ul>
  @endif
  @endif
  @endif

  @if($activities->count() > 0)
  <hr>
  <h5 class="fw-bold">Aktivitas Terbaru</h5>
  <ul class="list-group list-group-flush">
    @foreach($activities as $activity)
    <li class="list-group-item d-flex justify-content-between align-items-center small">
      <span><i class="bi bi-journal-text me-2 text-secondary-telegram"></i> {{ $activity->description }}</span>
      <span class="text-secondary-telegram">{{ $activity->created_at->diffForHumans() }}</span>
    </li>
    @endforeach
  </ul>
  @endif
</div>

@if($telegramUser->provider)
<div class="text-center mt-3">
  <form action="{{ route('profile.social.disconnect', $telegramUser->provider->id) }}" method="POST">
    @csrf
    @method('DELETE')
    <button type="submit" class="telegram-btn-danger" onclick="return confirm('Putuskan akun Telegram ini?')">
      <i class="bi bi-unlink"></i> Putuskan Akun Telegram
    </button>
  </form>
</div>
@endif
@endsection