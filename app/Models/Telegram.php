<?php
namespace Modules\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\UserManagement\Models\SocialAccount;
use Modules\UserManagement\Interfaces\SocialAccountInterface;

class Telegram extends Model implements SocialAccountInterface
{
	protected $table = "telegram";

	protected $fillable = [
		"telegram_id",
		"username",
		"first_name",
		"last_name",
		"auth_date",
		"notifications",
		"settings",
		"additional_data",
	];

	protected $casts = [
		"auth_date" => "timestamp",
		"settings" => "array",
		"additional_data" => "array",
	];

	public function provider(): MorphOne
	{
		return $this->morphOne(SocialAccount::class, "providerable");
	}
}
