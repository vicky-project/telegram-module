@extends('coreui::layouts.admin')
@section('title', 'Detail Pengguna Telegram')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <a href="{{ route('admin.telegram.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
  </a>
  <h4 class="mb-0">Detail Pengguna Telegram</h4>
  <div></div>
</div>

<div class="row">
  <!-- Informasi Utama -->
  <div class="col-lg-5 mb-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="card-title mb-0">
          <i class="bi bi-telegram me-2 text-primary"></i> Informasi Telegram
        </h5>
      </div>
      <div class="card-body text-center">
        @if($telegramUser->photo_url)
        <img src="{{ $telegramUser->photo_url }}" class="rounded-circle mb-3" width="100" height="100" alt="Profile">
        @else
        <i class="bi bi-person-circle fs-1 mb-3"></i>
        @endif
        <h5>{{ $telegramUser->first_name }} {{ $telegramUser->last_name }}</h5>
        <p class="text-muted">
          @ {{ $telegramUser->username }}
        </p>
        <p>
          <strong>Telegram ID:</strong> {{ $telegramUser->telegram_id }}
        </p>
        <p>
          <strong>Bahasa:</strong> {{ $telegramUser->language_code ?? '-' }}
        </p>
        <p>
          <strong>Bergabung:</strong> {{ $telegramUser->created_at->format('d M Y H:i') }}
        </p>
        <p>
          <strong>Terakhir update:</strong> {{ $telegramUser->updated_at->diffForHumans() }}
        </p>
        <hr>
        <div class="text-start">
          <p>
            <i class="bi bi-box-arrow-up-right me-2"></i> Terhubung dengan akun sosial?
          </p>
          @if($telegramUser->provider)
          <span class="badge bg-info">{{ $telegramUser->provider->provider_name ?? 'Social Account' }}</span>
          @else
          <span class="badge bg-secondary">Tidak terhubung</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Aktivitas Terbaru (Spatie) -->
  <div class="col-lg-7 mb-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="card-title mb-0">
          <i class="bi bi-activity me-2 text-primary"></i> Aktivitas Terbaru
        </h5>
      </div>
      <div class="card-body">
        @if($telegramUser->activities->count())
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="min-width: 140px;">Waktu</th>
                <th>Aksi</th>
                <th>Subject</th>
                <th>Detail</th>
              </tr>
            </thead>
            <tbody>
              @foreach($telegramUser->activities->take(20) as $activity)
              <tr>
                <td class="small text-nowrap">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $activity->description }}</td>
                <td>
                  @if($activity->subject)
                  @if($activity->subject instanceof \Modules\Users\Models\User)
                  {{ $activity->subject->name }}
                  @elseif($activity->subject instanceof \Modules\Telegram\Models\TelegramUser)
                  {{ $activity->subject->first_name }} {{ $activity->subject->last_name }}
                  @else
                  {{ class_basename($activity->subject) }} #{{ $activity->subject->id }}
                  @endif
                  @else
                  -
                  @endif
                </td>
                <td class="small">
                  @if($activity->properties && $activity->properties->count())
                  <pre class="mb-0 text-muted" style="font-size: 0.7rem;">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT) }}</pre>
                  @else
                  -
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <p class="text-muted">
          Belum ada aktivitas tercatat.
        </p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection