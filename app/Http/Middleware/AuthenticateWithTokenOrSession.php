<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class AuthenticateWithTokenOrSession
{
  protected $auth;

  public function __construct(AuthFactory $auth) {
    $this->auth = $auth;
  }

  public function handle($request, Closure $next) {
    // Coba autentikasi via token (Sanctum)
    $token = $request->bearerToken();

    if (!$token && $request->has("token")) {
      \Log::debug("Using token");
      $token = $request->get("token");
      $request->headers->set("Authorization", "Bearer ".$token);
    }

    if ($request->bearerToken()) {
      \Log::debug("Using bearer token");
      if ($this->auth->guard('sanctum')->check()) {
        $this->auth->shouldUse('sanctum');
        return $next($request);
      }
    }

    // Jika tidak ada token atau token invalid, coba session (web)
    if ($this->auth->guard('web')->check()) {
      \Log::debug("Check session web");
      $this->auth->shouldUse('web');
      return $next($request);
    }

    \Log::error("User unauthenticated");
    throw new AuthenticationException('Unauthenticated.');
  }
}