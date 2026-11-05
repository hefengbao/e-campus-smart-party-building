<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\FeeRecord;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * 党费系统对接：接收外部党费系统推送的交纳记录（fee/sync）。
 *
 * 鉴权：请求头 X-Fee-Token 与 configs 表 fee_sync_token（bcrypt）比对。
 * 匹配：先 staff_no 精确匹配，再 id_card 哈希匹配（sha256，与 demo 契约一致）。
 * 写入：(member_id, period) upsert，source = 党费系统同步。
 */
class FeeSyncController extends Controller
{
    /**
     * 批量同步党费记录。
     */
    public function sync(Request $request): JsonResponse
    {
        $token = (string) $request->header('X-Fee-Token', '');

        if (! $this->tokenMatches($token)) {
            return response()->json(['message' => '无效的同步令牌'], 401);
        }

        $validated = $request->validate([
            'records' => ['required', 'array'],
            'records.*.staff_no' => ['nullable', 'string'],
            'records.*.id_card' => ['nullable', 'string'],
            'records.*.period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'records.*.amount' => ['nullable', 'numeric', 'min:0'],
            'records.*.pay_status' => ['nullable', 'in:已交纳,未交纳'],
            'records.*.pay_date' => ['nullable', 'date'],
        ]);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($validated['records'] as $index => $record) {
            try {
                $member = $this->matchMember($record);

                if ($member === null) {
                    throw new \RuntimeException('未匹配到党员');
                }

                $attributes = [
                    'member_id' => $member->id,
                    'period' => $record['period'],
                ];

                $values = [
                    'amount' => (float) ($record['amount'] ?? 0),
                    'pay_status' => $record['pay_status'] ?? '已交纳',
                    'pay_date' => $record['pay_date'] ?? null,
                    'source' => '党费系统同步',
                ];

                $existing = FeeRecord::query()->where($attributes)->first();

                if ($existing === null) {
                    FeeRecord::query()->create($attributes + $values);
                    $created++;
                } else {
                    $existing->fill($values)->save();
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['index' => $index, 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }

    /**
     * 校验同步令牌（configs.fee_sync_token 存 bcrypt 哈希）。
     */
    private function tokenMatches(string $token): bool
    {
        $hash = Config::value('fee_sync_token');

        if ($hash === null || $token === '') {
            return false;
        }

        return Hash::check($token, $hash);
    }

    /**
     * 匹配党员：先 staff_no 精确匹配，再 id_card 哈希匹配。
     *
     * id_card 使用 encrypted cast 无法等值查询，采用 sha256 摘要比对
     * （与 demo 契约一致：同一明文摘要稳定）。
     *
     * @param  array<string, mixed>  $record
     */
    private function matchMember(array $record): ?Member
    {
        $staffNo = trim((string) ($record['staff_no'] ?? ''));

        if ($staffNo !== '') {
            $member = Member::query()->where('staff_no', $staffNo)->first();

            if ($member !== null) {
                return $member;
            }
        }

        $idCard = trim((string) ($record['id_card'] ?? ''));

        if ($idCard !== '') {
            $digest = hash('sha256', $idCard);

            return Member::query()
                ->get()
                ->first(fn (Member $member): bool => hash('sha256', (string) $member->id_card) === $digest);
        }

        return null;
    }
}
