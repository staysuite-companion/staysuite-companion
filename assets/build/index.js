/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/booking/BookingForm.js"
/*!************************************!*\
  !*** ./src/booking/BookingForm.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ BookingForm)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);

/**
 * Group booking extras form.
 *
 * When mounted next to a theme search bar in Group mode, location, dates
 * and guests are read live from the theme fields (autocomplete,
 * datepickers and guest logic included) and this form collects only the
 * group-specific extras. Standalone (shortcode/block without a theme
 * search nearby) it renders its own Where/dates/guests fields.
 */



/**
 * Find the active theme search wrapper feeding this form.
 *
 * Matches the classic template root and the Elementor widget root alike;
 * SearchMode flags whichever wrapper it enhances with ssc-group-active.
 *
 * @return {Element|null} Theme search wrapper or null.
 */
function findThemeSearch() {
  return document.querySelector('.advanced_search_form_wrapper.ssc-group-active, .search_wr_elementor.ssc-group-active');
}

/**
 * Read a named field from the theme search form.
 *
 * @param {Element|null} root Theme search wrapper.
 * @param {string} name Field name.
 * @return {string} Field value or empty string.
 */
function readThemeField(root, name) {
  if (!root) {
    return '';
  }
  const field = root.querySelector(`[name="${name}"]`);
  return field ? field.value : '';
}
const initialForm = {
  city: '',
  check_in: '',
  check_out: '',
  rooms: '1',
  guests: '2',
  male: '',
  female: '',
  budget_min: '',
  budget_max: '',
  name: '',
  email: '',
  phone: '',
  requirements: '',
  // Honeypot: bots fill it, humans never see it (server rejects non-empty).
  ssc_company: ''
};
function BookingForm({
  title
}) {
  const [form, setForm] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(initialForm);
  const [external, setExternal] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [state, setState] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    status: 'idle',
    message: '',
    matches: [],
    total: 0
  });
  const set = key => event => {
    setForm(prev => ({
      ...prev,
      [key]: event.target.value
    }));
  };
  const handleResult = res => {
    if (res && res.success) {
      setState({
        status: 'done',
        message: '',
        matches: res.data.matches || [],
        total: res.data.total || 0
      });
    } else {
      setState({
        status: 'error',
        message: res && res.data && res.data.message || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Something went wrong. Please try again.', 'staysuite-companion'),
        matches: [],
        total: 0
      });
    }
  };
  const sendQuote = (payload, retried) => {
    const body = new URLSearchParams();
    body.append('action', 'ssc_group_quote');
    body.append('nonce', sscBooking.quote_nonce);
    Object.entries(payload).forEach(([key, value]) => body.append(key, value));
    fetch(sscBooking.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body
    }).then(response => response.json()).then(res => {
      // Cached pages carry stale nonces: refresh once and retry.
      if (res && !res.success && res.data && res.data.code === 'ssc_nonce_expired' && !retried) {
        refreshNonceAndRetry(payload);
        return;
      }
      handleResult(res);
    }).catch(() => handleResult(null));
  };
  const refreshNonceAndRetry = payload => {
    const refresh = new URLSearchParams();
    refresh.append('action', 'ssc_quote_nonce');
    fetch(sscBooking.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body: refresh
    }).then(response => response.json()).then(res => {
      if (res && res.success && res.data && res.data.nonce) {
        sscBooking.quote_nonce = res.data.nonce;
        sendQuote(payload, true);
      } else {
        handleResult(res);
      }
    }).catch(() => handleResult(null));
  };
  const doSubmit = () => {
    setState({
      status: 'loading',
      message: '',
      matches: [],
      total: 0
    });
    const themeSearch = findThemeSearch();
    const payload = {
      ...form
    };
    if (themeSearch) {
      payload.location_text = readThemeField(themeSearch, 'search_location');
      payload.check_in = readThemeField(themeSearch, 'check_in');
      payload.check_out = readThemeField(themeSearch, 'check_out');
      const themeGuests = parseInt(readThemeField(themeSearch, 'guest_no'), 10);
      payload.guests = themeGuests > 0 ? String(themeGuests) : form.guests;
      payload.city = '';
    }
    sendQuote(payload, false);
  };
  const submitRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  submitRef.current = doSubmit;
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    setExternal(!!findThemeSearch());
    const trigger = event => {
      event.preventDefault();
      if (submitRef.current) {
        submitRef.current();
      }
    };
    window.addEventListener('ssc-group-quote-submit', trigger);
    return () => window.removeEventListener('ssc-group-quote-submit', trigger);
  }, []);
  const submit = event => {
    event.preventDefault();
    doSubmit();
  };
  const cities = sscBooking.cities || [];
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-booking-form-wrap"
  }, title && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
    className: "ssc-booking-title"
  }, title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("form", {
    className: "ssc-booking-form",
    onSubmit: submit
  }, !external && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Where?', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: form.city,
    onChange: set('city')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: ""
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Anywhere', 'staysuite-companion')), cities.map(city => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: city.slug,
    value: city.slug
  }, city.name)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Check in', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "date",
    value: form.check_in,
    onChange: set('check_in')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Check out', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "date",
    value: form.check_out,
    onChange: set('check_out')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Guests', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "1",
    value: form.guests,
    onChange: set('guests'),
    required: true
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Rooms', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "1",
    value: form.rooms,
    onChange: set('rooms'),
    required: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Male', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "0",
    value: form.male,
    onChange: set('male'),
    placeholder: "0"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Female', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "0",
    value: form.female,
    onChange: set('female'),
    placeholder: "0"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Budget min', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "0",
    value: form.budget_min,
    onChange: set('budget_min'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Per night', 'staysuite-companion')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Budget max', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    min: "0",
    value: form.budget_max,
    onChange: set('budget_max'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Per night', 'staysuite-companion')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Your name', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: form.name,
    onChange: set('name'),
    required: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Email', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "email",
    value: form.email,
    onChange: set('email'),
    required: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Phone', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "tel",
    value: form.phone,
    onChange: set('phone')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "ssc-booking-full"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Extra requirements', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    rows: "4",
    value: form.requirements,
    onChange: set('requirements'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Food, transport, event hall — anything we should quote for…', 'staysuite-companion')
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "submit",
    className: "ssc-booking-submit",
    disabled: state.status === 'loading'
  }, state.status === 'loading' ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Finding stays…', 'staysuite-companion') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Get quote', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    name: "ssc_company",
    value: form.ssc_company,
    onChange: set('ssc_company'),
    tabIndex: -1,
    autoComplete: "off",
    "aria-hidden": "true",
    style: {
      position: 'absolute',
      left: '-9999px',
      opacity: 0,
      height: 0
    }
  })), state.status === 'error' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "ssc-booking-error"
  }, state.message), state.status === 'done' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-booking-results"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, state.total > 0 ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Suggested stays in your budget', 'staysuite-companion') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No stays matched — our team will still quote you by email.', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-booking-grid"
  }, state.matches.map(match => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("article", {
    key: match.id,
    className: "ssc-booking-card"
  }, match.image && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: match.url
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: match.image,
    alt: "",
    loading: "lazy"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: match.url
  }, match.title)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-booking-card-meta"
  }, [match.price && `${match.price}`, match.guests > 0 && `${match.guests} guests`].filter(Boolean).join(' · ')))))));
}

