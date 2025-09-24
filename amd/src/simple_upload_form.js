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
 * Javascript module for the simple uplaod page.
 *
 * @module     mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni (elan e.V.) (zamani@elan-ev.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {eventTypes} from 'core/local/inplace_editable/events';

export const init = (elementitemid) => {
    document.addEventListener(eventTypes.elementUpdated, e => {
        const inplaceEditable = e.target;
        const formId = inplaceEditable.closest('form').getAttribute('id');
        if (inplaceEditable.getAttribute('data-itemid') == elementitemid) {
            const newValue = inplaceEditable.getAttribute('data-value');
            document.getElementsByName('ocinstance')[0].value = newValue;
            if (formId && M.form) {
                M.form.updateFormState(formId);
            }
        }
    });
};
