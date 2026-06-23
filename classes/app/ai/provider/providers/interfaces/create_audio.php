<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_mxaimanager\app\ai\provider\providers\interfaces;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\create_audio_request;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;

/**
 * Provider capability contract: synthesize audio (TTS) from a text input.
 *
 * @package local_mxaimanager
 * @copyright Tresipunt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface create_audio {

    /**
     * Generate audio (text-to-speech) from a text input.
     *
     * @param string $text The text to synthesize.
     * @return create_audio_request The request DTO carrying the base64-encoded audio in `response`.
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_audio(string $text): create_audio_request;
}