/***/ },

/***/ "./src/cards/HotelBadge.js"
/*!*********************************!*\
  !*** ./src/cards/HotelBadge.js ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ HotelBadge)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./api */ "./src/cards/api.js");

/**
 * Hotel badge rendered inside a listing card.
 *
 * Renders nothing until the room is confirmed to belong to a hotel,
 * so standalone listings are untouched.
 */


function HotelBadge({
  roomId
}) {
  const [hotel, setHotel] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    let alive = true;
    (0,_api__WEBPACK_IMPORTED_MODULE_1__.getHotel)(roomId).then(result => {
      if (alive) {
        setHotel(result);
      }
    });
    return () => {
      alive = false;
    };
  }, [roomId]);
  if (!hotel) {
    return null;
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ssc-hotel-link-wrap"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ssc-hotel-link-label"
  }, " \xB7 "), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "ssc-hotel-link",
    href: hotel.url
  }, hotel.name));
}

/***/ },

/***/ "./src/cards/api.js"
/*!**************************!*\
  !*** ./src/cards/api.js ***!
  \**************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getHotel: () => (/* binding */ getHotel)
/* harmony export */ });
/**
 * Batched room -> hotel resolver with per-page caching.
 *
 * Card badges mount independently, but all requests made in the same
 * tick are merged into a single admin-ajax call.
 */

