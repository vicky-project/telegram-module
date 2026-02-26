<?php
namespace Modules\Telegram\Services\Support;

class TelegramMarkdownHelper
{
  /**
  * Characters that need to be escaped in MarkdownV2
  */
  private const MARKDOWNV2_ESCAPE_CHARS = [
    //"_",
    //"*",
    "[",
    "]",
    "(",
    ")",
    //"~",
    //"`",
    ">",
    "#",
    "+",
    "-",
    "=",
    "|",
    "{",
    "}",
    ".",
    "!",
  ];

  /**
  * Escape text for MarkdownV2 parse mode
  */
  public static function escapeMarkdownV2(string $text): string
  {
    foreach (self::MARKDOWNV2_ESCAPE_CHARS as $char) {
      $text = str_replace($char, "\\" . $char, $text);
    }
    return $text;
  }

  /**
  * Escape text for HTML parse mode (if needed)
  */
  public static function escapeHtml(string $text): string
  {
    $htmlEntities = [
      "&" => "&amp;",
      "<" => "&lt;",
      ">" => "&gt;",
    ];

    return str_replace(
      array_keys($htmlEntities),
      array_values($htmlEntities),
      $text
    );
  }

  /**
  * Escape text based on parse mode
  */
  public static function escapeText(
    string $text,
    ?string $parseMode = null
  ): string {
    if ($parseMode === "MarkdownV2") {
      return self::escapeMarkdownV2($text);
    }

    if ($parseMode === "HTML") {
      return self::escapeHtml($text);
    }

    // For Markdown (legacy) and plain text, no escaping needed
    return $text;
  }

  /**
  * Check if text needs escaping for the given parse mode
  */
  public static function needsEscaping(
    string $text,
    ?string $parseMode = null
  ): bool {
    if (!$parseMode || $parseMode === "Markdown") {
      return false;
    }

    if ($parseMode === "MarkdownV2") {
      foreach (self::MARKDOWNV2_ESCAPE_CHARS as $char) {
        if (str_contains($text, $char)) {
          return true;
        }
      }
    }

    if ($parseMode === "HTML") {
      return str_contains($text, "&") ||
      str_contains($text, "<") ||
      str_contains($text, ">");
    }

    return false;
  }

  /**
  * Safely format text for Telegram with auto-escaping
  */
  public static function safeText(
    string $text,
    ?string $parseMode = null
  ): string {
    $parseMode = $parseMode ?? "Markdown";

    if (self::needsEscaping($text, $parseMode)) {
      if ($parseMode === "MarkdownV2") {
        return self::escapeMarkdownV2($text);
      }

      if ($parseMode === "HTML") {
        return self::escapeHtml($text);
      }
    }

    // If text contains code blocks, preserve them
    if (str_contains($text, "```") || str_contains($text, "`")) {
      return self::escapeCodeBlocks($text, $parseMode);
    }

    return self::escapeText($text, $parseMode);
  }

  /**
  * Handle code blocks specially to preserve formatting
  */
  private static function escapeCodeBlocks(
    string $text,
    string $parseMode
  ): string {
    if ($parseMode !== "MarkdownV2") {
      return $text;
    }

    // Split by code blocks
    $parts = preg_split(
      "/(```[^`]*```|`[^`]*`)/",
      $text,
      -1,
      PREG_SPLIT_DELIM_CAPTURE
    );

    $result = "";
    foreach ($parts as $i => $part) {
      if ($i % 2 === 0) {
        // Regular text, escape it
        $result .= self::escapeMarkdownV2($part);
      } else {
        // Code block, leave as is (but escape backticks in MarkdownV2)
        $result .= self::escapeCodeBlockContent($part);
      }
    }

    return $result;
  }

  /**
  * Escape only the content of code blocks, not the backticks themselves
  */
  private static function escapeCodeBlockContent(string $codeBlock): string
  {
    // For inline code `code`, escape only the content
    if (preg_match('/^`([^`]*)`$/', $codeBlock, $matches)) {
      $content = $matches[1];
      $escapedContent = self::escapeMarkdownV2($content);
      return "`" . $escapedContent . "`";
    }

    // For code blocks ```lang\ncode\n```, escape only the code content
    if (preg_match('/^```(\w*)\n(.*?)\n```$/s', $codeBlock, $matches)) {
      $lang = $matches[1];
      $content = $matches[2];
      $escapedContent = self::escapeMarkdownV2($content);
      return "```{$lang}\n{$escapedContent}\n```";
    }

    // Fallback: escape the whole thing
    return self::escapeMarkdownV2($codeBlock);
  }

  /**
  * Format a simple message with safe escaping
  */
  public static function formatMessage(
    string $message,
    array $params = []
  ): string {
    $parseMode = $params["parse_mode"] ?? "Markdown";

    // Replace placeholders if any
    if (!empty($params)) {
      foreach ($params as $key => $value) {
        if (is_string($value)) {
          $message = str_replace("{" . $key . "}", $value, $message);
        }
      }
    }

    return self::safeText($message, $parseMode);
  }
}