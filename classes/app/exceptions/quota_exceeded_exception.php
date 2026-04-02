<?php

namespace local_mxaimanager\app\exceptions;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class quota_exceeded_exception extends \moodle_exception
{
    /**
     * @param string $period  'daily', 'weekly', or 'monthly'
     * @param string $type    'input' or 'output'
     */
    public function __construct(string $period = 'monthly', string $type = 'input')
    {
        $key = "quota_exceeded_{$period}_{$type}";
        parent::__construct($key, 'local_mxaimanager');
    }
}
