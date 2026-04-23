<?php

namespace local_mxaimanager\app\ai\provider;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd


/**
 * Provider repository — all providers are stored in the database.
 * Managed providers are locked via managed_provider_ids setting (edit/delete blocked in controller).
 *
 * @extends \local_mxaimanager\app\repository<entity>
 */
class repository extends \local_mxaimanager\app\repository
{
    public function get_table(): string
    {
        return 'local_mxaimanager_providers';
    }

    public function get_by_id(int $id): entity
    {
        return $this->base_factory->ai()->provider()->entity(
            (array)$this->db->get_record($this->get_table(), ['id' => $id], strictness: MUST_EXIST)
        );
    }

    public function get_by_name(string $name): entity
    {
        return $this->base_factory->ai()->provider()->entity(
            (array)$this->db->get_record($this->get_table(), ['name' => $name], strictness: MUST_EXIST)
        );
    }

    public function get_all_by_classname(string $classname): \local_mxaimanager\app\collection
    {
        return $this->base_factory->collection(
            array_map(
                function (object $record) {
                    return $this->base_factory->ai()->provider()->entity((array)$record);
                },
                $this->db->get_records($this->get_table(), ['classname' => $classname])
            )
        );
    }

    public function get_all_by_classnames(array $classnames): \local_mxaimanager\app\collection
    {
        if (empty($classnames)) {
            return $this->base_factory->collection();
        }

        [$in_sql, $params] = $this->db->get_in_or_equal($classnames);

        $sql = "SELECT *
                  FROM {{$this->get_table()}}
                 WHERE classname $in_sql";
        return $this->base_factory->collection(
            array_map(
                function (object $record) {
                    return $this->base_factory->ai()->provider()->entity((array)$record);
                },
                $this->db->get_records_sql($sql, $params)
            )
        );
    }

    /**
     * @return \local_mxaimanager\app\collection<entity>
     * @throws \dml_exception
     */
    public function get_all(): \local_mxaimanager\app\collection
    {
        return $this->base_factory->collection(
            array_map(
                function (object $record) {
                    return $this->base_factory->ai()->provider()->entity((array)$record);
                },
                $this->db->get_records($this->get_table())
            )
        );
    }
}
