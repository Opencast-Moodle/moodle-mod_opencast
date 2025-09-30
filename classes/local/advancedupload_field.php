<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The advanced upload page field class.
 *
 * Handles field properties and provides getter/setter methods.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\local;

/**
 * The advanced upload page field class.
 *
 * Handles field properties and provides getter/setter methods.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class advancedupload_field {

    /**
     * @var string Field data type.
     */
    private $datatype;

    /**
     * @var string Field ID.
     */
    private $id;

    /**
     * @var string Field label.
     */
    private $label;

    /**
     * @var array Field parameters.
     */
    private $params = [];

    /**
     * @var array Field HTML attributes.
     */
    private $attributes = [];

    /**
     * @var mixed Default value.
     */
    private $default = null;

    /**
     * @var bool Is field required.
     */
    private $required = false;

    /**
     * @var array Sub Fields.
     */
    private $subfields = [];

    /**
     * Constructor to set field properties.
     *
     * @param string $datatype
     * @param string $id
     * @param string $label
     * @param array $params
     * @param array $attributes
     * @param mixed $default
     * @param bool $required
     */
    public function __construct(
        $datatype,
        $id,
        $label,
        $params = [],
        $attributes = [],
        $default = null,
        $required = null,
    ) {
        $this->datatype = $datatype;
        $this->id = $id;
        $this->label = $label;
        $this->params = $params;
        $this->attributes = $attributes;
        $this->default = $default;
        if (is_bool($required)) {
            $this->required = $required;
        }
    }

    /**
     * Get the field data type.
     *
     * @return string
     */
    public function get_datatype(): string {
        return $this->datatype;
    }

    /**
     * Set the field data type.
     *
     * @param string $datatype
     */
    public function set_datatype(string $datatype): void {
        $this->datatype = $datatype;
    }

    /**
     * Get the field ID.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Set the field ID.
     *
     * @param string $id
     */
    public function set_id(string $id): void {
        $this->id = $id;
    }

    /**
     * Get the field label.
     *
     * @return string
     */
    public function get_label(): string {
        return $this->label;
    }

    /**
     * Set the field label.
     *
     * @param string $label
     */
    public function set_label(string $label): void {
        $this->label = $label;
    }

    /**
     * Get the field parameters.
     *
     * @return mixed
     */
    public function get_params() {
        return $this->params;
    }

    /**
     * Set the field parameters.
     *
     * @param mixed $params
     */
    public function set_params($params): void {
        $this->params = $params;
    }

    /**
     * Get the field HTML attributes.
     *
     * @return mixed
     */
    public function get_attributes() {
        return $this->attributes;
    }

    /**
     * Set the field HTML attributes.
     *
     * @param mixed $attributes
     */
    public function set_attributes($attributes): void {
        $this->attributes = $attributes;
    }

    /**
     * Get the default value.
     *
     * @return mixed
     */
    public function get_default() {
        return $this->default;
    }

    /**
     * Set the default value.
     *
     * @param mixed $default
     */
    public function set_default($default): void {
        $this->default = $default;
    }

    /**
     * Get whether the field is required.
     *
     * @return bool
     */
    public function is_required(): bool {
        return $this->required;
    }

    /**
     * Set whether the field is required.
     *
     * @param bool $required
     */
    public function set_required(bool $required): void {
        $this->required = $required;
    }

    /**
     * Add a subfield.
     *
     * @param advancedupload_field $subfield
     */
    public function set_subfield(advancedupload_field $subfield): void {
        $this->subfields[] = $subfield;
    }

    /**
     * Get the subfields.
     *
     * @return array
     */
    public function get_subfields(): array {
        return $this->subfields;
    }
}