const cache = new Map();
const pending = new Map();
let scheduled = false;

/**
 * Send one batched request for every queued room ID.
 *
 * @return {void}
 */
function flushQueue() {
  scheduled = false;
  if (pending.size === 0 || typeof sscCards === 'undefined') {
    pending.clear();
    return;
  }
  const ids = Array.from(pending.keys());
  const waiting = pending;
  pending.clear();
  const body = new URLSearchParams();
  body.append('action', 'ssc_resolve_hotels');
  body.append('nonce', sscCards.nonce);
  ids.forEach(id => body.append('ids[]', String(id)));
  fetch(sscCards.ajaxurl, {
    method: 'POST',
    credentials: 'same-origin',
    body
  }).then(response => response.json()).then(res => {
    const map = res && res.success && res.data ? res.data : {};
    ids.forEach(id => {
      const hotel = map[id] || map[String(id)] || null;
      cache.set(id, hotel);
      (waiting.get(id) || []).forEach(resolve => resolve(hotel));
    });
  }).catch(() => {
    ids.forEach(id => {
      cache.set(id, null);
      (waiting.get(id) || []).forEach(resolve => resolve(null));
    });
  });
}

/**
 * Resolve the hotel for a room ID, using cache when available.
 *
 * @param {number} roomId Room (estate_property) post ID.
 * @return {Promise<Object|null>} Hotel {name, url} or null.
 */
function getHotel(roomId) {
  const id = parseInt(roomId, 10);
  if (cache.has(id)) {
    return Promise.resolve(cache.get(id));
  }
  return new Promise(resolve => {
    if (!pending.has(id)) {
      pending.set(id, []);
    }
    pending.get(id).push(resolve);
    if (!scheduled) {
      scheduled = true;
      queueMicrotask(flushQueue);
    }
  });
}

/***/ },

/***/ "./src/carousels/Carousel.js"
/*!***********************************!*\
  !*** ./src/carousels/Carousel.js ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CarouselNav)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);

/**
 * Prev/next controls for a server-rendered carousel track.
 *
 * Mounted into existing markup by src/index.js — the track and its
 * cards are plain HTML, so rows work with and without JavaScript.
 * Controls hide themselves when everything already fits (e.g. a
 * single item), which also covers post-load font/image shifts via a
 * delayed re-check.
 */


function CarouselNav({
  track
}) {
  const [visible, setVisible] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!track) {
      return undefined;
    }
    const check = () => {
      setVisible(track.scrollWidth > track.clientWidth + 2);
    };
    check();
    window.addEventListener('resize', check);
    const settled = setTimeout(check, 600);
    return () => {
      window.removeEventListener('resize', check);
      clearTimeout(settled);
    };
  }, [track]);
  if (!visible) {
    return null;
  }
  const scroll = direction => {
    track.scrollBy({
      left: direction * track.clientWidth * 0.8,
      behavior: 'smooth'
    });
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-carousel-nav"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "ssc-carousel-prev",
    "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Previous', 'staysuite-companion'),
    onClick: () => scroll(-1)
  }, "\u2039"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "ssc-carousel-next",
    "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Next', 'staysuite-companion'),
    onClick: () => scroll(1)
  }, "\u203A"));
}

/***/ },

/***/ "./src/search/SearchMode.js"
/*!**********************************!*\
  !*** ./src/search/SearchMode.js ***!
  \**********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ SearchMode)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _booking_BookingForm__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../booking/BookingForm */ "./src/booking/BookingForm.js");

