<?php

namespace local_mxaimanager\app\ai\provider;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

class entity extends \local_mxaimanager\app\entity
{
    public function get_name(): string
    {
        return $this->record['name'] ?? '';
    }

    public function set_name(string $value): self
    {
        $this->record['name'] = $value;
        return $this;
    }

    public function get_classname(): string
    {
        return $this->record['classname'] ?? '';
    }

    public function set_classname(string $value): self
    {
        $this->record['classname'] = $value;
        return $this;
    }

    public function get_config_json(): string
    {
        return $this->record['config_json'] ?? '{}';
    }

    public function set_config_json(string $value): self
    {
        $this->record['config_json'] = $value;
        return $this;
    }

    public function get_timecreated(): int
    {
        return $this->record['timecreated'] ?? 0;
    }

    public function set_timecreated(int $value): self
    {
        $this->record['timecreated'] = $value;
        return $this;
    }

    public function get_timemodified(): int
    {
        return $this->record['timemodified'] ?? 0;
    }

    public function set_timemodified(int $value): self
    {
        $this->record['timemodified'] = $value;
        return $this;
    }


    public function to_array(): array
    {
        return [
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'classname' => $this->get_classname(),
            'config_json' => $this->get_config_json(),
            'timecreated' => $this->get_timecreated(),
            'timemodified' => $this->get_timemodified(),

        ];
    }
}
