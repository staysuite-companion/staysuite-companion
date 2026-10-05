/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/blocks/Preview.js"
/*!*******************************!*\
  !*** ./src/blocks/Preview.js ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Preview)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);

/**
 * First-party block preview.
 *
 * POSTs attributes to the ssc/v1/preview REST route and renders the
 * returned HTML. Replaces @wordpress/server-side-render, whose
 * ref/effect machinery conflicts with the iframed editor canvas.
 */





function Preview({
  block,
  attributes
}) {
  const [html, setHtml] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [loading, setLoading] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const timer = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);

  // The REST request has no query context, so the edited post is sent along.
  // Server-rendered fallbacks (the hero cover) need it to match the page.
  const postId = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useSelect)(select => select('core/editor').getCurrentPostId(), []);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    setLoading(true);
    clearTimeout(timer.current);
    timer.current = setTimeout(() => {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
        path: '/ssc/v1/preview',
        method: 'POST',
        data: {
          block,
          attributes,
          postId
        }
      }).then(res => {
        setHtml(res && res.html ? res.html : '');
        setLoading(false);
      }).catch(() => setLoading(false));
    }, 250);
    return () => clearTimeout(timer.current);
    // Stringified attributes keep the effect keyed on values, not identity.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [block, postId, JSON.stringify(attributes)]);
  if (loading && !html) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Spinner, null);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.RawHTML, null, html);
}

/***/ },

/***/ "./src/blocks/ProUpsell.js"
/*!*********************************!*\
  !*** ./src/blocks/ProUpsell.js ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ProUpsell)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);

/**
 * Shared Pro upsell panel for block inspectors.
 *
 * Tasteful and wp.org-safe: locked feature list plus an outbound link.
 * Never gates existing free functionality.
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
function ProUpsell({
  features
}) {
  const links = sscLinks();
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('StaySuite Pro', 'staysuite-companion'),
    initialOpen: false
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
    style: {
      listStyle: 'disc',
      marginLeft: '18px'
    }
  }, (features || []).map(feature => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: feature
  }, feature))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    variant: "primary",
    href: links.pro,
    target: "_blank",
    rel: "noopener noreferrer",
    style: {
      marginTop: '8px'
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Get StaySuite Pro', 'staysuite-companion')));
}

/***/ },

/***/ "./src/blocks/group-booking.js"
/*!*************************************!*\
  !*** ./src/blocks/group-booking.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _Preview__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Preview */ "./src/blocks/Preview.js");
/* harmony import */ var _ProUpsell__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ProUpsell */ "./src/blocks/ProUpsell.js");

/**
 * Group booking block editor.
 */






(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__.registerBlockType)('ssc/group-booking', {
  title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('StaySuite Group Booking', 'staysuite-companion'),
  icon: 'groups',
  category: 'widgets',
  attributes: {
    title: {
      type: 'string',
      default: ''
    }
  },
  edit({
    attributes,
    setAttributes
  }) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Form settings', 'staysuite-companion')
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Heading', 'staysuite-companion'),
      value: attributes.title,
      onChange: title => setAttributes({
        title
      })
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ProUpsell__WEBPACK_IMPORTED_MODULE_6__["default"], {
      features: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Quote-to-invoice pipeline', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Email templates + reminders', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Upsell add-ons', 'staysuite-companion')]
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Preview__WEBPACK_IMPORTED_MODULE_5__["default"], {
      block: "ssc/group-booking",
      attributes: attributes
    }));
  },
  save: () => null
});

/***/ },

/***/ "./src/blocks/hero-search.js"
/*!***********************************!*\
  !*** ./src/blocks/hero-search.js ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _Preview__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Preview */ "./src/blocks/Preview.js");
/* harmony import */ var _ProUpsell__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ProUpsell */ "./src/blocks/ProUpsell.js");

/**
 * Hero search block editor.
 */