/**
 * Individual / Group switch mounted above a theme search bar.
 *
 * Group mode hides the search bar with an animated collapse and portals
 * the group quote form to a sibling node AFTER the cover, so the cover
 * never stretches and the form spans the content width.
 */




function SearchMode({
  wrapper
}) {
  const [mode, setMode] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('individual');
  const [portalNode, setPortalNode] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!wrapper) {
      return undefined;
    }
    wrapper.classList.toggle('ssc-group-active', mode === 'group');
    if (mode !== 'group') {
      return undefined;
    }
    // Route the theme search submit into the group quote flow.
    const form = wrapper.querySelector('form');
    if (!form) {
      return undefined;
    }
    const intercept = event => {
      event.preventDefault();
      window.dispatchEvent(new CustomEvent('ssc-group-quote-submit'));
    };
    form.addEventListener('submit', intercept);
    return () => form.removeEventListener('submit', intercept);
  }, [mode, wrapper]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (mode !== 'group' || !wrapper) {
      return undefined;
    }
    const hero = wrapper.closest('.ssc-hero');
    const host = hero && hero.parentNode ? hero.parentNode : wrapper.parentNode;
    if (!host) {
      return undefined;
    }
    const node = document.createElement('div');
    node.className = 'ssc-mode-form-outside';
    if (hero && hero.nextSibling) {
      host.insertBefore(node, hero.nextSibling);
    } else {
      host.appendChild(node);
    }
    setPortalNode(node);
    // Trigger the pop-out animation after the collapsed state commits.
    requestAnimationFrame(() => requestAnimationFrame(() => {
      if (node.isConnected) {
        node.classList.add('ssc-open');
      }
    }));
    // Dock the Individual/Group capsule just below the header so the
    // search and the whole group form land in view together.
    const previous = wrapper.previousElementSibling;
    const toggle = previous && previous.hasAttribute('data-ssc-mode') ? previous : document.querySelector('[data-ssc-mode]');
    setTimeout(() => {
      if (!toggle) {
        return;
      }
      const sticky = document.querySelector('.header_wrapper.navbar-fixed-top');
      const offset = (sticky ? sticky.offsetHeight : 80) + 16;
      const y = toggle.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({
        top: Math.max(y, 0),
        behavior: 'smooth'
      });
    }, 80);
    return () => {
      node.remove();
      setPortalNode(null);
    };
  }, [mode, wrapper]);
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-mode"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-mode-toggle",
    role: "tablist",
    "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Booking type', 'staysuite-companion')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    role: "tab",
    "aria-selected": mode === 'individual',
    className: mode === 'individual' ? 'ssc-mode-active' : '',
    onClick: () => setMode('individual')
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Individual', 'staysuite-companion')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    role: "tab",
    "aria-selected": mode === 'group',
    className: mode === 'group' ? 'ssc-mode-active' : '',
    onClick: () => setMode('group')
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Group', 'staysuite-companion'))), mode === 'group' && portalNode && (0,react_dom__WEBPACK_IMPORTED_MODULE_1__.createPortal)((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ssc-mode-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_booking_BookingForm__WEBPACK_IMPORTED_MODULE_3__["default"], null)), portalNode));
}

/***/ },

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
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_dom_client__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-dom/client */ "./node_modules/react-dom/client.js");
/* harmony import */ var _cards_HotelBadge__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./cards/HotelBadge */ "./src/cards/HotelBadge.js");
/* harmony import */ var _carousels_Carousel__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./carousels/Carousel */ "./src/carousels/Carousel.js");
/* harmony import */ var _booking_BookingForm__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./booking/BookingForm */ "./src/booking/BookingForm.js");
/* harmony import */ var _search_SearchMode__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./search/SearchMode */ "./src/search/SearchMode.js");

/**
 * Companion frontend entry.
 *
 * Mounts a HotelBadge React root inside every WpRentals listing card.
 * Cards expose their room ID via .listing_wrapper[data-listid].
 */





