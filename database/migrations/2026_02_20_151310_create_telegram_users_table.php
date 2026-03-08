<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up() {
    Schema::create('telegram_users', function (Blueprint $table) {
      $table->id();
      $table->bigInteger('telegram_id')->unique();
      $table->string('first_name')->nullable();
      $table->string('last_name')->nullable();
      $table->string('username')->nullable();
      $table->string('language_code', 10)->nullable();
      $table->string('photo_url')->nullable();
      $table->json('data')->nullable();
      $table->timestamps();
    });
  }

  public function down() {
    Schema::dropIfExists('telegram_users');
  }
};