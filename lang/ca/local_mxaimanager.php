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

/**
 * Catalan strings for the Moxis AI Manager plugin.
 *
 * Only contains the keys introduced by the TTS extension (ticket
 * tiny_courseaiaudio-2). The rest of the plugin was not translated to Catalan
 * prior to this change; that gap is preexisting and out of scope for this ticket.
 *
 * @package local_mxaimanager
 * @copyright Tresipunt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['default_tts_format'] = 'Format Text-to-Speech per defecte';
$string['default_tts_model'] = 'Model Text-to-Speech per defecte';
$string['default_tts_voice'] = 'Veu Text-to-Speech per defecte';
$string['openai_tts_format'] = 'Format Text-to-Speech d\'OpenAI';
$string['openai_tts_format_help'] = 'Format de contenidor d\'àudio retornat per OpenAI. <strong>mp3</strong> és l\'opció més segura per a l\'etiqueta HTML5 audio; també es suporten <strong>opus</strong>, <strong>aac</strong>, <strong>flac</strong>, <strong>wav</strong> i <strong>pcm</strong>.';
$string['openai_tts_model'] = 'Model Text-to-Speech d\'OpenAI';
$string['openai_tts_model_help'] = 'Aquí pots especificar el model TTS (text-to-speech) que s\'ha d\'utilitzar. Per exemple: <strong>tts-1</strong>, <strong>tts-1-hd</strong>. Consulta la documentació d\'OpenAI per veure els models disponibles.';
$string['openai_tts_voice'] = 'Veu Text-to-Speech d\'OpenAI';
$string['openai_tts_voice_help'] = 'Veu utilitzada per sintetitzar l\'àudio. OpenAI suporta actualment: <strong>alloy</strong>, <strong>echo</strong>, <strong>fable</strong>, <strong>onyx</strong>, <strong>nova</strong>, <strong>shimmer</strong>.';
$string['supports_tts'] = 'Suporta Text-to-Speech';
$string['uses_tts'] = 'Text-to-Speech';
