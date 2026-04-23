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
        parent::__construct(
            'local_mxaimanager/credit_recharge_ui',
            get_string('credit_recharge_heading', 'local_mxaimanager'),
            '',
            ''
        );
        $this->nosave = true;
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
     */
    public function write_setting($data)
    {
        // Handled via separate POST in output_html.
        return '';
    }

    /**
     * Render the recharge form and credit ledger.
     */
    public function output_html($data, $query = ''): string
    {
        global $OUTPUT, $USER;

        // Handle recharge POST.
        if (optional_param('credit_recharge_submit', false, PARAM_BOOL)) {
            require_sesskey();
            $amount = (float) optional_param('credit_recharge_amount', 0, PARAM_FLOAT);
            $note = optional_param('credit_recharge_note', '', PARAM_TEXT);
            $expiry_str = optional_param('credit_recharge_expiry', '', PARAM_TEXT);
            $expires_at = !empty($expiry_str) ? strtotime($expiry_str . ' 23:59:59') : null;
            if ($amount > 0) {
                credit_service::recharge($amount, 'recharge', $note, $USER->id, $expires_at);
                redirect(new \moodle_url('/admin/settings.php', [
                    'section' => 'local_mxaimanager_billing'
                ]), get_string('credit_recharged', 'local_mxaimanager'), 0, 'success');
            }
        }

        // Handle toggle POST.
        $toggle_id = optional_param('credit_toggle_id', 0, PARAM_INT);
        if ($toggle_id > 0) {
            require_sesskey();
            credit_service::toggle($toggle_id);
            redirect(new \moodle_url('/admin/settings.php', [
                'section' => 'local_mxaimanager_billing'
            ]));
        }

        // Build current status.
        $total = credit_service::get_total_allocated();
        $balance = credit_service::get_balance();
        $used = round($total - $balance, 1);
        $expired = credit_service::is_expired();

        // Build ledger history.
        $ledger = credit_service::get_ledger_history(15);
        $ledger_rows = '';
        $action_url = new \moodle_url('/admin/settings.php', ['section' => 'local_mxaimanager_billing']);
        $sesskey = sesskey();
        foreach ($ledger as $entry) {
            $date = userdate($entry->timecreated, '%d/%m/%Y %H:%M');
            $amount = number_format((float) $entry->amount, 1);
            $type = get_string('credit_type_' . $entry->type, 'local_mxaimanager');
            $note = s($entry->note ?? '');
            $expiry = !empty($entry->expires_at) ? userdate((int)$entry->expires_at, '%d/%m/%Y') : '—';
            $is_active = !empty($entry->active);

            // Toggle button.
            $btn_class = $is_active ? 'btn-outline-danger' : 'btn-outline-success';
            $btn_icon = $is_active ? 'fa-ban' : 'fa-check';
            $btn_title = $is_active
                ? get_string('credit_disable', 'local_mxaimanager')
                : get_string('credit_enable', 'local_mxaimanager');
            $toggle_btn = "<form method='post' action='{$action_url}' style='display:inline;'>"
                . "<input type='hidden' name='sesskey' value='{$sesskey}'/>"
                . "<input type='hidden' name='credit_toggle_id' value='{$entry->id}'/>"
                . "<button type='submit' class='btn btn-sm {$btn_class}' title='{$btn_title}'>"
                . "<i class='fa {$btn_icon}'></i></button></form>";

            // Row styling.
            $row_class = $is_active ? '' : "class='text-muted'";
            $amount_class = $is_active ? 'text-success font-weight-bold' : 'text-muted';
            $strike_start = $is_active ? '' : '<s>';
            $strike_end = $is_active ? '' : '</s>';

            $ledger_rows .= "<tr {$row_class}>"
                . "<td>{$date}</td>"
                . "<td class='{$amount_class}'>{$strike_start}+{$amount}{$strike_end}</td>"
                . "<td>{$type}</td>"
                . "<td>{$expiry}</td>"
                . "<td>{$note}</td>"
                . "<td>{$toggle_btn}</td>"
                . "</tr>";
        }

        // Status bar.
        $pct = $total > 0 ? min(100, round(($used / $total) * 100)) : 0;
        $bar_class = $expired ? 'bg-secondary' : ($pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success'));
        $balance_fmt = number_format($balance, 1);
        $total_fmt = number_format($total, 1);

        $status_html = '';
        if ($total > 0) {
            $status_html = "
                <div class='d-flex justify-content-between mb-1'>
                    <strong>{$balance_fmt} " . get_string('credits_remaining', 'local_mxaimanager') . "</strong>
                    <small class='text-muted'>" . number_format($used, 1) . " / {$total_fmt}</small>
                </div>
                <div class='progress mb-3' style='height: 12px;'>
                    <div class='progress-bar {$bar_class}' style='width: {$pct}%'></div>
                </div>";
        } else {
            $status_html = "<div class='alert alert-info'><i class='fa fa-info-circle mr-1'></i> "
                . get_string('credit_empty_info', 'local_mxaimanager') . "</div>";
        }

        if ($expired) {
            $status_html .= "<div class='alert alert-danger'><i class='fa fa-exclamation-triangle mr-1'></i> "
                . get_string('credit_expired', 'local_mxaimanager') . "</div>";
        }

        // Recharge form.
        $sesskey = sesskey();
        $recharge_label = get_string('credit_recharge', 'local_mxaimanager');
        $amount_ph = get_string('credit_recharge_amount', 'local_mxaimanager');
        $note_ph = get_string('credit_recharge_note', 'local_mxaimanager');
        $action_url = new \moodle_url('/admin/settings.php', ['section' => 'local_mxaimanager_billing']);

        $expiry_ph = get_string('credit_recharge_expiry', 'local_mxaimanager');

        $form_html = "
            <form method='post' action='{$action_url}' class='form-inline mb-3'>
                <input type='hidden' name='sesskey' value='{$sesskey}'/>
                <input type='hidden' name='credit_recharge_submit' value='1'/>
                <div class='form-group mr-2'>
                    <input type='number' name='credit_recharge_amount' step='0.1' min='1'
                           class='form-control' placeholder='{$amount_ph}' style='width: 120px;'/>
                </div>
                <div class='form-group mr-2'>
                    <input type='date' name='credit_recharge_expiry' class='form-control'
                           title='{$expiry_ph}' style='width: 160px;'/>
                </div>
                <div class='form-group mr-2'>
                    <input type='text' name='credit_recharge_note' class='form-control'
                           placeholder='{$note_ph}' style='width: 200px;'/>
                </div>
                <button type='submit' class='btn btn-primary btn-sm'>
                    <i class='fa fa-plus mr-1'></i> {$recharge_label}
                </button>
            </form>";

        // Ledger table.
        $ledger_html = '';
        if (!empty($ledger_rows)) {
            $th_date = get_string('credit_history_date', 'local_mxaimanager');
            $th_amount = get_string('credit_history_amount', 'local_mxaimanager');
            $th_type = get_string('credit_history_type', 'local_mxaimanager');
            $th_expiry = get_string('credit_history_expiry', 'local_mxaimanager');
            $th_note = get_string('credit_history_note', 'local_mxaimanager');
            $ledger_html = "
                <table class='table table-sm table-striped mt-2'>
                    <thead><tr><th>{$th_date}</th><th>{$th_amount}</th><th>{$th_type}</th><th>{$th_expiry}</th><th>{$th_note}</th><th></th></tr></thead>
                    <tbody>{$ledger_rows}</tbody>
                </table>";
        }

        $html = "<div class='form-group row'>
            <div class='col-sm-12'>
                {$status_html}
                {$form_html}
                {$ledger_html}
            </div>
        </div>";

        return format_admin_setting($this, $this->visiblename, $html, $this->description);
    }
}
