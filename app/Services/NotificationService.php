<?php
namespace Modules\Telegram\Services;

use App\Models\User;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Services\Telegram\Handlers\Callbacks\CallbackHandlerFactory;
use Modules\Wallet\Services\Telegram\Types\BudgetNotification;
use Modules\Wallet\Services\Telegram\Types\TransactionNotification;

class NotificationService
{
	protected $telegram;
	protected $telegramLinkService;
	protected $handlerFactory;

	public function __construct(
		LinkService $telegramLinkService,
		CallbackHandlerFactory $handlerFactory
	) {
		$token = config("wallet.telegram_bot.token");
		if ($token) {
			$this->telegram = new Api($token);
		}
		$this->telegramLinkService = $telegramLinkService;
		$this->handlerFactory = $handlerFactory;
	}

	/**
	 * Handle incoming callback query
	 */
	public function handleCallbackQuery(CallbackQuery $callbackQuery): array
	{
		try {
			$chatId = $callbackQuery
				->getMessage()
				->getChat()
				->getId();
			$data =
				json_decode($callbackQuery->getData(), true) ??
				$this->parseLegacyCallbackData($callbackQuery->getData());

			if (!$data) {
				$this->telegramApi->answerCallbackQuery(
					$callbackQuery->getId(),
					"❌ Data tidak valid",
					true
				);
				return ["success" => false];
			}

			$user = User::where("telegram_chat_id", $chatId)->first();
			if (!$user) {
				$this->telegramApi->answerCallbackQuery(
					$callbackQuery->getId(),
					"❌ User tidak ditemukan",
					true
				);
				return ["success" => false];
			}

			$action = $data["action"] ?? null;
			$type = $data["type"] ?? null;

			if (!$action || !$type) {
				$this->telegramApi->answerCallbackQuery(
					$callbackQuery->getId(),
					"❌ Format callback tidak valid",
					true
				);
				return ["success" => false];
			}

			// Special case for cancel action
			if ($action === "cancel") {
				$this->telegramApi->deleteMessage(
					$chatId,
					$callbackQuery->getMessage()->getMessageId()
				);
				$this->telegramApi->answerCallbackQuery(
					$callbackQuery->getId(),
					"❌ Dibatalkan"
				);
				return ["success" => true];
			}

			// Find appropriate handler
			$handler = $this->handlerFactory->getHandlerForCallback($action, $type);
			if (!$handler) {
				$this->telegramApi->answerCallbackQuery(
					$callbackQuery->getId(),
					"❌ Handler tidak ditemukan",
					true
				);
				return ["success" => false];
			}

			// Execute handler
			return $handler->handle($user, $data, $callbackQuery);
		} catch (\Exception $e) {
			Log::error("Callback query handling error", [
				"callback_data" => $callbackQuery->getData(),
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			$this->telegramApi->answerCallbackQuery(
				$callbackQuery->getId(),
				"❌ Terjadi kesalahan sistem",
				true
			);
			return ["success" => false, "error" => $e->getMessage()];
		}
	}

	/**
	 * Parse legacy callback data format (action_type_id)
	 */
	private function parseLegacyCallbackData(string $data): array
	{
		$parts = explode("_", $data, 3);

		if (count($parts) < 3) {
			return [];
		}

		return [
			"action" => $parts[0],
			"type" => $parts[1],
			"id" => $parts[2],
		];
	}

	/**
	 * Send notification to user
	 */
	public function send(User $user, string $message, array $options = []): bool
	{
		if (!$user->hasLinkedTelegram() || !$user->telegram_notifications) {
			return false;
		}

		// Check if user wants notifications
		if (!$this->shouldSendNotification($user, $options["type"] ?? "general")) {
			return false;
		}

		try {
			$chatId = $user->telegram_chat_id;

			$params = [
				"chat_id" => $chatId,
				"text" => $message,
				"parse_mode" => $options["parse_mode"] ?? null,
				"disable_web_page_preview" => $options["disable_preview"] ?? true,
			];

			// Add keyboard if provided
			if (isset($options["reply_markup"])) {
				$params["reply_markup"] = $options["reply_markup"];
			}

			$this->telegram->sendMessage($params);

			Log::info("Telegram notification sent", [
				"user_id" => $user->id,
				"type" => $options["type"] ?? "general",
			]);

			return true;
		} catch (TelegramSDKException $e) {
			Log::error("Failed to send Telegram notification", [
				"user_id" => $user->id,
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}

	/**
	 * Check if user wants this type of notification
	 */
	private function shouldSendNotification(User $user, string $type): bool
	{
		$settings = $user->getAllTelegramSettings();

		// Default settings if not configured
		$defaults = [
			"new_transaction" => true,
			"daily_summary" => false,
			"weekly_summary" => true,
			"budget_warning" => true,
			"budget_exceeded" => true,
			"low_balance" => true,
			"bill_reminder" => true,
			"income_achievement" => true,
			"savings_milestone" => true,
		];

		$setting = $settings[$type] ?? ($defaults[$type] ?? false);

		return (bool) $setting;
	}

	/**
	 * Notify new transaction
	 */
	public function notifyNewTransaction(
		User $user,
		Transaction $transaction
	): bool {
		$notification = app(TransactionNotification::class);
		$notification->setContext($user, ["transaction" => $transaction]);

		return $notification->send($user);
	}

	/**
	 * Notify daily summary
	 */
	public function notifyDailySummary(User $user, array $summary): bool
	{
		$date = Carbon::now()->format("d/m/Y");
		$totalIncome = number_format($summary["income"] ?? 0);
		$totalExpense = number_format($summary["expense"] ?? 0);
		$netAmount = number_format(
			($summary["income"] ?? 0) - ($summary["expense"] ?? 0)
		);
		$transactionCount = $summary["count"] ?? 0;

		$message = "📊 *Laporan Harian {$date}*\n\n";
		$message .= "💰 *Pemasukan:* Rp {$totalIncome}\n";
		$message .= "💸 *Pengeluaran:* Rp {$totalExpense}\n";
		$message .= "📈 *Bersih:* Rp {$netAmount}\n";
		$message .= "📝 *Jumlah Transaksi:* {$transactionCount}\n";

		// Top categories
		if (!empty($summary["top_categories"])) {
			$message .= "\n🏆 *Kategori Teratas:*\n";
			foreach ($summary["top_categories"] as $index => $category) {
				$amount = number_format($category["amount"]);
				$emoji = ["🥇", "🥈", "🥉"][$index] ?? "•";
				$message .= "{$emoji} {$category["name"]}: Rp {$amount}\n";
			}
		}

		// Budget status
		if (!empty($summary["budget_alerts"])) {
			$message .= "\n⚠️ *Status Budget:*\n";
			foreach ($summary["budget_alerts"] as $alert) {
				$percentage = round($alert["percentage"]);
				$message .= "• {$alert["category"]}: {$percentage}% terpakai\n";
			}
		}

		$message .= "\n💡 *Tips:* Cek detail di aplikasi web.";

		return $this->send($user, $message, [
			"type" => "daily_summary",
			"parse_mode" => "Markdown",
		]);
	}

	/**
	 * Notify weekly summary
	 */
	public function notifyWeeklySummary(User $user, array $summary): bool
	{
		$week = Carbon::now()->weekOfYear;
		$year = Carbon::now()->year;
		$totalIncome = number_format($summary["income"] ?? 0);
		$totalExpense = number_format($summary["expense"] ?? 0);
		$savingsRate =
			$summary["income"] > 0
				? round(
					(($summary["income"] - $summary["expense"]) / $summary["income"]) *
						100,
					1
				)
				: 0;

		$message = "📈 *Laporan Mingguan #{$week}/{$year}*\n\n";
		$message .= "💰 *Total Pemasukan:* Rp {$totalIncome}\n";
		$message .= "💸 *Total Pengeluaran:* Rp {$totalExpense}\n";
		$message .= "📊 *Tabungan:* {$savingsRate}%\n";
		$message .=
			"📝 *Rata-rata Harian:* Rp " .
			number_format(($summary["expense"] ?? 0) / 7) .
			"\n";

		// Expense breakdown
		if (!empty($summary["categories"])) {
			$message .= "\n📋 *Breakdown Pengeluaran:*\n";
			foreach ($summary["categories"] as $category) {
				$percentage =
					$summary["expense"] > 0
						? round(($category["amount"] / $summary["expense"]) * 100)
						: 0;
				$message .= "• {$category["name"]}: {$percentage}%\n";
			}
		}

		// Comparison with previous week
		if (isset($summary["previous_week"])) {
			$change = $summary["expense"] - $summary["previous_week"];
			$trend = $change >= 0 ? "📈 Naik" : "📉 Turun";
			$message .=
				"\n📊 *Perbandingan:* {$trend} " .
				number_format(abs($change)) .
				" dari minggu lalu";
		}

		$message .=
			"\n\n🎯 *Target Minggu Depan:* Kurangi pengeluaran non-esensial.";

		return $this->send($user, $message, [
			"type" => "weekly_summary",
			"parse_mode" => "Markdown",
		]);
	}

	/**
	 * Notify budget warning (80-99% usage)
	 */
	public function notifyBudgetWarning(
		User $user,
		Budget $budget,
		float $percentage
	): bool {
		$notification = app(BudgetNotification::class);
		$notification->setContext($user, ["budget", "percentage" => $percentage]);

		return $notification->send($user);
	}

	/**
	 * Notify budget exceeded
	 */
	public function notifyBudgetExceeded(User $user, Budget $budget): bool
	{
		$exceededBy = number_format(
			$budget->spent->getAmount()->toInt() -
				$budget->amount->getAmount()->toInt()
		);
		$usagePercentage = round(
			($budget->spent->getAmount()->toInt() /
				$budget->amount->getAmount()->toInt()) *
				100
		);

		$message = "🚨 *Budget Terlampaui!*\n\n";
		$message .= "📋 *Budget:* {$budget->name}\n";
		$message .= "📂 *Kategori:* {$budget->category->name}\n";
		$message .= "📊 *Penggunaan:* {$usagePercentage}%\n";
		$message .= "💸 *Melebihi:* Rp {$exceededBy}\n";
		$message .=
			"💰 *Total Terpakai:* Rp " .
			number_format($budget->spent->getAmount()->toInt()) .
			"\n\n";
		$message .= "⚠️ *Tindakan:*\n";
		$message .= "• Evaluasi pengeluaran kategori ini\n";
		$message .= "• Pertimbangkan untuk menambah budget\n";
		$message .= "• Tinjau transaksi yang tidak perlu";

		return $this->send($user, $message, [
			"type" => "budget_exceeded",
			"parse_mode" => "Markdown",
		]);
	}

	/**
	 * Notify low balance
	 */
	public function notifyLowBalance(
		User $user,
		Account $account,
		int $threshold
	): bool {
		$balance = number_format($account->balance->getAmount()->toInt());
		$thresholdFormatted = number_format($threshold);
		$percentage = round(
			($account->balance->getAmount()->toInt() / $threshold) * 100
		);

		$message = "🔔 *Peringatan Saldo Rendah*\n\n";
		$message .= "🏦 *Akun:* {$account->name}\n";
		$message .= "💰 *Saldo:* Rp {$balance}\n";
		$message .= "📊 *Threshold:* Rp {$thresholdFormatted}\n";
		$message .= "📈 *Persentase:* {$percentage}%\n\n";
		$message .= "💡 *Saran:*\n";
		$message .= "• Tambahkan dana ke akun ini\n";
		$message .= "• Tinjau pengeluaran mendatang\n";
		$message .= "• Pertimbangkan transfer dari akun lain";

		// Add quick action buttons
		$keyboard = [
			"inline_keyboard" => [
				[
					[
						"text" => "💰 Tambah Saldo",
						"callback_data" => "add_funds_" . $account->id,
					],
					[
						"text" => "📊 Lihat Transaksi",
						"callback_data" => "view_transactions_" . $account->id,
					],
				],
			],
		];

		return $this->send($user, $message, [
			"type" => "low_balance",
			"parse_mode" => "Markdown",
			"reply_markup" => json_encode($keyboard),
		]);
	}

	/**
	 * Notify bill reminder
	 */
	public function notifyBillReminder(User $user, array $bills): bool
	{
		$dueCount = count($bills);
		$totalAmount = number_format(array_sum(array_column($bills, "amount")));

		$message = "📅 *Pengingat Tagihan*\n\n";
		$message .= "Anda memiliki {$dueCount} tagihan yang akan jatuh tempo:\n\n";

		foreach ($bills as $bill) {
			$dueDate = Carbon::parse($bill["due_date"])->format("d/m");
			$amount = number_format($bill["amount"]);
			$message .= "• {$bill["description"]}: Rp {$amount} (Jatuh tempo: {$dueDate})\n";
		}

		$message .= "\n💡 Jangan lupa bayar tagihan sebelum jatuh tempo!";

		return $this->send($user, $message, [
			"type" => "bill_reminder",
			"parse_mode" => "Markdown",
		]);
	}

	/**
	 * Notify savings milestone
	 */
	public function notifySavingsMilestone(
		User $user,
		Account $account,
		int $milestone
	): bool {
		$balance = number_format($account->balance->getAmount()->toInt());
		$milestoneFormatted = number_format($milestone);

		$message = "🎉 *Pencapaian Tabungan!*\n\n";
		$message .= "🏦 *Akun:* {$account->name}\n";
		$message .= "💰 *Saldo:* Rp {$balance}\n";
		$message .= "🎯 *Milestone:* Rp {$milestoneFormatted}\n\n";
		$message .= "Selamat! Anda telah mencapai milestone tabungan. 🎊\n";
		$message .= "Terus pertahankan kebiasaan menabung yang baik!";

		return $this->send($user, $message, [
			"type" => "savings_milestone",
			"parse_mode" => "Markdown",
		]);
	}

	/**
	 * Notify income achievement
	 */
	public function notifyIncomeAchievement(User $user, array $achievement): bool
	{
		$message = "📈 *Pencapaian Pemasukan!*\n\n";
		$message .= "Bulan ini Anda telah:\n\n";
		$message .=
			"💰 *Total Pemasukan:* Rp " .
			number_format($achievement["total_income"]) .
			"\n";
		$message .=
			"📊 *Rata-rata Harian:* Rp " .
			number_format($achievement["daily_average"]) .
			"\n";
		$message .=
			"📈 *Pertumbuhan:* " .
			($achievement["growth"] >= 0 ? "+" : "") .
			round($achievement["growth"], 1) .
			"% dari bulan lalu\n\n";
		$message .= "Luar biasa! Pertahankan konsistensi Anda. 💪";

		return $this->send($user, $message, [
			"type" => "income_achievement",
			"parse_mode" => "Markdown",
		]);
	}

	/**
	 * Send custom notification
	 */
	public function sendCustomNotification(
		User $user,
		string $title,
		string $content,
		string $type = "info"
	): bool {
		$icon = match ($type) {
			"success" => "✅",
			"warning" => "⚠️",
			"error" => "❌",
			"info" => "ℹ️",
			default => "📢",
		};

		$message = "{$icon} *{$title}*\n\n{$content}";

		return $this->send($user, $message, [
			"type" => "custom",
			"parse_mode" => "Markdown",
		]);
	}

	/**
	 * Get user's notification settings
	 */
	public function getUserSettings(User $user): array
	{
		$defaults = [
			"new_transaction" => true,
			"daily_summary" => false,
			"weekly_summary" => true,
			"budget_warning" => true,
			"budget_exceeded" => true,
			"low_balance" => true,
			"bill_reminder" => true,
			"income_achievement" => true,
			"savings_milestone" => true,
		];

		$settings = $user->telegram_settings ?? [];

		return array_merge($defaults, $settings);
	}

	/**
	 * Update user's notification settings
	 */
	public function updateUserSettings(User $user, array $settings): bool
	{
		$current = $user->telegram_settings ?? [];
		$newSettings = array_merge($current, $settings);

		return $user->update(["telegram_settings" => $newSettings]);
	}
}
