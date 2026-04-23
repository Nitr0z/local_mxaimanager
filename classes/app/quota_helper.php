<?php

namespace local_mxaimanager\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

/**
 * Provides quota/credit usage data for display in manage page templates.
 */
class quota_helper
{
    /**
     * Returns the appropriate display data based on the configured quota mode.
     *
     * @return array Template data for quota display.
     */
    public static function get_display_data(): array
    {
        $mode = get_config('local_mxaimanager', 'quota_display_mode') ?: 'credits';

        if ($mode === 'credits') {
            return self::get_credit_bar();
        }
        return self::get_quota_bars();
    }

    /**
     * Returns credit wallet bar data.
     *
     * @return array{has_credits: bool, ...}
     */
    public static function get_credit_bar(): array
    {

        $total = credit_service::get_total_allocated();
        if ($total <= 0) {
            // No credits allocated yet — show empty state with recharge form.
            return [
                'has_credits'       => true,
                'credit_balance'    => '0.0',
                'credit_total'      => '0.0',
                'credit_used'       => '0.0',
                'credit_percent'    => 0,
                'credit_bar_class'  => 'bg-secondary',
                'credit_expired'    => false,
                'credit_expiry'     => '',
                'has_expiry'        => false,
                'credit_empty'      => true,
            ];
        }

        $balance = credit_service::get_balance();
        $used = round($total - $balance, 1);
        $pct = min(100, round(($used / $total) * 100));
        $expired = credit_service::is_expired();

        $nearest_expiry = credit_service::get_nearest_expiry();

        return [
            'has_credits'       => true,
            'credit_balance'    => number_format($balance, 1),
            'credit_total'      => number_format($total, 1),
            'credit_used'       => number_format($used, 1),
            'credit_percent'    => $pct,
            'credit_bar_class'  => $expired ? 'bg-secondary' : self::bar_class($pct),
            'credit_expired'    => $expired,
            'credit_expiry'     => $nearest_expiry ? userdate($nearest_expiry, '%d/%m/%Y') : '',
            'has_expiry'        => $nearest_expiry !== null,
        ];
    }

    /**
     * Returns token quota bar data for all configured quotas (daily/weekly/monthly × input/output).
     * Used when quota_display_mode = 'tokens'.
     * Quotas apply to all managed providers.
     *
     * @return array{has_quotas: bool, bars: array}
     */
    public static function get_quota_bars(): array
    {
        global $DB;

        $now = time();
        $periods = [
            'daily' => [
                'start' => mktime(0, 0, 0, (int)date('n', $now), (int)date('j', $now), (int)date('Y', $now)),
                'label_input' => get_string('daily_input_quota', 'local_mxaimanager'),
                'label_output' => get_string('daily_output_quota', 'local_mxaimanager'),
            ],
            'weekly' => [
                'start' => strtotime('monday this week', $now),
                'label_input' => get_string('weekly_input_quota', 'local_mxaimanager'),
                'label_output' => get_string('weekly_output_quota', 'local_mxaimanager'),
            ],
            'monthly' => [
                'start' => mktime(0, 0, 0, (int)date('n', $now), 1, (int)date('Y', $now)),
                'label_input' => get_string('monthly_input_quota', 'local_mxaimanager'),
                'label_output' => get_string('monthly_output_quota', 'local_mxaimanager'),
            ],
        ];

        $bars = [];

        foreach ($periods as $period => $info) {
            $input_quota = (int) get_config('local_mxaimanager', "{$period}_input_quota");
            $output_quota = (int) get_config('local_mxaimanager', "{$period}_output_quota");

            if ($input_quota <= 0 && $output_quota <= 0) {
                continue;
            }

            $row = $DB->get_record_sql(
                'SELECT COALESCE(SUM(input_tokens), 0) AS used_input,
                        COALESCE(SUM(output_tokens), 0) AS used_output
                   FROM {local_mxaimanager_feature_action_usage_logs}
                  WHERE timecreated >= :start',
                ['start' => $info['start']]
            );

            if ($input_quota > 0) {
                $used = (int) $row->used_input;
                $pct = min(100, round(($used / $input_quota) * 100));
                $bars[] = [
                    'label' => $info['label_input'],
                    'used' => number_format($used),
                    'limit' => number_format($input_quota),
                    'percent' => $pct,
                    'bar_class' => self::bar_class($pct),
                ];
            }

            if ($output_quota > 0) {
                $used = (int) $row->used_output;
                $pct = min(100, round(($used / $output_quota) * 100));
                $bars[] = [
                    'label' => $info['label_output'],
                    'used' => number_format($used),
                    'limit' => number_format($output_quota),
                    'percent' => $pct,
                    'bar_class' => self::bar_class($pct),
                ];
            }
        }

        return [
            'has_quotas' => !empty($bars),
            'bars' => $bars,
        ];
    }

    private static function bar_class(int $pct): string
    {
        if ($pct >= 90) {
            return 'bg-danger';
        }
        if ($pct >= 70) {
            return 'bg-warning';
        }
        return 'bg-success';
    }
}
