<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create("telegram", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table
				->unsignedBigInteger("telegram_id")
				->unique()
				->nullable();
			$table->string("username")->nullable();
			$table->string("first_name")->nullable();
			$table->string("last_name")->nullable();
			$table->timestamp("auth_date")->nullable();
			$table->string("verification_code")->nullable();
			$table->timestamp("code_expires_at")->nullable();
			$table->boolean("notifications")->default(true);
			$table->json("settings")->nullable();
			$table->json("additional_data")->nullable();
			$table->timestamps();

			$table->index(["user_id", "telegram_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists("telegram");
	}
};
