/**
 * Signup extras injected into theme registration forms.
 *
 * The theme renders its register forms (shortcode, modal, mobile menu,
 * booking flow) with no hooks, so fields are appended here and carried
 * to the server two ways: phone inputs reuse the theme's own
 * user_phone_register IDs (its AJAX already sends and stores them),
 * while gender travels via an ajaxPrefilter because no theme payload
 * includes it. Enforcement itself is server-side (Signup.php); the
 * client-side checks below are instant-feedback UX only.
 */

function signupConfig() {
    const cfg = (typeof window !== 'undefined' && window.sscSignup) || {};
    const mode = (value, fallback, allowed) => (allowed.includes(value) ? value : fallback);
    const gender = mode(cfg.gender, 'profile', ['off', 'profile', 'optional', 'required']);
    return {
        phone: mode(cfg.phone, 'required', ['off', 'optional', 'required']),
        gender,
        showGenderOnSignup: gender === 'optional' || gender === 'required',
        genders: cfg.genders && typeof cfg.genders === 'object' ? cfg.genders : {},
        genderValue: typeof cfg.gender_value === 'string' ? cfg.gender_value : '',
    };
}

function registerContainers() {
    const found = [];
    document.querySelectorAll('.login_form').forEach((node) => {
        if (node.querySelector('input[name="user_login_register"]')) {
            found.push(node);
        }
    });
    document.querySelectorAll('form').forEach((form) => {
        if (
            form.querySelector('input[name="user_login_register"]') &&
            !found.some((node) => node === form || node.contains(form) || form.contains(node))
        ) {
            found.push(form);
        }
    });
    return found;
}

function usernameSuffix(container) {
    const login = container.querySelector('input[name="user_login_register"]');
    if (!login || !login.id) {
        return '';
    }
    return login.id.replace('user_login_register', '');
}

function phoneRow(suffix, required) {
    const wrap = document.createElement('div');
    wrap.className = 'loginrow';
    wrap.setAttribute('data-ssc-signup', 'phone');
    const input = document.createElement('input');
    input.type = 'tel';
    input.name = 'user_phone_register';
    input.id = `user_phone_register${suffix}`;
    input.className = 'form-control';
    input.placeholder = 'Phone';
    input.autocomplete = 'tel';
    if (required) {
        input.required = true;
    }
    wrap.appendChild(input);
    return wrap;
}

function genderRow(genders, selected, required) {
    const wrap = document.createElement('div');
    wrap.className = 'loginrow';
    wrap.setAttribute('data-ssc-signup', 'gender');
    const select = document.createElement('select');
    select.name = 'ssc_gender';
    select.className = 'form-control';
    select.setAttribute('data-ssc-gender', '1');
    if (required) {
        select.required = true;
    }
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Gender';
    select.appendChild(placeholder);
    Object.keys(genders).forEach((slug) => {
        const option = document.createElement('option');
        option.value = slug;
        option.textContent = genders[slug];
        if (slug === selected) {
            option.selected = true;
        }
        select.appendChild(option);
    });
    wrap.appendChild(select);
    return wrap;
}

function insertBeforeSubmit(container, node) {
    const anchor =
        container.querySelector('input[name="terms"]') ||
        container.querySelector('[id^="wp-submit-register"]') ||
        container.querySelector('button[type="submit"]');
    if (anchor && anchor.parentNode === container) {
        container.insertBefore(node, anchor);
        return;
    }
    const row = anchor ? anchor.closest('.loginrow') : null;
    if (row && row.parentNode === container) {
        container.insertBefore(node, row);
        return;
    }
    container.appendChild(node);
}

function ensureFields(container, config) {
    if (container.hasAttribute('data-ssc-signup-done')) {
        return;
    }
    container.setAttribute('data-ssc-signup-done', '1');
    const suffix = usernameSuffix(container);
    if (config.phone !== 'off' && !container.querySelector('input[name="user_phone_register"]')) {
        insertBeforeSubmit(container, phoneRow(suffix, config.phone === 'required'));
    }
    if (config.showGenderOnSignup && !container.querySelector('[data-ssc-gender]')) {
        insertGenderAfterPhone(container, genderRow(config.genders, '', config.gender === 'required'));
    }
    growModalDialog(container);
}

/**
 * Place gender directly after the phone field (theme-rendered or just
 * injected); fall back to the pre-submit position when no phone exists.
 */
function insertGenderAfterPhone(container, node) {
    const phone = container.querySelector('input[name="user_phone_register"]');
    const row = phone ? phone.closest('.loginrow') : null;
    if (row && row.parentNode) {
        row.after(node);
        return;
    }
    if (phone && phone.parentNode) {
        phone.after(node);
        return;
    }
    insertBeforeSubmit(container, node);
}

/**
 * Let the login modal grow with injected rows.
 *
 * The theme fixes the dialog height in PHP from its own option flags,
 * so our extra rows overflow under the heading. Auto height keeps the
 * absolutely-positioned side image stretching with the dialog.
 */
