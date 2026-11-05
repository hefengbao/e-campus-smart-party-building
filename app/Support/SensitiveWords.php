<?php

namespace App\Support;

use App\Models\Config;

/**
 * 敏感词过滤：从 configs 表读取 workshop_sensitive_words（逗号分隔，兼容中文逗号），
 * 未配置时使用默认词库。
 */
class SensitiveWords
{
    /** @var array<int, string> 默认敏感词 */
    public const array DEFAULT_WORDS = [
        '反动', '暴力恐怖', '赌博', '诈骗', '色情', '谣言', '涉密', '攻击领导人',
    ];

    /**
     * 获取敏感词列表。
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $raw = Config::value('workshop_sensitive_words');

        if ($raw === null || trim($raw) === '') {
            return self::DEFAULT_WORDS;
        }

        $words = array_map(
            static fn (string $item): string => trim($item),
            explode(',', str_replace('，', ',', $raw)),
        );

        $words = array_values(array_filter($words, static fn (string $w): bool => $w !== ''));

        return $words !== [] ? $words : self::DEFAULT_WORDS;
    }

    /**
     * 检查文本是否命中敏感词，返回命中的词（未命中返回 null）。
     */
    public static function hit(string ...$texts): ?string
    {
        foreach (self::all() as $word) {
            foreach ($texts as $text) {
                if ($text !== '' && mb_stripos($text, $word) !== false) {
                    return $word;
                }
            }
        }

        return null;
    }
}
