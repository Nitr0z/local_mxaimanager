<?php

namespace local_mxaimanager\app\exceptions;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class quota_exceeded_exception extends \moodle_exception
{
    /**
     * @param string $period  'daily', 'weekly', 'monthly', 'expired', or 'depleted'
     * @param string $type    'input', 'output', or 'credits'
     */
    public function __construct(string $period = 'monthly', string $type = 'input')
    {
        $message_key = "quota_exceeded_{$period}_{$type}";

        // Use a clean title key as the errorcode (shown as dialog heading),
        // and pass the specific user message via $a so users see a friendly
        // message instead of a raw error code + stack trace.
        // The specific key is passed as debuginfo for developer diagnostics.
        parent::__construct(
            'quota_exceeded_title',       // errorcode - used as dialog title
            'local_mxaimanager',          // module
            '',                           // link
            get_string($message_key, 'local_mxaimanager'), // $a - injected into the title string
            $message_key                  // debuginfo - visible only in debug mode
        );
    }
}