(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__.registerBlockType)('ssc/hero-search', {
  title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('StaySuite Hero Search', 'staysuite-companion'),
  icon: 'cover-image',
  category: 'widgets',
  attributes: {
    title: {
      type: 'string',
      default: ''
    },
    subtitle: {
      type: 'string',
      default: ''
    },
    image_id: {
      type: 'number',
      default: 0
    },
    show_search: {
      type: 'boolean',
      default: true
    },
    search_mode: {
      type: 'string',
      default: 'theme'
    }
  },
  edit({
    attributes,
    setAttributes
  }) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hero settings', 'staysuite-companion')
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Heading', 'staysuite-companion'),
      value: attributes.title,
      onChange: title => setAttributes({
        title
      })
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Subheading', 'staysuite-companion'),
      value: attributes.subtitle,
      onChange: subtitle => setAttributes({
        subtitle
      })
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.MediaUploadCheck, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.MediaUpload, {
      allowedTypes: ['image'],
      value: attributes.image_id,
      onSelect: media => setAttributes({
        image_id: media.id
      }),
      render: ({
        open
      }) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
        variant: "secondary",
        onClick: open
      }, attributes.image_id ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Change cover image', 'staysuite-companion') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Pick cover image', 'staysuite-companion'))
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Show search form', 'staysuite-companion'),
      checked: attributes.show_search,
      onChange: show_search => setAttributes({
        show_search
      })
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Search type', 'staysuite-companion'),
      value: attributes.search_mode,
      options: [{
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Theme search (with Group pill)', 'staysuite-companion'),
        value: 'theme'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Simple search', 'staysuite-companion'),
        value: 'simple'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('No search', 'staysuite-companion'),
        value: 'none'
      }],
      onChange: search_mode => setAttributes({
        search_mode
      })
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ProUpsell__WEBPACK_IMPORTED_MODULE_6__["default"], {
      features: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('AI concierge search', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Scheduled cover variants', 'staysuite-companion')]
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Preview__WEBPACK_IMPORTED_MODULE_5__["default"], {
      block: "ssc/hero-search",
      attributes: attributes
    }));
  },
  save: () => null
});

/***/ },

/***/ "./src/blocks/listing-carousel.js"
/*!****************************************!*\
  !*** ./src/blocks/listing-carousel.js ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _Preview__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Preview */ "./src/blocks/Preview.js");
/* harmony import */ var _ProUpsell__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./ProUpsell */ "./src/blocks/ProUpsell.js");

/**
 * Listing carousel block editor.
 */








