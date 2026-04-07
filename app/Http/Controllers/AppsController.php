<?php

namespace Modules\Telegram\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Telegram\Services\AppRegistryCollector;

class AppsController
{
  public function index(): JsonResponse
  {
    $apps = AppRegistryCollector::collect();

    return response()->json([
      'success' => true,
      'data' => $apps,
    ]);
  }
}