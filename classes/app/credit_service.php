<?php

namespace local_mxaimanager\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use local_mxaimanager\app\exceptions\quota_exceeded_exception;

/**
 * Centralized credit wallet service.
 *
 * Balance = SUM(active, non-expired ledger entries) - SUM(usage logs credits_used)
 * Each ledger entry can have its own expires_at and active flag.
 */
class credit_service
{
    /**
     * Get total credits allocated (active + non-expired ledger entries only).
     */
    public static function get_total_allocated(): float
    {
        global $DB;
        $now = time();
        return (float) $DB->get_field_sql(
            'SELECT COALESCE(SUM(amount), 0)
               FROM {local_mxaimanager_credit_ledger}
              WHERE active = 1
                AND (expires_at IS NULL OR expires_at = 0 OR expires_at > ?)',
            [$now]
        );
    }

    /**
     * Get total credits consumed (from usage logs).
     */
    public static function get_total_consumed(): float
    {
        global $DB;
        return (float) $DB->get_field_sql(
            'SELECT COALESCE(SUM(credits_used), 0)
               FROM {local_mxaimanager_feature_action_usage_logs}'
        );
    }

    /**
     * Get current credit balance (active non-expired allocated minus consumed).
     */
    public static function get_balance(): float
    {
        return round(self::get_total_allocated() - self::get_total_consumed(), 2);
    }

    /**
     * Check if ALL active credit allocations have expired.
     * Returns false if there are no active allocations or at least one is still valid.
     */
    public static function is_expired(): bool
    {
        global $DB;

        // If no active entries, not expired (just unconfigured).
        $active_entries = (int) $DB->count_records('local_mxaimanager_credit_ledger', ['active' => 1]);
        if ($active_entries === 0) {
            return false;
        }

        // Check if any active entry is still valid (no expiry or not expired).
        $now = time();
        $valid = (int) $DB->count_records_sql(
            'SELECT COUNT(*)
               FROM {local_mxaimanager_credit_ledger}
              WHERE active = 1
                AND (expires_at IS NULL OR expires_at = 0 OR expires_at > ?)',
            [$now]
        );

        return $valid === 0;
    }

    /**
     * Get the nearest upcoming expiry date from active entries (for display).
     *
     * @return int|null Timestamp or null if no expiry set.
     */
    public static function get_nearest_expiry(): ?int
    {
        global $DB;
        $now = time();
        $expiry = $DB->get_field_sql(
            'SELECT MIN(expires_at)
               FROM {local_mxaimanager_credit_ledger}
              WHERE active = 1
                AND expires_at IS NOT NULL AND expires_at > 0 AND expires_at > ?',
            [$now]
        );
        return $expiry ? (int) $expiry : null;
    }

    /**
     * Add credits to the wallet (purchase, recharge, or adjustment).
     *
     * @param float $amount Number of credits to add.
     * @param string $type One of: 'initial', 'recharge', 'adjustment'.
     * @param string $note Free-text note (e.g. invoice reference).
     * @param int $user_id ID of the admin who performed the recharge.
     * @param int|null $expires_at Optional expiry timestamp for these credits.
     */
    public static function recharge(float $amount, string $type, string $note, int $user_id, ?int $expires_at = null): void
    {
        global $DB;

        $allowed_types = ['initial', 'recharge', 'adjustment'];
        if (!in_array($type, $allowed_types, true)) {
            $type = 'recharge';
        }

        $DB->insert_record('local_mxaimanager_credit_ledger', [
            'amount'      => round($amount, 2),
            'type'        => $type,
            'note'        => $note,
            'created_by'  => $user_id,
            'timecreated' => time(),
            'expires_at'  => $expires_at,
            'active'      => 1,
        ]);
    }

    /**
     * Toggle a ledger entry active/inactive.
     *
     * @param int $id Ledger entry ID.
     * @return bool New active state.
     */
    public static function toggle(int $id): bool
    {
        global $DB;

        $entry = $DB->get_record('local_mxaimanager_credit_ledger', ['id' => $id], '*', MUST_EXIST);
        $new_state = $entry->active ? 0 : 1;
        $DB->set_field('local_mxaimanager_credit_ledger', 'active', $new_state, ['id' => $id]);

        return (bool) $new_state;
    }

    /**
     * Enforce credit quota. Throws if balance is depleted or credits expired.
     *
     * @throws quota_exceeded_exception
     */
    public static function check_balance(): void
    {
        if (self::is_expired()) {
            throw new quota_exceeded_exception('expired', 'credits');
        }

        $allocated = self::get_total_allocated();

        // If no credits allocated: block if managed providers are configured,
        // otherwise skip (truly unconfigured fresh install).
        if ($allocated <= 0) {
            $has_managed = !empty(get_config('local_mxaimanager', 'managed_provider_ids'));
            if ($has_managed) {
                throw new quota_exceeded_exception('depleted', 'credits');
            }
            return;
        }

        if (self::get_balance() <= 0) {
            throw new quota_exceeded_exception('depleted', 'credits');
        }
    }

    /**
     * Calculate credit cost for a given token usage and provider multiplier.
     *
     * @param int $input_tokens
     * @param int $output_tokens
     * @param float $multiplier Provider credit multiplier.
     * @return float Credits consumed.
     */
    public static function calculate_credits(int $input_tokens, int $output_tokens, float $multiplier): float
    {
        $tokens_per_credit = (int) get_config('local_mxaimanager', 'tokens_per_credit') ?: 10000;
        $total_tokens = $input_tokens + $output_tokens;
        return round(($total_tokens * $multiplier) / $tokens_per_credit, 2);
    }

    /**
     * Get ledger history (for admin display). Includes all entries (active and inactive).
     *
     * @param int $limit Maximum entries.
     * @return array
     */
    public static function get_ledger_history(int $limit = 20): array
    {
        global $DB;
        return array_values($DB->get_records(
            'local_mxaimanager_credit_ledger',
            null,
            'timecreated DESC',
            '*',
            0,
            $limit
        ));
    }
}
