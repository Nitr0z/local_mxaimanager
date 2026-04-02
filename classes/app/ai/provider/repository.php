<?php

namespace local_mxaimanager\app\ai\provider;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd


/**
 * @extends \local_mxaimanager\app\repository<entity>
 */
class repository extends \local_mxaimanager\app\repository
{
    public function get_table(): string
    {
        return 'local_mxaimanager_providers';
    }

    private function get_preconfigured_providers(): \local_mxaimanager\app\collection
    {
        global $CFG;

        $builtin = [];

        // Only include freemium provider if enabled in admin settings.
        $freemium_enabled = get_config('local_mxaimanager', 'enable_freemium');
        if (!empty($freemium_enabled)) {
            $builtin[] = (object)[
                'name' => 'Freemium',
                'classname' => \local_mxaimanager\app\ai\provider\providers\freemium::class,
                'default_unless_explicitly_set' => true,
                'api_key' => get_config('local_mxaimanager', 'freemium_api_key') ?: '',
                'base_url' => get_config('local_mxaimanager', 'freemium_base_url') ?: '',
                'chat_model' => get_config('local_mxaimanager', 'freemium_model') ?: '',
            ];
        }

        $preconfigured = array_merge($builtin, $CFG->local_mxaimanager_preconfigured_providers ?? []);
        $entities = [];
        foreach ($preconfigured as $index => $config) {
            $record = (array) $config;
            $config_json = array_diff_key($record, array_flip(['name', 'classname']));
            $record['id'] = -($index + 1);
            $record['config_json'] = json_encode($config_json);
            $record['is_preconfigured'] = true;
            $record['timecreated'] = null;
            $record['timemodified'] = null;
            $entities[] = $this->base_factory->ai()->provider()->entity($record);
        }
        return $this->base_factory->collection($entities);
    }

    public function get_by_id(int $id): entity
    {
        if ($id < 0) {
            $preconfigured = $this->get_preconfigured_providers();
            foreach ($preconfigured as $entity) {
                if ($entity->get_id() === $id) {
                    return $entity;
                }
            }
            throw new \dml_missing_record_exception($this->get_table());
        }
        return $this->base_factory->ai()->provider()->entity(
            (array)$this->db->get_record($this->get_table(), ['id' => $id], strictness: MUST_EXIST)
        );
    }

    public function get_by_name(string $name): entity
    {
        $preconfigured = $this->get_preconfigured_providers();
        foreach ($preconfigured as $entity) {
            if ($entity->get_name() === $name) {
                return $entity;
            }
        }
        return $this->base_factory->ai()->provider()->entity(
            (array)$this->db->get_record($this->get_table(), ['name' => $name], strictness: MUST_EXIST)
        );
    }

    public function get_all_by_classname(string $classname): \local_mxaimanager\app\collection
    {
        $db_entities = $this->base_factory->collection(
            array_map(
                function (object $record) {
                    return $this->base_factory->ai()->provider()->entity((array)$record);
                },
                $this->db->get_records($this->get_table(), ['classname' => $classname])
            )
        );
        $preconfigured_entities = $this->get_preconfigured_providers()->filter(static function (\local_mxaimanager\app\ai\provider\entity $entity) use ($classname) {
            return $entity->get_classname() === $classname;
        });
        return $db_entities->merge($preconfigured_entities);
    }

    public function get_all_by_classnames(array $classnames): \local_mxaimanager\app\collection
    {
        $db_entities = $this->base_factory->collection();
        if (!empty($classnames)) {
            [$in_sql, $params] = $this->db->get_in_or_equal($classnames);

            $sql = "SELECT *
                      FROM {{$this->get_table()}}
                     WHERE classname $in_sql";
            $db_entities = $this->base_factory->collection(
                array_map(
                    function (object $record) {
                        return $this->base_factory->ai()->provider()->entity((array)$record);
                    },
                    $this->db->get_records_sql($sql, $params)
                )
            );
        }
        $preconfigured_entities = $this->get_preconfigured_providers()->filter(static function (\local_mxaimanager\app\ai\provider\entity $entity) use ($classnames) {
            return in_array($entity->get_classname(), $classnames, true);
        });
        return $db_entities->merge($preconfigured_entities);
    }

    /**
     * @return \local_mxaimanager\app\collection<entity>
     * @throws \dml_exception
     */
    public function get_all(): \local_mxaimanager\app\collection
    {
        $db_entities = $this->base_factory->collection(
            array_map(
                function (object $record) {
                    return $this->base_factory->ai()->provider()->entity((array)$record);
                },
                $this->db->get_records($this->get_table())
            )
        );
        $preconfigured_entities = $this->get_preconfigured_providers();
        return $db_entities->merge($preconfigured_entities);
    }

    public function insert(entity|\local_mxaimanager\app\entity $entity): int
    {
        if ($entity->get_is_preconfigured()) {
            throw new \coding_exception('Cannot insert preconfigured providers.');
        }
        return parent::insert($entity);
    }

    public function update(entity|\local_mxaimanager\app\entity $entity): bool
    {
        if ($entity->get_is_preconfigured()) {
            throw new \coding_exception('Cannot update preconfigured providers.');
        }
        return parent::update($entity);
    }

    public function delete(int $id): bool
    {
        if ($id < 0) {
            throw new \coding_exception('Cannot delete preconfigured providers.');
        }
        return parent::delete($id);
    }
}
