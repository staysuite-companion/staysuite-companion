/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/react-dom/client.js"
/*!******************************************!*\
  !*** ./node_modules/react-dom/client.js ***!
  \******************************************/
(__unused_webpack_module, exports, __webpack_require__) {



var m = __webpack_require__(/*! react-dom */ "react-dom");
if (false) // removed by dead control flow
{} else {
  var i = m.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED;
  exports.createRoot = function(c, o) {
    i.usingClientEntryPoint = true;
    try {
      return m.createRoot(c, o);
    } finally {
      i.usingClientEntryPoint = false;
    }
  };
  exports.hydrateRoot = function(c, h, o) {
    i.usingClientEntryPoint = true;
    try {
      return m.hydrateRoot(c, h, o);
    } finally {
      i.usingClientEntryPoint = false;
    }
  };
}


/***/ },

/***/ "react"
/*!************************!*\
  !*** external "React" ***!
  \************************/
(module) {

module.exports = window["React"];

/***/ },

/***/ "react-dom"
/*!***************************!*\
  !*** external "ReactDOM" ***!
  \***************************/
(module) {

module.exports = window["ReactDOM"];

/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/hooks"
/*!*******************************!*\
  !*** external ["wp","hooks"] ***!
  \*******************************/
(module) {

module.exports = window["wp"]["hooks"];

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	const __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		const cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		const module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			const e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = (module) => {
/******/ 		const getter = module && module.__esModule ?
/******/ 			() => (module['default']) :
/******/ 			() => (module);
/******/ 		__webpack_require__.d(getter, { a: getter });
/******/ 		return getter;
/******/ 	};
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	// define getter/value functions for harmony exports
/******/ 	__webpack_require__.d = (exports, definition) => {
/******/ 		for(var key in definition) {
/******/ 			if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 				Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 			}
/******/ 		}
/******/ 	};
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	__webpack_require__.o = (obj, prop) => (Object.hasOwn(obj, prop));
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ 	
/************************************************************************/
let __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**********************!*\
  !*** ./src/admin.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_dom_client__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-dom/client */ "./node_modules/react-dom/client.js");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);

/**
 * StaySuite admin tab shell.
 *
 * Single settings page (menu slug ssc-staysuite). Tabs come from the
 * ssc.admin.tabs filter so Pro injects License/AI tabs without edits.
 * Go Pro renders only when Pro is absent (data-pro flag).
 */






/**
 * Canonical outbound links, passed from PHP (Links::all) via
 * wp_localize_script. No hard-coded URLs in JS.
 *
 * @return {Object} Link list (may be empty outside wp-admin).
 */