function useCarouselOptions(taxonomy, source) {
  const [options, setOptions] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    terms: [],
    posts: []
  });
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    let active = true;
    const path = `/ssc/v1/carousel-options?taxonomy=${encodeURIComponent(taxonomy || '')}&source=${encodeURIComponent(source || 'rooms')}`;
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default()({
      path
    }).then(res => {
      if (!active) {
        return;
      }
      setOptions({
        terms: Array.isArray(res && res.terms) ? res.terms : [],
        posts: Array.isArray(res && res.posts) ? res.posts : []
      });
    }).catch(() => {
      if (active) {
        setOptions({
          terms: [],
          posts: []
        });
      }
    });
    return () => {
      active = false;
    };
  }, [taxonomy, source]);
  return options;
}
;(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__.registerBlockType)('ssc/listing-carousel', {
  title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('StaySuite Listing Carousel', 'staysuite-companion'),
  icon: 'slides',
  category: 'widgets',
  attributes: {
    title: {
      type: 'string',
      default: ''
    },
    source: {
      type: 'string',
      default: 'rooms'
    },
    taxonomy: {
      type: 'string',
      default: ''
    },
    term: {
      type: 'string',
      default: ''
    },
    city: {
      type: 'string',
      default: ''
    },
    count: {
      type: 'number',
      default: 8
    },
    featured_only: {
      type: 'boolean',
      default: false
    },
    include_ids: {
      type: 'string',
      default: ''
    },
    order: {
      type: 'string',
      default: 'featured'
    }
  },
  edit({
    attributes,
    setAttributes
  }) {
    const set = key => value => setAttributes({
      [key]: value
    });
    const {
      terms,
      posts
    } = useCarouselOptions(attributes.taxonomy, attributes.source);
    const hasHandPicked = attributes.include_ids !== '';
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Row settings', 'staysuite-companion')
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Row title', 'staysuite-companion'),
      value: attributes.title,
      onChange: set('title')
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Source', 'staysuite-companion'),
      value: attributes.source,
      options: [{
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Rooms', 'staysuite-companion'),
        value: 'rooms'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hotels', 'staysuite-companion'),
        value: 'hotels'
      }],
      onChange: value => setAttributes({
        source: value,
        include_ids: ''
      })
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hand-picked', 'staysuite-companion'),
      help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Optional single post from the selected source. Shows only this post; the filters below are hidden while set.', 'staysuite-companion'),
      value: attributes.include_ids,
      options: [{
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('None', 'staysuite-companion'),
        value: ''
      }, ...posts.map(p => ({
        label: p.title,
        value: String(p.id)
      }))],
      onChange: set('include_ids')
    }), !hasHandPicked && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Filter taxonomy', 'staysuite-companion'),
      value: attributes.taxonomy,
      options: [{
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('None', 'staysuite-companion'),
        value: ''
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('City', 'staysuite-companion'),
        value: 'property_city'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Category', 'staysuite-companion'),
        value: 'property_category'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Listing type', 'staysuite-companion'),
        value: 'property_action_category'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Area', 'staysuite-companion'),
        value: 'property_area'
      }],
      onChange: value => setAttributes({
        taxonomy: value,
        term: ''
      })
    }), attributes.taxonomy && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Term', 'staysuite-companion'),
      help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Choose from the selected taxonomy. Works for both rooms and hotels.', 'staysuite-companion'),
      value: attributes.term,
      options: [{
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('All terms', 'staysuite-companion'),
        value: ''
      }, ...terms.map(t => ({
        label: `${t.name} (${t.count})`,
        value: t.slug
      }))],
      onChange: set('term')
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.RangeControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('How many', 'staysuite-companion'),
      value: attributes.count,
      min: 1,
      max: 24,
      onChange: set('count')
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Featured only', 'staysuite-companion'),
      checked: attributes.featured_only,
      onChange: set('featured_only')
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Order', 'staysuite-companion'),
      value: attributes.order,
      options: [{
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Featured first', 'staysuite-companion'),
        value: 'featured'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Price: low to high', 'staysuite-companion'),
        value: 'price_asc'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Price: high to low', 'staysuite-companion'),
        value: 'price_desc'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Newest', 'staysuite-companion'),
        value: 'newest'
      }, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Random', 'staysuite-companion'),
        value: 'rand'
      }],
      onChange: set('order')
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ProUpsell__WEBPACK_IMPORTED_MODULE_7__["default"], {
      features: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Discount badges + scheduled sales', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Sponsored ordering', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Multi-room Add buttons', 'staysuite-companion')]
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Preview__WEBPACK_IMPORTED_MODULE_6__["default"], {
      block: "ssc/listing-carousel",
      attributes: attributes
    }));
  },
  save: () => null
});

/***/ },

/***/ "./src/blocks/payment-strip.js"
/*!*************************************!*\
  !*** ./src/blocks/payment-strip.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _Preview__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Preview */ "./src/blocks/Preview.js");
/* harmony import */ var _ProUpsell__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ProUpsell */ "./src/blocks/ProUpsell.js");

/**
 * Payment strip block editor.
 */






(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__.registerBlockType)('ssc/payment-strip', {
  title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('StaySuite Payment Strip', 'staysuite-companion'),
  icon: 'money-alt',
  category: 'widgets',
  attributes: {
    title: {
      type: 'string',
      default: 'Pay With'
    },
    image_id: {
      type: 'number',
      default: 0
    }
  },
  edit({
    attributes,
    setAttributes
  }) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Payment strip settings', 'staysuite-companion')
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Label', 'staysuite-companion'),
      value: attributes.title,
      onChange: title => setAttributes({
        title
      })
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.MediaUploadCheck, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.MediaUpload, {
      allowedTypes: ['image'],
      value: attributes.image_id,
      onSelect: media => setAttributes({
        image_id: media.id
      }),
      render: ({
        open
      }) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
        variant: "secondary",
        onClick: open
      }, attributes.image_id ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Change banner image', 'staysuite-companion') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Pick banner image', 'staysuite-companion'))
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ProUpsell__WEBPACK_IMPORTED_MODULE_6__["default"], {
      features: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Clickable payment links', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('UPI + crypto badges', 'staysuite-companion')]
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Preview__WEBPACK_IMPORTED_MODULE_5__["default"], {
      block: "ssc/payment-strip",
      attributes: attributes
    }));
  },
  save: () => null
});

