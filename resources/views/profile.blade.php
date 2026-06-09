@extends('telegram::layouts.app')

@section('title', 'Telegram - ' . ($telegramUser->first_name ?? ''))

@section('content')
<div class="telegram-header">
  <a href="{{ url()->previous() }}" class="back-link">
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
  <h5 class="fw-bold">Informasi</h5>
  <ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between">
      <span class="text-secondary-telegram">ID Telegram</span>
      <span>{{ $telegramUser->telegram_id }}</span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
      <span class="text-secondary-telegram">Bahasa</span>
      <span>{{ $telegramUser->language_code ?? 'Tidak diketahui' }}</span>
    </li>
    @if(!empty($telegramUser->data))
    <li class="list-group-item">
      <span class="text-secondary-telegram">Data Tambahan</span>
      <pre class="mt-2 bg-light p-2 rounded small">{{ json_encode($telegramUser->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </li>
    @endif
  </ul>

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

{{-- Tombol Putuskan Koneksi --}}
@if($telegramUser->provider)
<div class="text-center mt-3">
  <form action="{{ route('profile.social.disconnect', $telegramUser->provider->id) }}" method="POST">
    @csrf
    @method('DELETE')
    <button type="submit" class="telegram-btn telegram-btn-outline" onclick="return confirm('Putuskan akun Telegram ini?')">
      <i class="bi bi-unlink"></i> Putuskan Akun Telegram
    </button>
  </form>
</div>
@endif
@endsection