function growModalDialog(container) {
    const dialog = container.closest('#loginmodal')?.querySelector('.modal-dialog');
    if (dialog) {
        dialog.style.height = 'auto';
    }
}

function readStash(container) {
    const login = container.querySelector('input[name="user_login_register"]');
    const phone = container.querySelector('input[name="user_phone_register"]');
    const gender = container.querySelector('[data-ssc-gender]');
    return {
        user: login ? login.value.trim() : '',
        phone: phone ? phone.value.trim() : '',
        gender: gender ? gender.value : '',
    };
}

function messageArea(container) {
    return (
        container.querySelector('.loginalert') ||
        container.querySelector('[id^="register_message_area"]') ||
        document.querySelector('[id^="register_message_area"]')
    );
}

const stashByUser = {};

/**
 * Intercept register submits for instant feedback, then stash values so
 * the ajaxPrefilter can attach gender (and phone on surfaces whose
 * theme payload omits it) to the outgoing theme AJAX request.
 */
function watchSubmits(config) {
    document.addEventListener(
        'click',
        (event) => {
            const button = event.target.closest('[id^="wp-submit-register"], button[type="submit"]');
            if (!button) {
                return;
            }
            const container = button.closest('.login_form, form');
            if (!container || !container.querySelector('input[name="user_login_register"]')) {
                return;
            }
            const stash = readStash(container);
            if (stash.user) {
                stashByUser[stash.user] = stash;
            }
            const missing =
                (config.phone === 'required' && !stash.phone) ||
                (config.gender === 'required' && !stash.gender);
            if (!missing) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            const area = messageArea(container);
            if (area) {
                area.textContent = !stash.phone ? 'Please enter your phone number.' : 'Please select your gender.';
                area.style.display = 'block';
            }
        },
        true
    );
}

function appendParam(data, key, value) {
    if (!value) {
        return data;
    }
    const encoded = `${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
    if (data.indexOf(`${encodeURIComponent(key)}=`) !== -1) {
        return data;
    }
    return data ? `${data}&${encoded}` : encoded;
}

/**
 * Attach gender (always) and phone (when the theme payload lacks it,
 * e.g. the booking flow) to theme registration AJAX requests, plus
 * gender to the dashboard profile-update request.
 */
function watchAjax() {
    if (typeof jQuery === 'undefined' || !jQuery.ajaxPrefilter) {
        return;
    }
    jQuery.ajaxPrefilter((options) => {
        if (typeof options.data !== 'string') {
            return;
        }
        if (options.data.indexOf('wpestate_ajax_update_profile') !== -1) {
            const current = document.querySelector('#ssc_gender_profile');
            if (current && current.value) {
                options.data = appendParam(options.data, 'ssc_gender', current.value);
            }
            return;
        }
        if (options.data.indexOf('wpestate_ajax_register_form') === -1) {
            return;
        }
        const match = options.data.match(/user_login_register=([^&]*)/);
        const user = match ? decodeURIComponent(match[1].replace(/\+/g, ' ')) : '';
        const stash = stashByUser[user];
        if (!stash) {
            return;
        }
        options.data = appendParam(options.data, 'user_phone', stash.phone);
        options.data = appendParam(options.data, 'ssc_gender', stash.gender);
    });
}

/**
 * Inject an editable Gender select into the theme dashboard profile.
 *
 * Mirrors the theme's own label + input rows and sits after Mobile, in
 * the phone/contact context. Skipped when gender is off; shown for
 * profile, optional and required modes alike.
 */
export function mountProfileGender() {
    const config = signupConfig();
    if (config.gender === 'off') {
        return;
    }
    const panel = document.querySelector('.user_dashboard_panel #update_profile')?.closest('.user_dashboard_panel');
    if (!panel || panel.querySelector('#ssc_gender_profile')) {
        return;
    }
    const anchor = panel.querySelector('#usermobile')?.closest('p');
    if (!anchor) {
        return;
    }
    const row = document.createElement('p');
    const label = document.createElement('label');
    label.setAttribute('for', 'ssc_gender_profile');
    label.textContent = 'Gender';
    const select = document.createElement('select');
    select.id = 'ssc_gender_profile';
    select.name = 'ssc_gender';
    select.className = 'form-control';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select gender';
    select.appendChild(placeholder);
    Object.keys(config.genders).forEach((slug) => {
        const option = document.createElement('option');
        option.value = slug;
        option.textContent = config.genders[slug];
        if (slug === config.genderValue) {
            option.selected = true;
        }
        select.appendChild(option);
    });
    row.appendChild(label);
    row.appendChild(select);
    anchor.after(row);
}

export default function mountSignupFields() {
    const config = signupConfig();
    if (config.phone === 'off' && config.gender === 'off') {
        return;
    }
    registerContainers().forEach((container) => ensureFields(container, config));
    watchSubmits(config);
    watchAjax();
}