function sscLinks() {
  return typeof window !== 'undefined' && window.sscLinks || {};
}
function tabSlugFromSubmenuLink(anchor) {
  try {
    const url = new URL(anchor.href, window.location.href);
    return url.searchParams.get('tab') || 'settings';
  } catch (e) {
    return 'settings';
  }
}
function syncSidebarSubmenu(slug) {
  document.querySelectorAll('#adminmenu .wp-submenu a[href*="page=ssc-staysuite"]').forEach(anchor => {
    const active = tabSlugFromSubmenuLink(anchor) === slug;
    anchor.classList.toggle('current', active);
    const li = anchor.closest('li');
    if (li) {
      li.classList.toggle('current', active);
    }
  });
}
function useFeedback() {
  const [feedback, setFeedback] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!feedback) {
      return undefined;
    }
    const timer = window.setTimeout(() => setFeedback(null), 4500);
    return () => window.clearTimeout(timer);
  }, [feedback]);
  return {
    feedback,
    setFeedback
  };
}
function FeedbackToast({
  feedback
}) {
  if (!feedback) {
    return null;
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `ssc-feedback ssc-feedback-${feedback.kind || 'success'}`
  }, feedback.text);
}
function useSettings() {
  const [settings, setSettings] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const {
    feedback,
    setFeedback
  } = useFeedback();
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/ssc/v1/settings'
    }).then(res => {
      setSettings(res && res.settings ? res.settings : {});
    }).catch(err => {
      setFeedback({
        kind: 'error',
        text: err && err.message ? err.message : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Could not load settings.', 'staysuite-companion')
      });
    });
  }, []);
  const save = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/ssc/v1/settings',
      method: 'POST',
      data: {
        settings
      }
    }).then(res => {
      setSettings(res && res.settings ? res.settings : settings);
      setFeedback({
        kind: 'success',
        text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Settings saved.', 'staysuite-companion')
      });
    }).catch(err => {
      setFeedback({
        kind: 'error',
        text: err && err.message ? err.message : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Could not save settings.', 'staysuite-companion')
      });
    });
  };
  return {
    settings,
    setSettings,
    save,
    feedback
  };
}
function Check({
  label,
  checked,
  onChange
}) {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    style: {
      display: 'block',
      marginBottom: '10px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: !!checked,
    onChange: e => onChange(e.target.checked ? 1 : 0)
  }), " ", label);
}
function Row({
  label,
  hint,
  children
}) {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-form-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-form-label"
  }, label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-form-field"
  }, children, hint && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "description"
  }, hint)));
}
function SettingsTab() {
  const {
    settings,
    setSettings,
    save,
    feedback
  } = useSettings();
  if (!settings) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Loading…', 'staysuite-companion'));
  }
  const set = key => value => setSettings(prev => ({
    ...prev,
    [key]: value
  }));
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-tab-panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('General', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Individual / Group capsule', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Check, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Show the capsule above the homepage search', 'staysuite-companion'),
    checked: settings.capsule,
    onChange: set('capsule')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Default adults', 'staysuite-companion'),
    hint: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Preselected in every Guests panel. 0 disables.', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "0",
    max: "6",
    value: settings.default_adults,
    onChange: e => set('default_adults')(parseInt(e.target.value || '0', 10)),
    style: {
      width: '80px'
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Homepage sections', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Check, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hairline dividers between sections', 'staysuite-companion'),
    checked: settings.dividers,
    onChange: set('dividers')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Check, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Group form pop-out animation', 'staysuite-companion'),
    checked: settings.animations,
    onChange: set('animations')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hero cover height', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "30",
    max: "100",
    value: settings.hero_height,
    onChange: e => set('hero_height')(parseInt(e.target.value || '75', 10)),
    style: {
      width: '80px'
    }
  }), ' ', (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "vh")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Search', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Search results return', 'staysuite-companion'),
    hint: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Applies to the homepage search and the advanced search. Hotels is the default.', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: settings.search_result === 'listings' ? 'listings' : 'hotels',
    onChange: e => set('search_result')(e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "hotels"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hotels', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "listings"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Listings (rooms)', 'staysuite-companion')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Group quotes', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Multi-room selection', 'staysuite-companion'),
    hint: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Shows “Add to quote” on hotel room cards so visitors can request several rooms in one group request (Pro). Off leaves instant booking only.', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Check, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Enable group quote requests', 'staysuite-companion'),
    checked: settings.group_selection,
    onChange: set('group_selection')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Required contact', 'staysuite-companion'),
    hint: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Which contact field the group quote form requires. Phone numbers are never format-checked, only required.', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: ['email', 'phone', 'both'].includes(settings.contact_required) ? settings.contact_required : 'email',
    onChange: e => set('contact_required')(e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "email"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Email only', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "phone"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Phone only', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "both"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Email and phone', 'staysuite-companion')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Search colors', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Color source', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    style: {
      display: 'block',
      marginBottom: '8px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    checked: settings.color_mode !== 'custom',
    onChange: () => set('color_mode')('theme')
  }), ' ', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Follow theme customizer', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: '8px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    checked: settings.color_mode === 'custom',
    onChange: () => set('color_mode')('custom')
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Custom', 'staysuite-companion'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "color",
    value: settings.color_submit,
    onChange: e => set('color_submit')(e.target.value),
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Submit button', 'staysuite-companion')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "color",
    value: settings.color_hover,
    onChange: e => set('color_hover')(e.target.value),
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hover', 'staysuite-companion')
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Advanced', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Row, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Uninstall', 'staysuite-companion'),
    hint: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('When on, uninstalling deletes Hotels, Group Requests and all StaySuite data. Off keeps your content.', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Check, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Delete all StaySuite data when the plugin is uninstalled', 'staysuite-companion'),
    checked: settings.delete_on_uninstall,
    onChange: set('delete_on_uninstall')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button-primary",
    onClick: save
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Save settings', 'staysuite-companion'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(FeedbackToast, {
    feedback: feedback
  }));
}
const PRO_FEATURES = [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Multi-room selection with combined pricing summary', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Quote-to-invoice pipeline with deposits and reminders', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Seasonal pricing display and scheduled sales', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('OTA availability sync (Booking.com, Airbnb)', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('AI group concierge on your own API key', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Analytics, upsell add-ons and bulk importers', 'staysuite-companion')];
function GoProTab() {
  const links = sscLinks();
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-tab-panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('StaySuite Pro', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Turn browsing into high-value bookings:', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
    className: "ssc-pro-list"
  }, PRO_FEATURES.map(feature => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: feature
  }, feature))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "button button-primary button-hero",
    href: links.pro,
    target: "_blank",
    rel: "noopener noreferrer"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Get StaySuite Pro', 'staysuite-companion'))));
}
function AdminApp({
  logo,
  tabs,
  initial
}) {
  const [active, setActive] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(initial || (tabs.length ? tabs[0].slug : ''));
  const current = tabs.find(t => t.slug === active) || tabs[0];
  const links = sscLinks();
  const select = slug => {
    setActive(slug);
    updateTabUrl(slug);
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => syncSidebarSubmenu(active), [active]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const onNavigate = event => {
      if (!event.detail || typeof event.detail.slug !== 'string') {
        return;
      }
      setActive(event.detail.slug);
      updateTabUrl(event.detail.slug);
    };
    window.addEventListener('ssc-tab-navigate', onNavigate);
    return () => window.removeEventListener('ssc-tab-navigate', onNavigate);
  }, []);
  function updateTabUrl(slug) {
    try {
      const url = new URL(location.href);
      url.searchParams.set('tab', slug);
      history.replaceState(null, '', url.toString());
    } catch (e) {
      /* non-fatal */
    }
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-admin"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-admin-head"
  }, logo && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: logo,
    alt: "StaySuite"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "StaySuite"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ssc-admin-links"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: links.docs,
    target: "_blank",
    rel: "noopener noreferrer"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Docs', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: links.support,
    target: "_blank",
    rel: "noopener noreferrer"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Support', 'staysuite-companion')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-admin-tabs",
    role: "tablist"
  }, tabs.map(tab => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: tab.slug,
    type: "button",
    role: "tab",
    "aria-selected": tab.slug === active,
    className: tab.slug === active ? 'ssc-tab-active' : '',
    onClick: () => select(tab.slug)
  }, tab.title))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-admin-body"
  }, current && current.render ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(current.render, null) : null));
}
document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('ssc-admin-root');
  if (!root) {
    return;
  }
  const proActive = root.dataset.pro === '1';
  const base = [{
    slug: 'settings',
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Settings', 'staysuite-companion'),
    render: SettingsTab
  }];
  if (!proActive) {
    base.push({
      slug: 'go-pro',
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Go Pro', 'staysuite-companion'),
      render: GoProTab
    });
  }
  const tabs = (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__.applyFilters)('ssc.admin.tabs', base);
  document.querySelectorAll('#adminmenu .wp-submenu a[href*="page=ssc-staysuite"]').forEach(anchor => {
    anchor.addEventListener('click', event => {
      event.preventDefault();
      const slug = tabSlugFromSubmenuLink(anchor);
      window.dispatchEvent(new CustomEvent('ssc-tab-navigate', {
        detail: {
          slug
        }
      }));
    });
  });
  const slugs = tabs.map(t => t.slug);
  let initial = '';
  try {
    initial = new URLSearchParams(location.search).get('tab') || '';
  } catch (e) {
    initial = '';
  }
  if (!slugs.includes(initial)) {
    initial = slugs.length ? slugs[0] : '';
  }
  ;(0,react_dom_client__WEBPACK_IMPORTED_MODULE_1__.createRoot)(root).render((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(AdminApp, {
    logo: root.dataset.logo || '',
    tabs: tabs,
    initial: initial
  }));
});
})();

/******/ })()
;
//# sourceMappingURL=admin.js.map