<?php

namespace local_mxaimanager\app\controller;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use core\exception\coding_exception;
use local_mxaimanager\app\ai\provider\providers\provider;
use local_mxaimanager\app\factory as base_factory;
use local_mxaimanager\output\manage_providers\default_provider_form;

class manage_providers implements interfaces\view
{
    private base_factory $base_factory;
    private \moodle_url $url;
    private \moodle_page $page;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
        $this->url = new \moodle_url('/local/mxaimanager/view.php', [
            'view' => 'manage_providers'
        ]);
        $this->page = $this->base_factory->page();
    }

    public function action(string $action): string
    {
        $this->url->param('action', $action);

        switch ($action) {
            case 'browse':
                return $this->browse();
            case 'add':
                return $this->add();
            case 'edit':
                return $this->edit();
            case 'delete':
                return $this->delete();
            default:
                send_file_not_found();
        }
    }

    private function page_setup(): void
    {
        require_login();
        require_capability('local/mxaimanager:manage_configuration', \core\context\system::instance());

        $this->page->set_url($this->url);
        $this->page->set_context(\core\context\system::instance());
    }

    private function browse(): string
    {
        $this->page_setup();

        $default_provider_form = new default_provider_form($this->base_factory, $this->url);

        $default_providers = $this->base_factory->ai()->default_provider()->repository()->get_all();
        $default_provider_form->load_data($default_providers);

        if ($default_provider_form->is_cancelled()) {
            redirect($this->url);
            die();
        }

        if ($default_provider_form->is_submitted() && $default_provider_form->is_validated()) {
            /** @var object $data */
            $data = $default_provider_form->get_submitted_data();

            $actions = $this->base_factory->ai()->provider()->get_actions();
            foreach ($actions as $interface => $action) {
                if (!isset($data->$interface)) {
                    continue;
                }

                $provider_id = (int) $data->$interface;

                // If "none" selected, remove any existing default for this action.
                if ($provider_id === 0) {
                    try {
                        $existing = $this->base_factory->ai()->default_provider()->repository()
                            ->get_by_action_interface($interface);
                        $this->base_factory->ai()->default_provider()->repository()->delete($existing->get_id());
                    } catch (\dml_missing_record_exception $e) {
                        // No existing record, nothing to delete.
                    }
                    continue;
                }

                $default_provider = $this->base_factory->ai()->default_provider()->entity()
                    ->set_action_interface($interface)
                    ->set_provider_id($provider_id);

                $this->base_factory->ai()->default_provider()->repository()->insert_or_update($default_provider);
            }
        }

        $output = $this->base_factory->output()->header();
        $output .= $this->base_factory->output()->render(
            new \local_mxaimanager\output\manage_providers\browse(
                $this->base_factory, $this->url, $default_provider_form
            )
        );
        $output .= $this->base_factory->output()->footer();

        return $output;
    }

    /**
     * @throws coding_exception
     * @throws \moodle_exception
     * @throws \JsonException
     */
    public function add(): string
    {
        $this->page_setup();

        $form = new \local_mxaimanager\output\manage_providers\form(
            $this->base_factory,
            $this->url,
            show_set_as_default: true
        );

        if ($form->is_cancelled()) {
            $this->url->param('action', 'browse');
            redirect($this->url);
            die();
        }

        if ($form->is_submitted() && $form->is_validated()) {
            /** @var object $data */
            $data = $form->get_submitted_data();

            $provider_config_data = [];

            /** @var provider $provider */
            $providers = $this->base_factory->ai()->provider()->get_providers();
            foreach ($providers as $provider => $name) {
                if ($data->classname !== $provider) {
                    continue;
                }

                $prefix = $provider::get_provider_moodleform_element_prefix();

                foreach ($data as $key => $value) {
                    if (str_starts_with($key, $prefix)) {
                        $key_without_prefix = str_replace($prefix, '', $key);
                        $provider_config_data[$key_without_prefix] = $value;
                    }
                }
            }

            $time = time();
            $provider = $this->base_factory->ai()->provider()->entity()
                ->set_name($data->name)
                ->set_classname($data->classname)
                ->set_config_json(json_encode($provider_config_data, JSON_THROW_ON_ERROR))
                ->set_timecreated($time)
                ->set_timemodified($time);

            $provider->set_id(
                $this->base_factory->ai()->provider()->repository()->insert($provider)
            );

            if ($data->set_as_default) {
                $actions = $this->base_factory->ai()->provider()->get_actions();
                foreach ($actions as $interface => $action) {
                    $this->base_factory->ai()->default_provider()->repository()->insert_or_update(
                        $this->base_factory->ai()->default_provider()->entity()
                            ->set_action_interface($interface)
                            ->set_provider_id($provider->get_id())
                    );
                }
            }

            $this->url->param('action', 'browse');
            redirect($this->url);
        }

        $output = $this->base_factory->output()->header();
        $output .= $this->base_factory->output()->render(
            new \local_mxaimanager\output\manage_providers\add($this->base_factory, $form)
        );
        $output .= $this->base_factory->output()->footer();

        return $output;
    }

    /**
     * @throws coding_exception
     * @throws \moodle_exception
     * @throws \JsonException
     */
    public function edit(): string
    {
        $provider_id = required_param('id', PARAM_INT);
        $this->url->param('id', $provider_id);

        $this->page_setup();

        $provider_entity = $this->base_factory->ai()->provider()->repository()->get_by_id($provider_id);

        if (\local_mxaimanager\app\ai\feature\action_handler::is_managed_provider($provider_id)) {
            throw new \Exception('Managed providers cannot be edited.');
        }

        $form = new \local_mxaimanager\output\manage_providers\form($this->base_factory, $this->url, $provider_entity);

        if ($form->is_cancelled()) {
            $this->url->param('action', 'browse');
            redirect($this->url);
            die();
        }


        if ($form->is_submitted() && $form->is_validated()) {
            /** @var object $data */
            $data = $form->get_submitted_data();

            $provider_config_data = [];

            /** @var provider $provider */
            $providers = $this->base_factory->ai()->provider()->get_providers();
            foreach ($providers as $provider => $name) {
                if ($data->classname !== $provider) {
                    continue;
                }

                $prefix = $provider::get_provider_moodleform_element_prefix();

                foreach ($data as $key => $value) {
                    if (str_starts_with($key, $prefix)) {
                        $key_without_prefix = str_replace($prefix, '', $key);
                        $provider_config_data[$key_without_prefix] = $value;
                    }
                }
            }

            $time = time();
            $provider_entity->set_name($data->name)
                ->set_classname($data->classname)
                ->set_config_json(json_encode($provider_config_data, JSON_THROW_ON_ERROR))
                ->set_timemodified($time);

            $this->base_factory->ai()->provider()->repository()->update($provider_entity);

            $this->url->param('action', 'browse');
            redirect($this->url);
        }

        $output = $this->base_factory->output()->header();
        $output .= $this->base_factory->output()->render(
            new \local_mxaimanager\output\manage_providers\edit($this->base_factory, $form)
        );
        $output .= $this->base_factory->output()->footer();

        return $output;
    }

    public function delete(): string
    {
        $provider_id = required_param('id', PARAM_INT);
        $this->url->param('id', $provider_id);

        if (\local_mxaimanager\app\ai\feature\action_handler::is_managed_provider($provider_id)) {
            throw new \Exception('Managed providers cannot be deleted.');
        }

        $confirmed = optional_param('confirmed', 0, PARAM_INT);
        $this->url->param('confirmed', $confirmed);

        if ($confirmed) {
            $this->base_factory->ai()->provider()->repository()->delete($provider_id);
            $default_providers = $this->base_factory->ai()->default_provider()->repository()->get_all_by_provider_id(
                $provider_id
            );
            foreach ($default_providers as $default_provider) {
                $this->base_factory->ai()->default_provider()->repository()->delete($default_provider->get_id());
            }

            $this->url->param('action', 'browse');
            redirect($this->url);
            die();
        }

        $this->page_setup();

        $output = $this->base_factory->output()->header();
        $output .= $this->base_factory->output()->render(
            new \local_mxaimanager\output\manage_providers\delete($this->base_factory, $provider_id)
        );
        $output .= $this->base_factory->output()->footer();

        return $output;
    }
}
