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
            lookupTimers: {},
            lookupQueries: {},
            lookupFocusField: null,
            ruleSearch: '',
            ruleStatus: 'all',
            librarySearchFocused: false
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

        function formatSlugLabel(value) {
            return String(value || '')
                .split('_')
                .map(function (segment) {
                    return segment ? segment.charAt(0).toUpperCase() + segment.slice(1) : '';
                })
                .join(' ');
        }

        function getRulesSummary() {
            return {
                total: state.rules.length,
                active: state.rules.filter(function (rule) { return 'active' === rule.status; }).length,
                inactive: state.rules.filter(function (rule) { return 'inactive' === rule.status; }).length,
                liveModules: Array.from(new Set(state.rules.map(function (rule) { return rule.module; }))).length
            };
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

        function getFilteredRules() {
            var query = state.ruleSearch.trim().toLowerCase();

            return state.rules.filter(function (rule) {
                var matchesStatus = 'all' === state.ruleStatus || rule.status === state.ruleStatus;

                if (!matchesStatus) {
                    return false;
                }

                if (!query) {
                    return true;
                }

                return [rule.name, rule.module, rule.rule_type, rule.status]
                    .join(' ')
                    .toLowerCase()
                    .indexOf(query) !== -1;
            });
        }

        function resetForm(module) {
            state.module = module || '';
            state.ruleType = '';
            state.formData = getDefaults();
            state.editingId = null;
            state.lookupResults = {};
            state.lookupQueries = {};
            state.lookupFocusField = null;
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
            state.lookupQueries[fieldKey] = query;
            state.lookupFocusField = fieldKey;

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
            state.lookupQueries[fieldKey] = '';
            state.lookupFocusField = null;
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
            var query = state.lookupQueries[field.key] || '';
            var emptyState = query.length >= 2 && !results.length
                ? '<div class="pluginora-lookup-empty">No matches found. Try a broader search.</div>'
                : '';

            return [
                '<div class="pluginora-field is-full">',
                '<label>' + field.label + '</label>',
                '<input type="search" data-lookup-field="' + field.key + '" data-lookup-type="' + field.lookup + '" value="' + escapeHtml(query) + '" placeholder="' + config.strings.searchPlaceholder + '" />',
                results.length ? '<div class="pluginora-lookup-results">' + results.map(function (item) {
                    return '<button type="button" class="pluginora-lookup-option" data-action="lookup-add" data-field="' + field.key + '" data-multiple="' + (multiple ? '1' : '0') + '" data-id="' + item.id + '" data-label="' + escapeHtml(item.label) + '">' + escapeHtml(item.label) + '</button>';
                }).join('') + '</div>' : '',
                emptyState,
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
                return '<section class="pluginora-section"><div class="pluginora-section__header"><h3>' + escapeHtml(sectionTitle(sectionKey)) + '</h3><p>' + escapeHtml(sectionDescription(sectionKey)) + '</p></div><div class="pluginora-field-grid">' + sections[sectionKey].map(renderField).join('') + '</div></section>';
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

        function sectionDescription(key) {
            var descriptions = {
                basics: 'Name the rule, set its state, and define its execution order.',
                targeting: 'Choose where the promotion should apply across products or categories.',
                discount: 'Define the pricing or savings logic customers will see.',
                conditions: 'Control when the promotion becomes eligible.',
                display: 'Decide how the offer should appear on the storefront.',
                schedule: 'Schedule start and end dates for campaign control.',
                coupon: 'Configure coupon behavior, code, and redemption settings.'
            };

            return descriptions[key] || 'Configure this section before saving the rule.';
        }

        function getCurrentSelectionSummary() {
            var family = (state.schema && state.schema.families || []).find(function (item) {
                return item.slug === state.module;
            });
            var type = getTypeDefinition();

            return {
                family: family ? family.label : 'Not selected',
                type: type ? type.label : 'Not selected',
                status: state.formData.status || 'inactive',
                priority: state.formData.priority || 1
            };
        }

        function renderBuilderOverview() {
            var selection = getCurrentSelectionSummary();

            return '<div class="pluginora-workspace-overview">'
                + '<div class="pluginora-overview-stat"><span>Family</span><strong>' + escapeHtml(selection.family) + '</strong></div>'
                + '<div class="pluginora-overview-stat"><span>Rule Type</span><strong>' + escapeHtml(selection.type) + '</strong></div>'
                + '<div class="pluginora-overview-stat"><span>Status</span><strong>' + escapeHtml(formatSlugLabel(selection.status)) + '</strong></div>'
                + '<div class="pluginora-overview-stat"><span>Priority</span><strong>' + escapeHtml(selection.priority) + '</strong></div>'
                + '</div>';
        }

        function renderProgress() {
            var steps = [
                {
                    title: 'Choose Family',
                    description: 'Start with the promotion engine you need.',
                    state: state.module ? 'complete' : 'current'
                },
                {
                    title: 'Choose Rule Type',
                    description: 'Select the exact campaign pattern to create.',
                    state: state.ruleType ? 'complete' : (state.module ? 'current' : 'upcoming')
                },
                {
                    title: 'Configure Details',
                    description: 'Set targeting, savings, and activation details.',
                    state: state.ruleType ? 'current' : 'upcoming'
                }
            ];

            return '<div class="pluginora-progress">' + steps.map(function (step, index) {
                return '<div class="pluginora-progress-step is-' + step.state + '">'
                    + '<span class="pluginora-progress-step__index">' + (index + 1) + '</span>'
                    + '<div><strong>' + escapeHtml(step.title) + '</strong><p>' + escapeHtml(step.description) + '</p></div>'
                    + '</div>';
            }).join('') + '</div>';
        }

        function renderHero() {
            var summary = getRulesSummary();

            return '<section class="pluginora-workspace-hero">'
                + '<div class="pluginora-workspace-hero__copy">'
                + '<span class="pluginora-workspace-kicker">Campaign workspace</span>'
                + '<h2>Launch promotions with less guesswork.</h2>'
                + '<p>Design pricing and coupon campaigns from a cleaner workspace with guided creation, faster rule lookup, and clearer rule operations.</p>'
                + '</div>'
                + '<div class="pluginora-metrics">'
                + '<div class="pluginora-metric"><span>Total Rules</span><strong>' + escapeHtml(summary.total) + '</strong></div>'
                + '<div class="pluginora-metric"><span>Active</span><strong>' + escapeHtml(summary.active) + '</strong></div>'
                + '<div class="pluginora-metric"><span>Inactive</span><strong>' + escapeHtml(summary.inactive) + '</strong></div>'
                + '<div class="pluginora-metric"><span>Modules</span><strong>' + escapeHtml(summary.liveModules || 0) + '</strong></div>'
                + '</div>'
                + '</section>';
        }

        function getStatusTone(status) {
            return 'active' === status ? 'success' : 'neutral';
        }

        function renderRuleLibrary() {
            var rules = getFilteredRules();

            return '<div class="pluginora-library">'
                + '<div class="pluginora-library__toolbar">'
                + '<input type="search" class="pluginora-library__search" data-library-search="1" placeholder="Search rules by name, module, or type" value="' + escapeHtml(state.ruleSearch) + '" />'
                + '<select class="pluginora-library__filter" data-library-status="1">'
                + '<option value="all"' + ('all' === state.ruleStatus ? ' selected' : '') + '>All statuses</option>'
                + '<option value="active"' + ('active' === state.ruleStatus ? ' selected' : '') + '>Active</option>'
                + '<option value="inactive"' + ('inactive' === state.ruleStatus ? ' selected' : '') + '>Inactive</option>'
                + '</select>'
                + '</div>'
                + (rules.length ? '<div class="pluginora-rule-list">' + rules.map(function (rule) {
                    return '<article class="pluginora-rule-card">'
                        + '<div class="pluginora-rule-card__top">'
                        + '<div><h3>' + escapeHtml(rule.name) + '</h3><p>' + escapeHtml(formatSlugLabel(rule.module)) + ' / ' + escapeHtml(formatSlugLabel(rule.rule_type)) + '</p></div>'
                        + '<div class="pluginora-rule-card__badges">'
                        + '<span class="pluginora-badge is-' + getStatusTone(rule.status) + '">' + escapeHtml(formatSlugLabel(rule.status)) + '</span>'
                        + '<span class="pluginora-badge is-outline">Priority ' + escapeHtml(rule.priority) + '</span>'
                        + '</div>'
                        + '</div>'
                        + '<div class="pluginora-list-actions">'
                        + '<button type="button" class="button button-secondary" data-action="edit" data-id="' + rule.id + '">Edit</button>'
                        + '<button type="button" class="button button-secondary" data-action="duplicate" data-id="' + rule.id + '">Duplicate</button>'
                        + ('active' === rule.status
                            ? '<button type="button" class="button button-secondary" data-action="deactivate" data-id="' + rule.id + '">Deactivate</button>'
                            : '<button type="button" class="button button-secondary" data-action="activate" data-id="' + rule.id + '">Activate</button>')
                        + '<button type="button" class="button button-link-delete" data-action="delete" data-id="' + rule.id + '">Delete</button>'
                        + '</div>'
                        + '</article>';
                }).join('') + '</div>' : '<div class="pluginora-empty-state"><h3>No rules match this view.</h3><p>Try clearing the search, changing the status filter, or creating your first promotion rule.</p></div>')
                + '</div>';
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
                + '<div class="pluginora-shell">'
                + renderHero()
                + '<div class="pluginora-admin-grid">'
                + '<div class="pluginora-builder-card">'
                + '<div class="pluginora-builder-header"><div><span class="pluginora-builder-header__eyebrow">Guided Rule Builder</span><h2>Create and refine promotions</h2><p>Move through the workflow in order, then save only when targeting and discount behavior are fully defined.</p></div></div>'
                + renderBuilderOverview()
                + renderProgress()
                + '<section class="pluginora-stage"><div class="pluginora-stage__header"><h3>Promotion Family</h3><p>Choose the engine that best matches the campaign you want to launch.</p></div>'
                + '<div class="pluginora-family-grid">' + familyCards + '</div>'
                + '</section>'
                + (state.module ? '<section class="pluginora-stage"><div class="pluginora-stage__header"><h3>Rule Type</h3><p>Select the exact pricing or coupon pattern you want to configure.</p></div><div class="pluginora-type-grid">' + typeCards + '</div></section>' : '')
                + (state.ruleType ? '<section class="pluginora-stage"><div class="pluginora-stage__header"><h3>Configuration</h3><p>Complete the rule details below, then save when the summary reflects the intended setup.</p></div>' + renderSections() + '<div class="pluginora-actions"><button type="button" class="button button-primary" data-action="save">' + escapeHtml(state.editingId ? config.strings.update : config.strings.save) + '</button><button type="button" class="button button-secondary" data-action="cancel-form">' + escapeHtml(config.strings.cancel) + '</button></div></section>' : '<div class="pluginora-empty-state"><h3>Choose a rule type to continue.</h3><p>Once you select a family and rule type, Pluginora will show only the fields needed for that promotion pattern.</p></div>')
                + '</div>'
                + '<aside class="pluginora-list-card"><div class="pluginora-list-card__header"><div><span class="pluginora-builder-header__eyebrow">Rule Library</span><h2>' + escapeHtml(config.strings.existingRules) + '</h2><p>Review, filter, and operate on existing promotions without leaving the workspace.</p></div></div>' + renderRuleLibrary() + '</aside>'
                + '</div>'
                + '</div>';

            if (state.lookupFocusField) {
                var activeLookup = root.querySelector('[data-lookup-field="' + state.lookupFocusField + '"]');

                if (activeLookup) {
                    activeLookup.focus();

                    if (typeof activeLookup.setSelectionRange === 'function') {
                        var length = activeLookup.value.length;
                        activeLookup.setSelectionRange(length, length);
                    }
                }
            } else if (state.librarySearchFocused) {
                var librarySearch = root.querySelector('[data-library-search="1"]');

                if (librarySearch) {
                    librarySearch.focus();

                    if (typeof librarySearch.setSelectionRange === 'function') {
                        var searchLength = librarySearch.value.length;
                        librarySearch.setSelectionRange(searchLength, searchLength);
                    }
                }
            }
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

            if (event.target.hasAttribute('data-library-search')) {
                state.ruleSearch = event.target.value;
                state.librarySearchFocused = true;
                render();
                return;
            }

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

            if (event.target.hasAttribute('data-library-status')) {
                state.ruleStatus = event.target.value;
                state.librarySearchFocused = false;
                render();
                return;
            }

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