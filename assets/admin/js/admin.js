(function () {
    var config = window.pluginoraAdmin || null;

    function request(path, options) {
        var settings = options || {};
        var headers = settings.headers || {};

        headers['X-WP-Nonce'] = config.nonce;

        if (settings.body && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }

        return fetch(config.restBase + path.replace(/^\//, ''), {
            method: settings.method || 'GET',
            headers: headers,
            body: settings.body ? JSON.stringify(settings.body) : undefined,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (payload) {
                if (!response.ok) {
                    var message = payload.message || config.strings.loadError;
                    throw new Error(message);
                }

                return payload;
            });
        });
    }

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function createInitialState() {
        return {
            schema: null,
            rules: [],
            module: '',
            ruleType: '',
            formData: {},
            editingId: null,
            loading: true,
            saving: false,
            notice: null,
            lookupResults: {},
            lookupTimers: {}
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('pluginora-admin-app');

        if (!root || !config) {
            return;
        }

        var state = createInitialState();

        function showNotice(type, message) {
            state.notice = { type: type, message: message };
            render();
        }

        function normalizeSelection(values) {
            if (!Array.isArray(values)) {
                return [];
            }

            return values.map(function (item) {
                if (typeof item === 'object' && item !== null) {
                    return item;
                }

                return { id: Number(item), label: '#' + String(item) };
            });
        }

        function getDefaults() {
            return clone((state.schema && state.schema.defaults) || {});
        }

        function getTypeDefinition() {
            if (!state.schema || !state.module || !state.ruleType) {
                return null;
            }

            var types = state.schema.types[state.module] || [];

            return types.find(function (type) {
                return type.slug === state.ruleType;
            }) || null;
        }

        function getVisibleFields() {
            var definition = getTypeDefinition();

            if (!definition) {
                return [];
            }

            return definition.fields.filter(function (field) {
                if (!field.depends_on) {
                    return true;
                }

                var dependencyValue = state.formData[field.depends_on.field];

                if (Array.isArray(dependencyValue)) {
                    return dependencyValue.some(function (value) {
                        return field.depends_on.values.indexOf(value) !== -1;
                    });
                }

                return field.depends_on.values.indexOf(dependencyValue) !== -1;
            });
        }

        function resetForm(module) {
            state.module = module || '';
            state.ruleType = '';
            state.formData = getDefaults();
            state.editingId = null;
            state.lookupResults = {};
        }

        function hydrateForm(rule) {
            state.editingId = rule.id;
            state.module = rule.module;
            state.ruleType = rule.rule_type;
            state.formData = Object.assign(getDefaults(), clone(rule));
            state.formData.selected_products = normalizeSelection(rule.selected_products || []);
            state.formData.selected_categories = normalizeSelection(rule.selected_categories || []);
            state.formData.excluded_products = normalizeSelection(rule.excluded_products || []);
            state.formData.buy_product_id = rule.buy_product_id ? { id: Number(rule.buy_product_id), label: '#' + String(rule.buy_product_id) } : null;
            state.formData.get_product_id = rule.get_product_id ? { id: Number(rule.get_product_id), label: '#' + String(rule.get_product_id) } : null;
        }

        function serializeFormData() {
            var payload = clone(state.formData);

            payload.module = state.module;
            payload.rule_type = state.ruleType;
            payload.selected_products = normalizeSelection(payload.selected_products).map(function (item) {
                return item.id;
            });
            payload.selected_categories = normalizeSelection(payload.selected_categories).map(function (item) {
                return item.id;
            });
            payload.excluded_products = normalizeSelection(payload.excluded_products).map(function (item) {
                return item.id;
            });
            payload.buy_product_id = payload.buy_product_id && payload.buy_product_id.id ? payload.buy_product_id.id : 0;
            payload.get_product_id = payload.get_product_id && payload.get_product_id.id ? payload.get_product_id.id : 0;

            return payload;
        }

        function loadRule(id) {
            request('rules/' + id).then(function (response) {
                hydrateForm(response.item);
                render();
            }).catch(function (error) {
                showNotice('error', error.message);
            });
        }

        function loadData() {
            state.loading = true;
            render();

            Promise.all([
                request('builder/schema'),
                request('rules')
            ]).then(function (responses) {
                state.schema = responses[0];
                state.rules = responses[1].items || [];
                state.formData = getDefaults();
                state.loading = false;
                render();
            }).catch(function (error) {
                state.loading = false;
                showNotice('error', error.message);
            });
        }

        function saveRule() {
            state.saving = true;
            render();

            var method = state.editingId ? 'POST' : 'POST';
            var path = state.editingId ? 'rules/' + state.editingId : 'rules';

            request(path, {
                method: method,
                body: serializeFormData(),
                headers: state.editingId ? { 'X-HTTP-Method-Override': 'PUT' } : {}
            }).then(function () {
                state.saving = false;
                resetForm('');
                showNotice('success', config.strings.saveSuccess);
                return request('rules');
            }).then(function (response) {
                state.rules = response.items || [];
                render();
            }).catch(function (error) {
                state.saving = false;
                showNotice('error', error.message);
            });
        }

        function actionRule(id, action, confirmMessage) {
            if (confirmMessage && !window.confirm(confirmMessage)) {
                return;
            }

            var options = { method: 'POST' };
            var path = 'rules/' + id + '/' + action;

            if ('delete' === action) {
                path = 'rules/' + id;
                options = { method: 'DELETE' };
            }

            request(path, options).then(function () {
                if (state.editingId === id && 'delete' === action) {
                    resetForm('');
                }

                return request('rules');
            }).then(function (response) {
                state.rules = response.items || [];
                render();
            }).catch(function (error) {
                showNotice('error', error.message);
            });
        }

        function updateField(field, value, shouldRender) {
            state.formData[field] = value;

            if (shouldRender !== false) {
                render();
            }
        }

        function lookupSearch(fieldKey, lookupType, query) {
            if (!query || query.length < 2) {
                state.lookupResults[fieldKey] = [];
                render();
                return;
            }

            window.clearTimeout(state.lookupTimers[fieldKey]);
            state.lookupTimers[fieldKey] = window.setTimeout(function () {
                request('lookups/' + lookupType + '?search=' + encodeURIComponent(query)).then(function (response) {
                    state.lookupResults[fieldKey] = response.items || [];
                    render();
                }).catch(function (error) {
                    showNotice('error', error.message);
                });
            }, 250);
        }

        function addLookupItem(fieldKey, item, multiple) {
            if (multiple) {
                var values = normalizeSelection(state.formData[fieldKey] || []);
                if (!values.some(function (selectedItem) { return selectedItem.id === item.id; })) {
                    values.push(item);
                }
                state.formData[fieldKey] = values;
            } else {
                state.formData[fieldKey] = item;
            }

            state.lookupResults[fieldKey] = [];
            render();
        }

        function removeLookupItem(fieldKey, itemId) {
            if (Array.isArray(state.formData[fieldKey])) {
                state.formData[fieldKey] = normalizeSelection(state.formData[fieldKey]).filter(function (item) {
                    return item.id !== itemId;
                });
            } else if (state.formData[fieldKey] && state.formData[fieldKey].id === itemId) {
                state.formData[fieldKey] = null;
            }

            render();
        }

        function addTier() {
            var tiers = Array.isArray(state.formData.tiers) ? state.formData.tiers.slice() : [];
            tiers.push({ min_qty: 1, max_qty: '', discount_type: 'percentage', discount_value: 0 });
            state.formData.tiers = tiers;
            render();
        }

        function removeTier(index) {
            state.formData.tiers = (state.formData.tiers || []).filter(function (_, tierIndex) {
                return tierIndex !== index;
            });
            render();
        }

        function updateTier(index, key, value) {
            var tiers = Array.isArray(state.formData.tiers) ? state.formData.tiers.slice() : [];
            tiers[index][key] = value;
            state.formData.tiers = tiers;
        }

        function renderLookupField(field, multiple) {
            var values = multiple ? normalizeSelection(state.formData[field.key] || []) : (state.formData[field.key] ? [state.formData[field.key]] : []);
            var results = state.lookupResults[field.key] || [];

            return [
                '<div class="pluginora-field is-full">',
                '<label>' + field.label + '</label>',
                '<input type="search" data-lookup-field="' + field.key + '" data-lookup-type="' + field.lookup + '" placeholder="' + config.strings.searchPlaceholder + '" />',
                results.length ? '<div class="pluginora-lookup-results">' + results.map(function (item) {
                    return '<button type="button" class="pluginora-lookup-option" data-action="lookup-add" data-field="' + field.key + '" data-multiple="' + (multiple ? '1' : '0') + '" data-id="' + item.id + '" data-label="' + escapeHtml(item.label) + '">' + escapeHtml(item.label) + '</button>';
                }).join('') + '</div>' : '',
                values.length ? '<div class="pluginora-chip-list">' + values.map(function (item) {
                    return '<span class="pluginora-chip">' + escapeHtml(item.label || ('#' + item.id)) + '<button type="button" data-action="lookup-remove" data-field="' + field.key + '" data-id="' + item.id + '">×</button></span>';
                }).join('') + '</div>' : '',
                '</div>'
            ].join('');
        }

        function renderField(field) {
            var value = state.formData[field.key];
            var help = field.help ? '<span class="pluginora-field-help">' + field.help + '</span>' : '';

            if ('lookup-multi' === field.type) {
                return renderLookupField(field, true);
            }

            if ('lookup-single' === field.type) {
                return renderLookupField(field, false);
            }

            if ('textarea' === field.type) {
                return '<div class="pluginora-field is-full"><label>' + field.label + '</label><textarea data-field="' + field.key + '">' + escapeHtml(value || '') + '</textarea>' + help + '</div>';
            }

            if ('select' === field.type) {
                return '<div class="pluginora-field"><label>' + field.label + '</label><select data-field="' + field.key + '">' + (field.options || []).map(function (option) {
                    return '<option value="' + option.value + '"' + (option.value === value ? ' selected' : '') + '>' + option.label + '</option>';
                }).join('') + '</select>' + help + '</div>';
            }

            if ('checkbox' === field.type) {
                return '<div class="pluginora-field"><label><input type="checkbox" data-field="' + field.key + '"' + (value ? ' checked' : '') + ' /> ' + field.label + '</label>' + help + '</div>';
            }

            if ('checkbox-group' === field.type) {
                var checkedValues = Array.isArray(value) ? value : [];

                return '<div class="pluginora-field is-full"><label>' + field.label + '</label><div class="pluginora-checkbox-group">' + (field.options || []).map(function (option) {
                    var checked = checkedValues.indexOf(option.value) !== -1;
                    return '<label><input type="checkbox" data-checkbox-group="' + field.key + '" value="' + option.value + '"' + (checked ? ' checked' : '') + ' /> ' + option.label + '</label>';
                }).join('') + '</div>' + help + '</div>';
            }

            if ('tier-repeater' === field.type) {
                var tiers = Array.isArray(state.formData.tiers) ? state.formData.tiers : [];

                return '<div class="pluginora-field is-full"><label>' + field.label + '</label>' + tiers.map(function (tier, index) {
                    return '<div class="pluginora-tier-row">'
                        + '<input type="number" min="1" value="' + (tier.min_qty || 1) + '" data-tier-index="' + index + '" data-tier-field="min_qty" />'
                        + '<input type="number" min="1" value="' + (tier.max_qty || '') + '" data-tier-index="' + index + '" data-tier-field="max_qty" placeholder="Max qty" />'
                        + '<select data-tier-index="' + index + '" data-tier-field="discount_type"><option value="percentage"' + (tier.discount_type === 'percentage' ? ' selected' : '') + '>Percentage</option><option value="fixed"' + (tier.discount_type === 'fixed' ? ' selected' : '') + '>Fixed</option></select>'
                        + '<input type="number" min="0" step="0.01" value="' + (tier.discount_value || 0) + '" data-tier-index="' + index + '" data-tier-field="discount_value" />'
                        + '<button type="button" class="button button-link-delete" data-action="remove-tier" data-tier-index="' + index + '">Remove</button>'
                        + '</div>';
                }).join('') + '<button type="button" class="button" data-action="add-tier">Add Tier</button>' + help + '</div>';
            }

            var inputType = 'datetime' === field.type ? 'datetime-local' : ('number' === field.type ? 'number' : 'text');
            var attributes = [];

            if ('number' === field.type) {
                if (typeof field.min !== 'undefined') {
                    attributes.push('min="' + field.min + '"');
                }

                if (field.step) {
                    attributes.push('step="' + field.step + '"');
                }
            }

            return '<div class="pluginora-field"><label>' + field.label + '</label><input type="' + inputType + '" data-field="' + field.key + '" value="' + escapeHtml(value || '') + '" ' + attributes.join(' ') + ' />' + help + '</div>';
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderSections() {
            var sections = {};
            var fields = getVisibleFields();

            fields.forEach(function (field) {
                if (!sections[field.section]) {
                    sections[field.section] = [];
                }

                sections[field.section].push(field);
            });

            return Object.keys(sections).map(function (sectionKey) {
                return '<section class="pluginora-section"><h3>' + escapeHtml(sectionTitle(sectionKey)) + '</h3><div class="pluginora-field-grid">' + sections[sectionKey].map(renderField).join('') + '</div></section>';
            }).join('');
        }

        function sectionTitle(key) {
            var titles = {
                basics: 'Basics',
                targeting: 'Targeting',
                discount: 'Discount',
                conditions: 'Conditions',
                display: 'Display',
                schedule: 'Schedule',
                coupon: 'Coupon'
            };

            return titles[key] || key;
        }

        function renderRuleTable() {
            if (!state.rules.length) {
                return '<p class="pluginora-muted">No rules yet.</p>';
            }

            return '<table class="pluginora-list-table"><thead><tr><th>Name</th><th>Module</th><th>Type</th><th>Status</th><th>Priority</th><th>Actions</th></tr></thead><tbody>' + state.rules.map(function (rule) {
                return '<tr>'
                    + '<td><strong>' + escapeHtml(rule.name) + '</strong></td>'
                    + '<td>' + escapeHtml(rule.module.replace('_', ' ')) + '</td>'
                    + '<td>' + escapeHtml(rule.rule_type.replace(/_/g, ' ')) + '</td>'
                    + '<td>' + escapeHtml(rule.status) + '</td>'
                    + '<td>' + escapeHtml(rule.priority) + '</td>'
                    + '<td><div class="pluginora-list-actions">'
                    + '<button type="button" class="button button-secondary" data-action="edit" data-id="' + rule.id + '">Edit</button>'
                    + '<button type="button" class="button button-secondary" data-action="duplicate" data-id="' + rule.id + '">Duplicate</button>'
                    + ('active' === rule.status
                        ? '<button type="button" class="button button-secondary" data-action="deactivate" data-id="' + rule.id + '">Deactivate</button>'
                        : '<button type="button" class="button button-secondary" data-action="activate" data-id="' + rule.id + '">Activate</button>')
                    + '<button type="button" class="button button-link-delete" data-action="delete" data-id="' + rule.id + '">Delete</button>'
                    + '</div></td>'
                    + '</tr>';
            }).join('') + '</tbody></table>';
        }

        function render() {
            if (state.loading) {
                root.innerHTML = '<p>' + escapeHtml(config.strings.loading) + '</p>';
                return;
            }

            var familyCards = (state.schema.families || []).map(function (family) {
                var active = family.slug === state.module ? ' is-active' : '';
                return '<button type="button" class="pluginora-family-card' + active + '" data-action="select-family" data-family="' + family.slug + '"><h3>' + escapeHtml(family.label) + '</h3><p>' + escapeHtml(family.description) + '</p></button>';
            }).join('');

            var typeCards = state.module ? ((state.schema.types[state.module] || []).map(function (type) {
                var active = type.slug === state.ruleType ? ' is-active' : '';
                return '<button type="button" class="pluginora-type-card' + active + '" data-action="select-type" data-type="' + type.slug + '"><h3>' + escapeHtml(type.label) + '</h3><p>' + escapeHtml(type.description) + '</p></button>';
            }).join('')) : '';

            var notice = state.notice ? '<div class="pluginora-admin-notice is-' + state.notice.type + '">' + escapeHtml(state.notice.message) + '</div>' : '';

            root.innerHTML = notice
                + '<div class="pluginora-admin-grid">'
                + '<div class="pluginora-builder-card">'
                + '<h2>Guided Rule Builder</h2>'
                + '<div class="pluginora-family-grid">' + familyCards + '</div>'
                + (state.module ? '<div class="pluginora-type-grid">' + typeCards + '</div>' : '')
                + (state.ruleType ? renderSections() + '<div class="pluginora-actions"><button type="button" class="button button-primary" data-action="save">' + escapeHtml(state.editingId ? config.strings.update : config.strings.save) + '</button><button type="button" class="button button-secondary" data-action="cancel-form">' + escapeHtml(config.strings.cancel) + '</button></div>' : '<p class="pluginora-muted">Choose a rule type to continue.</p>')
                + '</div>'
                + '<div class="pluginora-list-card"><h2>' + escapeHtml(config.strings.existingRules) + '</h2>' + renderRuleTable() + '</div>'
                + '</div>';
        }

        root.addEventListener('click', function (event) {
            var target = event.target.closest('[data-action]');

            if (!target) {
                return;
            }

            var action = target.getAttribute('data-action');

            if ('select-family' === action) {
                resetForm(target.getAttribute('data-family'));
                render();
                return;
            }

            if ('select-type' === action) {
                state.ruleType = target.getAttribute('data-type');
                state.formData = Object.assign(getDefaults(), state.formData, { module: state.module, rule_type: state.ruleType });
                render();
                return;
            }

            if ('save' === action) {
                saveRule();
                return;
            }

            if ('cancel-form' === action) {
                resetForm('');
                render();
                return;
            }

            if ('edit' === action) {
                loadRule(Number(target.getAttribute('data-id')));
                return;
            }

            if ('duplicate' === action || 'activate' === action || 'deactivate' === action) {
                actionRule(Number(target.getAttribute('data-id')), action);
                return;
            }

            if ('delete' === action) {
                actionRule(Number(target.getAttribute('data-id')), action, config.strings.deleteConfirm);
                return;
            }

            if ('lookup-add' === action) {
                addLookupItem(
                    target.getAttribute('data-field'),
                    {
                        id: Number(target.getAttribute('data-id')),
                        label: target.getAttribute('data-label')
                    },
                    '1' === target.getAttribute('data-multiple')
                );
                return;
            }

            if ('lookup-remove' === action) {
                removeLookupItem(target.getAttribute('data-field'), Number(target.getAttribute('data-id')));
                return;
            }

            if ('add-tier' === action) {
                addTier();
                return;
            }

            if ('remove-tier' === action) {
                removeTier(Number(target.getAttribute('data-tier-index')));
            }
        });

        root.addEventListener('input', function (event) {
            var lookupField = event.target.getAttribute('data-lookup-field');

            if (lookupField) {
                lookupSearch(lookupField, event.target.getAttribute('data-lookup-type'), event.target.value);
                return;
            }

            var field = event.target.getAttribute('data-field');

            if (field && event.target.type !== 'checkbox') {
                updateField(field, event.target.value, false);
                return;
            }

            var tierField = event.target.getAttribute('data-tier-field');

            if (tierField) {
                updateTier(Number(event.target.getAttribute('data-tier-index')), tierField, event.target.value);
            }
        });

        root.addEventListener('change', function (event) {
            var field = event.target.getAttribute('data-field');

            if (field && event.target.type === 'checkbox') {
                updateField(field, event.target.checked);
                return;
            }

            if (field) {
                updateField(field, event.target.value);
                return;
            }

            var groupField = event.target.getAttribute('data-checkbox-group');

            if (groupField) {
                var existing = Array.isArray(state.formData[groupField]) ? state.formData[groupField].slice() : [];
                var value = event.target.value;
                var next = event.target.checked ? existing.concat([value]) : existing.filter(function (item) { return item !== value; });
                updateField(groupField, Array.from(new Set(next)));
                return;
            }

            var tierField = event.target.getAttribute('data-tier-field');

            if (tierField) {
                updateTier(Number(event.target.getAttribute('data-tier-index')), tierField, event.target.value);
                render();
            }
        });

        loadData();
    });
}());