document.addEventListener('DOMContentLoaded', () => {
  mountHotelBadges();
  mountCarouselNavs();
  mountBookingForms();
  mountSearchModes();
  pruneHiddenHeaderSearch();
  mountHeroCalendars();
  mountHotelMaps();
  window.addEventListener('load', mountHeroCalendars);
  window.addEventListener('load', defaultGuestCounts);
});

/**
 * Mount a HotelBadge root inside every listing card.
 *
 * @return {void}
 */
function mountHotelBadges() {
  if (typeof sscCards === 'undefined') {
    return;
  }
  document.querySelectorAll('.listing_wrapper[data-listid]').forEach(node => {
    if (node.querySelector('[data-ssc-badge]')) {
      return;
    }
    const roomId = parseInt(node.getAttribute('data-listid'), 10);
    if (!roomId) {
      return;
    }
    const mount = document.createElement('span');
    mount.setAttribute('data-ssc-badge', '1');
    const target = node.querySelector('.category_name') || node;
    target.appendChild(mount);
    (0,react_dom_client__WEBPACK_IMPORTED_MODULE_1__.createRoot)(mount).render((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_cards_HotelBadge__WEBPACK_IMPORTED_MODULE_2__["default"], {
      roomId: roomId
    }));
  });
}

/**
 * Attach prev/next controls to every server-rendered carousel.
 *
 * @return {void}
 */
function mountCarouselNavs() {
  document.querySelectorAll('[data-ssc-carousel]').forEach(root => {
    if (root.querySelector('[data-ssc-carousel-nav]')) {
      return;
    }
    const track = root.querySelector('.ssc-carousel-track');
    if (!track) {
      return;
    }
    const mount = document.createElement('div');
    mount.setAttribute('data-ssc-carousel-nav', '1');
    root.appendChild(mount);
    (0,react_dom_client__WEBPACK_IMPORTED_MODULE_1__.createRoot)(mount).render((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_carousels_Carousel__WEBPACK_IMPORTED_MODULE_3__["default"], {
      track: track
    }));
  });
}

/**
 * Mount the group booking form wherever requested.
 *
 * @return {void}
 */
function mountBookingForms() {
  if (typeof sscBooking === 'undefined') {
    return;
  }
  document.querySelectorAll('[data-ssc-group-booking]').forEach(node => {
    if (node.querySelector('[data-ssc-booking-root]')) {
      return;
    }
    const mount = document.createElement('div');
    mount.setAttribute('data-ssc-booking-root', '1');
    node.appendChild(mount);
    (0,react_dom_client__WEBPACK_IMPORTED_MODULE_1__.createRoot)(mount).render((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_booking_BookingForm__WEBPACK_IMPORTED_MODULE_4__["default"], {
      title: node.getAttribute('data-title') || ''
    }));
  });
}

/**
 * Remove the suppressed theme header search on hero and hotel pages.
 *
 * It is already display:none, but its duplicate check_in/check_out IDs
 * steal the theme's datepicker binding from the visible forms.
 *
 * @return {void}
 */
function pruneHiddenHeaderSearch() {
  if (!document.body.classList.contains('ssc-has-hero') && !document.body.classList.contains('single-ssc_hotel')) {
    return;
  }
  document.querySelectorAll('#search_wrapper').forEach(node => node.remove());
}

/**
 * Bind the theme daterangepicker to hero widget date fields.
 *
 * Hero inputs get unique IDs (duplicates would bind the hidden header
 * copy instead), then the theme's own binder wires them up.
 *
 * @return {void}
 */
