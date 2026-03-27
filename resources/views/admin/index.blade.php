@extends('coreui::layouts.admin')
@section('title', 'Telegram Users')

@use('Carbon\Carbon')

@section('content')
<div class="row">
  @foreach($tgUsers as $user)
  <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body text-center">
        @if($user->photo_url)
        <img src="{{ $user->photo_url}}" alt="{{ $user->first_name }}" class="rounded-circle mb-3" width="80" height="80" style="object-fit: cover;">
        @else
        @php
        $fullName = $user->first_name . $user->last_name ? ' '. $user->last_name : '';
        @endphp
        <img src="{{ Avatar::create($fullName)->setDimension(80, 80)->setBackground('#00580d')->toBase64() }}" class="rounded-circle mb-3" width="80" height="80" style="object-fit: cover;">
        @endif
        <h5 class="card-title mb-1">{{ $user->first_name }} {{ $user->last_name }}</h5>
        @if($user->username)
        <p class="text-muted small mb-2">
          <span>@</span>{{ $user->username }}
        </p>
        @endif
        @php
        $authDate = isset($user->data["auth_date"]) ? Carbon::parse($user->data["auth_date"])->diffForHumans() : 'Never';
        @endphp
        <span class="small text-muted">Last Used:</span> {{ $authDate }}
      </div>
      <div class="card-footer bg-transparent border-0 pt-0 pb-3">
        @if($user->username)
        <a href="https://t.me/{{ $user->username}}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
          <i class="bi bi-telegram me-1"></i> Buka di Telegram
        </a>
        @endif
      </div>
    </div>
  </div>
  @endforeach
</div>
@endsection

@push('styles')
<style>
  .card {
    transition: transform 0.2s;
  }
  .card:hover {
    transform: translateY(-4px);
  }
</style>
@endpush