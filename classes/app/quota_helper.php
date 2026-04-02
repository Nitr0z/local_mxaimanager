<?php

namespace local_mxaimanager\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

/**
 * Provides quota usage data for display in manage page templates.
 */
class quota_helper
{
    /**
     * Check if the current default chat provider is the built-in freemium (preconfigured, negative ID).
     */
    private static function is_using_freemium(): bool
    {
        // If freemium is disabled entirely, it cannot be in use.
        if (empty(get_config('local_mxaimanager', 'enable_freemium'))) {
            return false;
        }

        try {
            $factory = \local_mxaimanager\app\factory::make();
            $chat_interface = \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class;
            $default = $factory->ai()->default_provider()->repository()->get_by_action_interface($chat_interface);
            return $default->get_provider_id() < 0;
        } catch (\Exception $e) {
            // No default set — freemium is enabled, so it acts as fallback.
            return true;
        }
    }

    /**
     * Returns quota bar data for all configured quotas (daily/weekly/monthly × input/output).
     * If the default provider is not freemium, returns an "unlimited" state instead.
     *
     * @return array{has_quotas: bool, quota_unlimited: bool, bars: array}
     */
    public static function get_quota_bars(): array
    {
        // If client uses their own provider, show unlimited.
        if (!self::is_using_freemium()) {
            return [
                'has_quotas' => false,
                'quota_unlimited' => true,
                'bars' => [],
            ];
        }

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