function mountHeroCalendars() {
  if (typeof wpestaste_check_in_out_enable !== 'function') {
    return;
  }
  document.querySelectorAll('.ssc-hero .search_wr_elementor').forEach((root, index) => {
    const inEl = root.querySelector('#check_in');
    const outEl = root.querySelector('#check_out');
    if (!inEl || !outEl) {
      return;
    }
    const inId = `ssc_check_in_${index}`;
    const outId = `ssc_check_out_${index}`;
    if (inEl.id !== inId) {
      inEl.id = inId;
    }
    if (outEl.id !== outId) {
      outEl.id = outId;
    }
    if (!inEl.dataset.sscBound) {
      inEl.dataset.sscBound = '1';
      wpestaste_check_in_out_enable(inId, outId);
    }
  });
}
/**
 * Render hotel single maps with Leaflet (theme ships Leaflet globally).
 *
 * The theme's own listing-map script only runs for estate_property
 * singles, so hotels get a first-party map from the same library.
 *
 * @return {void}
 */
function mountHotelMaps() {
  if (typeof L === 'undefined') {
    return;
  }
  document.querySelectorAll('.ssc-hotel-map').forEach(node => {
    if (node.dataset.sscMapped) {
      return;
    }
    const lat = parseFloat(node.dataset.lat);
    const lng = parseFloat(node.dataset.lng);
    if (!isFinite(lat) || !isFinite(lng) || lat === 0 && lng === 0) {
      return;
    }
    node.dataset.sscMapped = '1';
    const map = L.map(node, {
      scrollWheelZoom: false
    }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(node.dataset.title || '');
    setTimeout(() => map.invalidateSize(), 400);
  });
}
/**
 * Default every pristine theme guest panel to 2 adults.
 *
 * Clicks the theme's own Adults "+" twice so its totals, labels and
 * hidden guest_no stay consistent (max-guest caps still apply).
 * Only panels still sitting at 0 are touched; runs after the theme
 * binds its controls on window load.
 *
 * @return {void}
 */
function defaultGuestCounts() {
  var adults = 2;
  if (typeof sscSettings !== 'undefined' && typeof sscSettings.adults !== 'undefined') {
    adults = parseInt(sscSettings.adults, 10);
  }
  if (!(adults > 0)) {
    return;
  }
  setTimeout(() => {
    document.querySelectorAll('.wpestate_guest_no_buttons').forEach(panel => {
      if (panel.dataset.sscDefaulted) {
        return;
      }
      const value = panel.querySelector('.steper_value_adults');
      const plus = panel.querySelector('.adults_control_plus');
      if (!value || !plus || value.textContent.trim() !== '0') {
        return;
      }
      panel.dataset.sscDefaulted = '1';
      for (let i = 0; i < adults; i++) {
        plus.click();
      }
    });
  }, 300);
}
/**
 * Mount an Individual/Group switch above the homepage theme search bar.
 *
 * Homepage only (body.ssc-homepage): everywhere else the theme search
 * stays exactly as is. Works with the Elementor widget and theme
 * templates alike because it keys off the shared search markup.
 *
 * @return {void}
 */
function mountSearchModes() {
  if (typeof sscBooking === 'undefined') {
    return;
  }
  var capsule = '1';
  if (typeof sscSettings !== 'undefined' && typeof sscSettings.capsule !== 'undefined') {
    capsule = String(sscSettings.capsule);
  }
  if (capsule === '0' || capsule === '') {
    return;
  }
  if (!document.body.classList.contains('ssc-homepage')) {
    return;
  }
  document.querySelectorAll('.advanced_search_form_wrapper, .search_wr_elementor').forEach(wrapper => {
    if (wrapper.previousElementSibling && wrapper.previousElementSibling.hasAttribute('data-ssc-mode')) {
      return;
    }
    // The theme header search is suppressed on hero pages (see
    // body.ssc-has-hero styles); only enhance the visible one.
    if (document.body.classList.contains('ssc-has-hero') && wrapper.closest('#search_wrapper')) {
      return;
    }
    const mount = document.createElement('div');
    mount.setAttribute('data-ssc-mode', '1');
    wrapper.parentNode.insertBefore(mount, wrapper);
    (0,react_dom_client__WEBPACK_IMPORTED_MODULE_1__.createRoot)(mount).render((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_search_SearchMode__WEBPACK_IMPORTED_MODULE_5__["default"], {
      wrapper: wrapper
    }));
  });
}
})();

/******/ })()
;
//# sourceMappingURL=index.js.map