/***/ },

/***/ "./src/blocks/term-tablets.js"
/*!************************************!*\
  !*** ./src/blocks/term-tablets.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _Preview__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Preview */ "./src/blocks/Preview.js");
/* harmony import */ var _ProUpsell__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ProUpsell */ "./src/blocks/ProUpsell.js");

/**
 * Term tablets block editor.
 */






const TAXONOMIES = [{
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Cities', 'staysuite-companion'),
  value: 'property_city'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Categories', 'staysuite-companion'),
  value: 'property_category'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Listing types', 'staysuite-companion'),
  value: 'property_action_category'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Areas', 'staysuite-companion'),
  value: 'property_area'
}];
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__.registerBlockType)('ssc/term-tablets', {
  title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('StaySuite Term Tablets', 'staysuite-companion'),
  icon: 'tag',
  category: 'widgets',
  attributes: {
    taxonomy: {
      type: 'string',
      default: 'property_city'
    },
    number: {
      type: 'number',
      default: 6
    },
    hide_empty: {
      type: 'boolean',
      default: true
    }
  },
  edit({
    attributes,
    setAttributes
  }) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Tablets settings', 'staysuite-companion')
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Source', 'staysuite-companion'),
      value: attributes.taxonomy,
      options: TAXONOMIES,
      onChange: taxonomy => setAttributes({
        taxonomy
      })
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.RangeControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('How many', 'staysuite-companion'),
      value: attributes.number,
      min: 1,
      max: 24,
      onChange: number => setAttributes({
        number
      })
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hide empty', 'staysuite-companion'),
      checked: attributes.hide_empty,
      onChange: hide_empty => setAttributes({
        hide_empty
      })
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ProUpsell__WEBPACK_IMPORTED_MODULE_6__["default"], {
      features: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Custom gradient palettes', 'staysuite-companion'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Sponsored tablet placement', 'staysuite-companion')]
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Preview__WEBPACK_IMPORTED_MODULE_5__["default"], {
      block: "ssc/term-tablets",
      attributes: attributes
    }));
  },
  save: () => null
});

/***/ },

/***/ "react"
/*!************************!*\
  !*** external "React" ***!
  \************************/
(module) {

module.exports = window["React"];

/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/block-editor"
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
(module) {

module.exports = window["wp"]["blockEditor"];

/***/ },

/***/ "@wordpress/blocks"
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
(module) {

module.exports = window["wp"]["blocks"];

/***/ },

/***/ "@wordpress/components"
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["components"];

/***/ },

/***/ "@wordpress/data"
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["data"];

/***/ },

/***/ "@wordpress/element"
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["element"];

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
/*!***********************!*\
  !*** ./src/editor.js ***!
  \***********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _blocks_term_tablets__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./blocks/term-tablets */ "./src/blocks/term-tablets.js");
/* harmony import */ var _blocks_listing_carousel__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./blocks/listing-carousel */ "./src/blocks/listing-carousel.js");
/* harmony import */ var _blocks_hero_search__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./blocks/hero-search */ "./src/blocks/hero-search.js");
/* harmony import */ var _blocks_group_booking__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./blocks/group-booking */ "./src/blocks/group-booking.js");
/* harmony import */ var _blocks_payment_strip__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./blocks/payment-strip */ "./src/blocks/payment-strip.js");
/**
 * Block editor entry: registers all companion blocks.
 */





})();

/******/ })()
;
//# sourceMappingURL=editor.js.map