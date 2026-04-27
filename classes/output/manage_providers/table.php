<?php

namespace local_mxaimanager\output\manage_providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\factory as base_factory;

class table extends \local_mxaimanager\app\table
{
    private base_factory $base_factory;
    private \moodle_url $url;

    public function __construct(base_factory $base_factory, \moodle_url $url)
    {
        $this->base_factory = $base_factory;
        $this->url = $url;

        $headers = [];
        $columns = [];

        $columns[] = 'name';
        $headers[] = get_string('manage_providers:table:name', 'local_mxaimanager');

        $columns[] = 'classname';
        $headers[] = get_string('manage_providers:table:classname', 'local_mxaimanager');

        $columns[] = 'supported_actions';
        $headers[] = get_string('manage_providers:table:supported_actions', 'local_mxaimanager');
        $this->no_sorting('supported_actions');

        $columns[] = 'actions';
        $headers[] = get_string('manage_providers:table:actions', 'local_mxaimanager');
        $this->no_sorting('actions');

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);

        $this->set_sortdata([]);

        parent::__construct($url, "manage_providers_table");
    }

    public function query_db($pagesize, $useinitialsbar = true)
    {
        $providers = $this->base_factory->ai()->provider()->repository()->get_all();
        foreach ($providers as $provider) {
            $this->add_data_keyed([
                'name' => $provider->get_name(),
                'classname' => $this->col_classname((object)$provider->to_array()),
                'supported_actions' => $this->col_supported_actions((object)$provider->to_array()),
                'actions' => $this->col_actions((object)$provider->to_array()),
            ]);
        }
    }

    protected function col_classname(object $record): string
    {
        $providers = $this->base_factory->ai()->provider()->get_providers();

        foreach ($providers as $provider => $name) {
            if ($record->classname === $provider) {
                return $name;
            }
        }

        return '';
    }

    protected function col_supported_actions(object $record): string
    {
        return $this->base_factory->output()->render(
            new form_provider_supports($record->classname)
        );
    }

    protected function col_actions(object $record): string
    {
        return $this->base_factory->output()->render(
            new table_actions($record->id)
        );
    }
}
