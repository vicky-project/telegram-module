<?php
namespace Modules\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telegram extends Model
{
	protected $table = "telegram";

	protected $fillable = [
		"user_id",
		"telegram_id",
		"username",
		"first_name",
		"last_name",
		"auth_date",
		"verification_code",
		"code_expires_at",
		"notifications",
		"settings",
		"additional_data",
	];

	protected $casts = [
		"auth_date" => "timestamp",
		"code_expires_at" => "timestamp",
		"settings" => "array",
		"additional_data" => "array",
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}
}
