<?php

namespace local_mxaimanager;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use local_mxaimanager\app\credit_service;

/**
 * Custom admin setting that renders the credit recharge form and ledger history.
 * This is displayed in the admin settings page, only accessible by site admins.
 */
class admin_setting_credit_recharge extends \admin_setting
{
    public function __construct()
    {
        // Handle toggle via GET before anything else renders.
        // This avoids the nested-form problem (nested <form> is invalid HTML).
        $this->handle_toggle_get();

        parent::__construct(
            'local_mxaimanager/credit_recharge_ui',
            get_string('credit_recharge_heading', 'local_mxaimanager'),
            '',
            ''
        );
        // nosave is NOT set here — we need write_setting() to be called on POST
        // so that credit recharge actions are processed before Moodle redirects.
    }

    /**
     * Handle toggle via GET param (clicked as a link, not a form submit).
     * Processes immediately and redirects to remove the param from the URL.
     */
    private function handle_toggle_get(): void
    {
        $toggle_id = optional_param('credit_toggle_id', 0, PARAM_INT);
        if ($toggle_id > 0 && confirm_sesskey()) {
            credit_service::toggle($toggle_id);
            $redirect = new \moodle_url('/admin/settings.php', ['section' => 'local_mxaimanager_billing']);
            redirect($redirect);
        }
    }

    /**
     * Return the default so Moodle doesn't flag this as needing a save.
     */
    public function get_setting()
    {
        return '';
    }

    /**
     * Process the recharge if form was submitted.
     * Moodle calls write_setting() on POST before redirecting,
     * so output_html() never sees the POST data.
     */
    public function write_setting($data)
    {
        global $USER;

        // Handle recharge POST.
        if (optional_param('credit_recharge_submit', false, PARAM_BOOL)) {
            require_sesskey();
            $amount = (float) optional_param('credit_recharge_amount', 0, PARAM_FLOAT);
            $note = optional_param('credit_recharge_note', '', PARAM_TEXT);
            $expiry_str = optional_param('credit_recharge_expiry', '', PARAM_TEXT);
            $expires_at = !empty($expiry_str) ? strtotime($expiry_str . ' 23:59:59') : null;
            if ($amount > 0) {
                credit_service::recharge($amount, 'recharge', $note, $USER->id, $expires_at);
            }
        }

        return '';
    }

    /**
     * Render the recharge form and credit ledger using a Mustache template.
     */
    public function output_html($data, $query = ''): string
    {
        global $OUTPUT;

        // Build current status.
        $total = credit_service::get_total_allocated();
        $balance = credit_service::get_balance();
        $used = round($total - $balance, 1);
        $expired = credit_service::is_expired();

        // Status bar calculations.
        $pct = $total > 0 ? min(100, round(($used / $total) * 100)) : 0;
        $bar_class = $expired ? 'bg-secondary' : ($pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success'));

        $action_url = (new \moodle_url('/admin/settings.php', ['section' => 'local_mxaimanager_billing']))->out(false);
        $sesskey = sesskey();

        // Build ledger rows for template.
        $ledger = credit_service::get_ledger_history(15);
        $ledger_rows = [];
        foreach ($ledger as $entry) {
            $is_active = !empty($entry->active);

            // Build toggle URL as a GET link (avoids nested form issue).
            $toggle_url = new \moodle_url('/admin/settings.php', [
                'section' => 'local_mxaimanager_billing',
                'credit_toggle_id' => $entry->id,
                'sesskey' => $sesskey,
            ]);

            $ledger_rows[] = [
                'id'           => $entry->id,
                'date'         => userdate($entry->timecreated, '%d/%m/%Y %H:%M'),
                'amount'       => number_format((float) $entry->amount, 1),
                'type'         => get_string('credit_type_' . $entry->type, 'local_mxaimanager'),
                'note'         => s($entry->note ?? ''),
                'expiry'       => !empty($entry->expires_at) ? userdate((int)$entry->expires_at, '%d/%m/%Y') : '—',
                'is_active'    => $is_active,
                'amount_class' => $is_active ? 'text-success font-weight-bold' : 'text-muted',
                'btn_class'    => $is_active ? 'btn-outline-danger' : 'btn-outline-success',
                'btn_icon'     => $is_active ? 'fa-ban' : 'fa-check',
                'btn_title'    => $is_active
                    ? get_string('credit_disable', 'local_mxaimanager')
                    : get_string('credit_enable', 'local_mxaimanager'),
                'toggle_url'   => $toggle_url->out(false),
            ];
        }

        // Build template context.
        $context = [
            'has_credits'             => $total > 0,
            'balance_fmt'             => number_format($balance, 1),
            'credits_remaining_label' => get_string('credits_remaining', 'local_mxaimanager'),
            'used_fmt'                => number_format($used, 1),
            'total_fmt'               => number_format($total, 1),
            'bar_class'               => $bar_class,
            'pct'                     => $pct,
            'is_expired'              => $expired,
            'credit_expired_label'    => get_string('credit_expired', 'local_mxaimanager'),
            'credit_empty_info_label' => get_string('credit_empty_info', 'local_mxaimanager'),
            'action_url'              => $action_url,
            'sesskey'                 => $sesskey,
            'recharge_label'          => get_string('credit_recharge', 'local_mxaimanager'),
            'amount_placeholder'      => get_string('credit_recharge_amount', 'local_mxaimanager'),
            'amount_label'            => get_string('credit_recharge_amount_label', 'local_mxaimanager'),
            'expiry_placeholder'      => get_string('credit_recharge_expiry', 'local_mxaimanager'),
            'expiry_label'            => get_string('credit_recharge_expiry_label', 'local_mxaimanager'),
            'note_placeholder'        => get_string('credit_recharge_note', 'local_mxaimanager'),
            'note_label'              => get_string('credit_recharge_note_label', 'local_mxaimanager'),
            'has_ledger'              => !empty($ledger_rows),
            'th_date'                 => get_string('credit_history_date', 'local_mxaimanager'),
            'th_amount'               => get_string('credit_history_amount', 'local_mxaimanager'),
            'th_type'                 => get_string('credit_history_type', 'local_mxaimanager'),
            'th_expiry'               => get_string('credit_history_expiry', 'local_mxaimanager'),
            'th_note'                 => get_string('credit_history_note', 'local_mxaimanager'),
            'ledger_rows'             => $ledger_rows,
        ];

        $html = '<div class="form-group row"><div class="col-sm-12">'
            . $OUTPUT->render_from_template('local_mxaimanager/admin_credit_recharge', $context)
            . '</div></div>';

        return format_admin_setting($this, $this->visiblename, $html, $this->description);
    }
}

