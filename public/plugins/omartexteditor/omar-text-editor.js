"use strict";
var OmarTextEditor = (() => {
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

  // src/index.ts
  var index_exports = {};
  __export(index_exports, {
    Editor: () => Editor,
    default: () => index_default,
    init: () => init
  });

  // src/core/EventBus.ts
  var EventBus = class {
    constructor() {
      this.listeners = /* @__PURE__ */ new Map();
    }
    on(event, handler) {
      if (!this.listeners.has(event)) {
        this.listeners.set(event, /* @__PURE__ */ new Set());
      }
      this.listeners.get(event).add(handler);
    }
    off(event, handler) {
      this.listeners.get(event)?.delete(handler);
    }
    fire(event, payload) {
      this.listeners.get(event)?.forEach((handler) => handler(payload));
    }
    destroy() {
      this.listeners.clear();
    }
  };

  // src/core/Commands.ts
  var Commands = class {
    constructor() {
      this.handlers = /* @__PURE__ */ new Map();
    }
    register(name, handler) {
      this.handlers.set(name, handler);
    }
    has(name) {
      return this.handlers.has(name);
    }
    exec(name, value) {
      const handler = this.handlers.get(name);
      if (!handler) return false;
      handler(value);
      return true;
    }
  };

  // src/core/History.ts
  var COALESCE_WINDOW_MS = 500;
  var History = class {
    constructor(initialHtml, initialBookmark = null) {
      this.stack = [];
      this.index = -1;
      this.lastPushTime = 0;
      this.stack.push({ html: initialHtml, bookmark: initialBookmark });
      this.index = 0;
    }
    // `coalesce: true` is for genuine continuous-typing input only (see
    // Editor's 'input' listener) — it may merge into the immediately
    // preceding entry if they land within COALESCE_WINDOW_MS. Every other
    // caller (setContent, commands, plugin edits) must push a discrete
    // entry: without this distinction, several deliberate edits executed in
    // quick succession (e.g. a script calling setContent three times, or a
    // plugin's insert immediately followed by another) would silently
    // collapse into one undo step and lose the intermediate states — a real
    // bug this class had until an integration test caught it.
    push(html, bookmark = null, coalesce = false) {
      const now = Date.now();
      const isCoalescable = coalesce && now - this.lastPushTime < COALESCE_WINDOW_MS;
      this.lastPushTime = now;
      if (this.stack[this.index]?.html === html) return;
      if (isCoalescable && this.index === this.stack.length - 1 && this.index > 0) {
        this.stack[this.index] = { html, bookmark };
        return;
      }
      this.stack = this.stack.slice(0, this.index + 1);
      this.stack.push({ html, bookmark });
      this.index = this.stack.length - 1;
    }
    canUndo() {
      return this.index > 0;
    }
    canRedo() {
      return this.index < this.stack.length - 1;
    }
    undo() {
      if (!this.canUndo()) return null;
      this.index -= 1;
      return this.stack[this.index];
    }
    redo() {
      if (!this.canRedo()) return null;
      this.index += 1;
      return this.stack[this.index];
    }
  };

  // src/core/Schema.ts
  var ALLOWED_TAGS = /* @__PURE__ */ new Set([
    "p",
    "br",
    "div",
    "span",
    "strong",
    "b",
    "em",
    "i",
    "u",
    "s",
    "strike",
    "sup",
    "sub",
    "code",
    "h1",
    "h2",
    "h3",
    "h4",
    "h5",
    "h6",
    "ul",
    "ol",
    "li",
    "a",
    "img",
    "blockquote",
    "pre",
    "hr",
    "table",
    "thead",
    "tbody",
    "tr",
    "td",
    "th",
    "iframe",
    "video",
    "audio",
    "source"
  ]);
  var ALLOWED_ATTRS = /* @__PURE__ */ new Set([
    "href",
    "target",
    "rel",
    "title",
    "src",
    "alt",
    "width",
    "height",
    "id",
    "class",
    "style",
    "colspan",
    "rowspan",
    "frameborder",
    "allowfullscreen",
    "sandbox",
    "controls",
    "poster",
    "preload",
    "loop",
    "muted",
    "playsinline",
    "type"
  ]);
  var BLOCKED_URL_SCHEMES = ["javascript:", "data:text/html", "vbscript:"];
  function isSafeUrl(url) {
    const normalized = url.trim().toLowerCase();
    return !BLOCKED_URL_SCHEMES.some((scheme) => normalized.startsWith(scheme));
  }
  var ALLOWED_IFRAME_SRC_PREFIXES = [
    "https://www.youtube.com/embed/",
    "https://player.vimeo.com/video/",
    "https://www.google.com/maps/embed",
    "https://codepen.io/",
    "https://docs.google.com/presentation/d/",
    "https://docs.google.com/document/d/",
    "https://docs.google.com/spreadsheets/d/",
    "https://open.spotify.com/embed/"
  ];
  var IFRAME_EMBED_PROVIDER_NAMES = [
    "YouTube",
    "Vimeo",
    "Google Maps",
    "CodePen",
    "Google Slides",
    "Google Docs",
    "Google Sheets",
    "Spotify"
  ];
  function isAllowedIframeSrc(src) {
    const normalized = src.trim().toLowerCase();
    return ALLOWED_IFRAME_SRC_PREFIXES.some((prefix) => normalized.startsWith(prefix));
  }
  var UNSAFE_STYLE_PATTERNS = [/expression\s*\(/i, /javascript:/i, /vbscript:/i, /<script/i];
  function isSafeStyleValue(value) {
    return !UNSAFE_STYLE_PATTERNS.some((pattern) => pattern.test(value));
  }
  var BLOCK_TAGS = /* @__PURE__ */ new Set([
    "p",
    "div",
    "h1",
    "h2",
    "h3",
    "h4",
    "h5",
    "h6",
    "ul",
    "ol",
    "li",
    "blockquote",
    "pre",
    "table",
    "hr"
  ]);
  function isBlockTag(tagName) {
    return BLOCK_TAGS.has(tagName.toLowerCase());
  }

  // src/core/Serializer.ts
  function sanitizeHtml(html) {
    const template = document.createElement("template");
    template.innerHTML = html;
    sanitizeNode(template.content);
    return template.content;
  }
  function sanitizeNode(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT);
    const toUnwrap = [];
    let node = walker.nextNode();
    while (node) {
      const tag = node.tagName.toLowerCase();
      if (tag === "script" || tag === "style") {
        node.remove();
        node = walker.nextNode();
        continue;
      }
      if (tag === "iframe") {
        const src = node.getAttribute("src") ?? "";
        if (!isAllowedIframeSrc(src)) {
          node.remove();
          node = walker.nextNode();
          continue;
        }
        sanitizeAttributes(node);
        node.setAttribute("sandbox", "allow-scripts allow-same-origin allow-presentation");
        node = walker.nextNode();
        continue;
      }
      if (!ALLOWED_TAGS.has(tag)) {
        toUnwrap.push(node);
      } else {
        sanitizeAttributes(node);
      }
      node = walker.nextNode();
    }
    for (const el of toUnwrap) {
      const parent = el.parentNode;
      if (!parent) continue;
      while (el.firstChild) parent.insertBefore(el.firstChild, el);
      parent.removeChild(el);
    }
  }
  function sanitizeAttributes(el) {
    for (const attr of Array.from(el.attributes)) {
      const name = attr.name.toLowerCase();
      const isDataAttr = name.startsWith("data-");
      if (name.startsWith("on") || !isDataAttr && !ALLOWED_ATTRS.has(name)) {
        el.removeAttribute(attr.name);
        continue;
      }
      if ((name === "href" || name === "src" || name === "poster") && !isSafeUrl(attr.value)) {
        el.removeAttribute(attr.name);
        continue;
      }
      if (name === "style" && !isSafeStyleValue(attr.value)) {
        el.removeAttribute(attr.name);
      }
    }
  }
  function getContent(root) {
    return root.innerHTML.trim();
  }
  function wrapLooseInlineContent(root) {
    let run = [];
    const flush = () => {
      if (run.length === 0) return;
      const p = document.createElement("p");
      root.insertBefore(p, run[0]);
      for (const node of run) p.appendChild(node);
      run = [];
    };
    for (const child of Array.from(root.childNodes)) {
      const isBlock = child.nodeType === Node.ELEMENT_NODE && isBlockTag(child.tagName);
      if (isBlock) {
        flush();
      } else {
        run.push(child);
      }
    }
    flush();
  }
  function setContent(root, html) {
    root.innerHTML = "";
    root.appendChild(sanitizeHtml(html));
    wrapLooseInlineContent(root);
  }

  // src/core/Selection.ts
  function getCurrentRange() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function setRange(range) {
    const sel = window.getSelection();
    if (!sel) return;
    sel.removeAllRanges();
    sel.addRange(range);
  }
  function isSelectionInside(root) {
    const range = getCurrentRange();
    if (!range) return false;
    return root.contains(range.commonAncestorContainer);
  }
  function closestAncestor(root, predicate) {
    const range = getCurrentRange();
    if (!range) return null;
    let node = range.commonAncestorContainer;
    while (node && node !== root.parentNode) {
      if (node.nodeType === Node.ELEMENT_NODE && predicate(node)) {
        return node;
      }
      node = node.parentNode;
    }
    return null;
  }
  function pathTo(root, node) {
    const path = [];
    let current = node;
    while (current && current !== root) {
      const parent = current.parentNode;
      if (!parent) break;
      path.unshift(Array.prototype.indexOf.call(parent.childNodes, current));
      current = parent;
    }
    return path;
  }
  function nodeAtPath(root, path) {
    let current = root;
    for (const index of path) {
      const child = current.childNodes[index];
      if (!child) return null;
      current = child;
    }
    return current;
  }
  function bookmarkSelection(root) {
    const range = getCurrentRange();
    if (!range || !root.contains(range.commonAncestorContainer)) return null;
    return {
      startPath: pathTo(root, range.startContainer),
      startOffset: range.startOffset,
      endPath: pathTo(root, range.endContainer),
      endOffset: range.endOffset
    };
  }
  function restoreSelection(root, bookmark) {
    const startNode = nodeAtPath(root, bookmark.startPath);
    const endNode = nodeAtPath(root, bookmark.endPath);
    if (!startNode || !endNode) return false;
    const range = document.createRange();
    try {
      range.setStart(startNode, bookmark.startOffset);
      range.setEnd(endNode, bookmark.endOffset);
    } catch {
      return false;
    }
    setRange(range);
    return true;
  }

  // src/core/Keymap.ts
  function formatLabel(key, ctrl, shift) {
    const parts = [];
    if (ctrl) parts.push("Ctrl");
    if (shift) parts.push("Shift");
    parts.push(key.length === 1 ? key.toUpperCase() : key);
    return parts.join("+");
  }
  var Keymap = class {
    constructor() {
      this.bindings = [];
    }
    add(binding) {
      this.bindings.push({ ...binding, label: formatLabel(binding.key, binding.ctrl, binding.shift) });
    }
    match(e) {
      const key = e.key.toLowerCase();
      const mod = e.ctrlKey || e.metaKey;
      for (const b of this.bindings) {
        if (b.key !== key) continue;
        if (!!b.ctrl !== mod) continue;
        if (!!b.shift !== e.shiftKey) continue;
        return b.command;
      }
      return null;
    }
    // Returns the first binding's label for a given command, if any — used
    // by Toolbar to append a shortcut hint to a button's tooltip.
    labelFor(command) {
      return this.bindings.find((b) => b.command === command)?.label ?? null;
    }
    list() {
      return this.bindings;
    }
  };
  function createDefaultKeymap() {
    const km = new Keymap();
    km.add({ key: "b", ctrl: true, command: "bold" });
    km.add({ key: "i", ctrl: true, command: "italic" });
    km.add({ key: "u", ctrl: true, command: "underline" });
    km.add({ key: "x", ctrl: true, shift: true, command: "strikethrough" });
    km.add({ key: "z", ctrl: true, command: "undo" });
    km.add({ key: "z", ctrl: true, shift: true, command: "redo" });
    km.add({ key: "y", ctrl: true, command: "redo" });
    km.add({ key: "k", ctrl: true, command: "link" });
    km.add({ key: "]", ctrl: true, command: "indent" });
    km.add({ key: "[", ctrl: true, command: "outdent" });
    km.add({ key: "f", ctrl: true, command: "searchreplace" });
    return km;
  }

  // src/ui/IconPack.ts
  var ICONS = {
    bold: '<path d="M5 3h6a3.5 3.5 0 0 1 2.5 5.9A3.75 3.75 0 0 1 11.5 16H5V3Zm3 2v3.5h3a1.75 1.75 0 0 0 0-3.5H8Zm0 5.5V14h3.5a2 2 0 0 0 0-4H8Z"/>',
    italic: '<path d="M8 3h6v2h-2.2l-2.6 9H11v2H5v-2h2.2l2.6-9H8V3Z"/>',
    underline: '<path d="M5 3v7a5 5 0 0 0 10 0V3h-2v7a3 3 0 0 1-6 0V3H5ZM4 16h12v2H4v-2Z"/>',
    strikethrough: '<path d="M3 9.5h14v1.5H3v-1.5ZM7 5.5c0-1.4 1.6-2.5 3-2.5s3 .8 3 2.2c0 .5-.2.9-.5 1.3H10c0-.7-.5-1.5-1.3-1.5-.6 0-1.2.4-1.2 1 0 .4.2.6.6.9H6.4A2.4 2.4 0 0 1 7 5.5Zm-.4 6.5H8.2c-.1.4-.2.7-.2 1 0 .8.7 1.5 2 1.5 1 0 1.7-.5 1.7-1.2 0-.4-.2-.7-.6-1H13c.4.4.6.9.6 1.4 0 1.6-1.7 2.8-3.6 2.8-2.1 0-3.6-1.3-3.6-3 0-.2 0-.4.1-.5Z"/>',
    undo: '<path d="M7 4 3 8l4 4V9.5c3.3 0 6 2.3 6 5.5h1.5c0-4-3.4-7-7.5-7V4Z"/>',
    redo: '<path d="M13 4 17 8l-4 4V9.5c-3.3 0-6 2.3-6 5.5H5.5c0-4 3.4-7 7.5-7V4Z"/>',
    bulletList: '<path d="M4 5.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm0 4.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm0 4.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2ZM7 5h9v1.5H7V5Zm0 4.5h9V11H7V9.5Zm0 4.5h9v1.5H7V14Z"/>',
    numberList: '<path d="M3 4.8h1.6V7H3V4.8ZM3 9.3h1.6v.6H3.8v.6H3v.9h2.1v-2.6H3v.5Zm0 4.4h.8v.3H3v.8h1.6v-2.3H3.4v.4H4v.3H3v.5ZM7 5h9v1.5H7V5Zm0 4.5h9V11H7V9.5Zm0 4.5h9v1.5H7V14Z"/>',
    link: '<path d="M8.6 12.6a3 3 0 0 1 0-4.2l2-2a3 3 0 1 1 4.2 4.2l-1 1-1-1.1 1-1a1.5 1.5 0 1 0-2.1-2.1l-2 2a1.5 1.5 0 0 0 0 2.1l-1.1 1.1Zm2.8-5.2a3 3 0 0 1 0 4.2l-2 2a3 3 0 1 1-4.2-4.2l1-1 1 1.1-1 1a1.5 1.5 0 1 0 2.1 2.1l2-2a1.5 1.5 0 0 0 0-2.1l1.1-1.1Z"/>',
    image: '<path d="M3 4h14v12H3V4Zm1.5 1.5v9L8 11l2 2.2 3.5-4 3 3.3V5.5h-13Zm2.25 1a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Z"/>',
    table: '<path d="M3 4h14v12H3V4Zm1.5 1.5v2.75h4.25V5.5H4.5Zm5.75 0v2.75h5.25V5.5h-5.25Zm-5.75 4.25v2.75h4.25v-2.75H4.5Zm5.75 0v2.75h5.25v-2.75h-5.25Z"/>',
    code: '<path d="m7 5-4.5 5L7 15l1.4-1.2L4.9 10l3.5-3.8L7 5Zm6 0-1.4 1.2 3.5 3.8-3.5 3.8L13 15l4.5-5L13 5Z"/>',
    emoji: '<path d="M10 2.5a7.5 7.5 0 1 0 0 15 7.5 7.5 0 0 0 0-15Zm0 1.5a6 6 0 1 1 0 12 6 6 0 0 1 0-12ZM7.5 8a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm5 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm-5.4 3.5c.7 1.4 2.1 2.3 3.9 2.3s3.2-.9 3.9-2.3H7.1Z"/>',
    search: '<path d="M8.5 3a5.5 5.5 0 0 1 4.3 8.9l3.65 3.65-1.06 1.06-3.65-3.65A5.5 5.5 0 1 1 8.5 3Zm0 1.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>',
    visualBlocks: '<path d="M3 3h14v2H3V3Zm0 4h6v2H3V7Zm8 0h6v2h-6V7ZM3 11h14v2H3v-2Zm0 4h6v2H3v-2Zm8 0h6v2h-6v-2Z"/>',
    more: '<path d="M5 10a1.5 1.5 0 1 1 0 .01V10Zm5 0a1.5 1.5 0 1 1 0 .01V10Zm5 0a1.5 1.5 0 1 1 0 .01V10Z"/>',
    superscript: '<path d="M2.5 5 6 9.8 2.5 15h2l2.5-3.7L9.5 15h2L8 9.8 11.5 5h-2L7 8.6 4.5 5h-2Zm12-.8h1.2v3H17v.9h-3.6v-.7l1.9-1.8c.4-.4.6-.7.6-1s-.2-.5-.6-.5c-.3 0-.6.2-.9.5l-.6-.6c.4-.5.9-.8 1.6-.8.9 0 1.5.5 1.5 1.3 0 .5-.3 1-.9 1.5l-1 1h2.1V4.2Z"/>',
    subscript: '<path d="M2.5 5 6 9.8 2.5 15h2l2.5-3.7L9.5 15h2L8 9.8 11.5 5h-2L7 8.6 4.5 5h-2Zm12 9.2h1.2v3H17v.9h-3.6v-.7l1.9-1.8c.4-.4.6-.7.6-1s-.2-.5-.6-.5c-.3 0-.6.2-.9.5l-.6-.6c.4-.5.9-.8 1.6-.8.9 0 1.5.5 1.5 1.3 0 .5-.3 1-.9 1.5l-1 1h2.1v-1.8Z"/>',
    alignLeft: '<path d="M3 4h14v1.6H3V4Zm0 3.4h9V9H3V7.4ZM3 10.8h14v1.6H3v-1.6Zm0 3.4h9V16H3v-1.8Z"/>',
    alignCenter: '<path d="M3 4h14v1.6H3V4Zm2.5 3.4h9V9h-9V7.4ZM3 10.8h14v1.6H3v-1.6Zm2.5 3.4h9V16h-9v-1.8Z"/>',
    alignRight: '<path d="M3 4h14v1.6H3V4Zm5 3.4h9V9H8V7.4ZM3 10.8h14v1.6H3v-1.6Zm5 3.4h9V16H8v-1.8Z"/>',
    alignJustify: '<path d="M3 4h14v1.6H3V4Zm0 3.4h14V9H3V7.4ZM3 10.8h14v1.6H3v-1.6Zm0 3.4h14V16H3v-1.8Z"/>',
    clearFormat: '<path d="M4 4h10.5l-1.2 1.6H9.9l-.8 1.8 1 1-.6 1.3-3-3L4 4Zm3.3 5.9 1.3 1.3-1.9 4.3H5l2.3-5.6ZM10.8 5.5l4 4-1 2.3H12l.9-2-2-2 .9-2.3ZM3 15.5l12-11 .9 1-12 11-.9-1Z"/>',
    blockquote: '<path d="M5 5.5c-1.7 0-3 1.4-3 3.2 0 1.6 1.1 2.8 2.6 2.8.2 1.4-.6 2.6-2 3.2l.5 1c2.2-.8 3.5-2.6 3.5-5 0-2.9-.7-5.2-1.6-5.2Zm7 0c-1.7 0-3 1.4-3 3.2 0 1.6 1.1 2.8 2.6 2.8.2 1.4-.6 2.6-2 3.2l.5 1c2.2-.8 3.5-2.6 3.5-5 0-2.9-.7-5.2-1.6-5.2Z"/>',
    hr: '<path d="M3 9.2h14v1.6H3V9.2Z"/>',
    indent: '<path d="M3 4h14v1.6H3V4Zm6 3.4h8V9H9V7.4Zm0 3.4h8V12H9v-1.2ZM3 7v6l3.5-3L3 7Zm0 7.2h14v1.6H3v-1.6Z"/>',
    outdent: '<path d="M3 4h14v1.6H3V4Zm6 3.4h8V9H9V7.4Zm0 3.4h8V12H9v-1.2ZM6.5 7l-3.5 3 3.5 3V7ZM3 14.2h14v1.6H3v-1.6Z"/>',
    unlink: '<path d="M8.6 12.6a3 3 0 0 1 0-4.2l2-2a3 3 0 1 1 4.2 4.2l-1 1-1-1.1 1-1a1.5 1.5 0 1 0-2.1-2.1l-2 2a1.5 1.5 0 0 0 0 2.1l-1.1 1.1Zm2.8-5.2a3 3 0 0 1 0 4.2l-2 2a3 3 0 1 1-4.2-4.2l1-1 1 1.1-1 1a1.5 1.5 0 1 0 2.1 2.1l2-2a1.5 1.5 0 0 0 0-2.1l1.1-1.1ZM3 3l14 14-1 1L2 4l1-1Z"/>',
    anchor: '<path d="M10 2.6a2.2 2.2 0 1 1 0 4.4 2.2 2.2 0 0 1 0-4.4Zm-.8 5.1h1.6v6.7c1.9-.3 3.3-1.9 3.5-3.9H13v-1.1h3v1.1h-1.1c-.2 2.7-2.2 4.9-4.9 5.2-2.7-.3-4.7-2.5-4.9-5.2H4v-1.1h3v1.1H5.9c.2 2 1.6 3.6 3.5 3.9V7.7Z"/>',
    video: '<path d="M3 5h11v10H3V5Zm12.5 2.3 3-1.8v9l-3-1.8V7.3Z"/>',
    codeBlock: '<path d="M3 4h14v12H3V4Zm1.5 1.5v9h11v-9h-11Zm2 1.8 3 2.7-3 2.7-.9-1 2-1.7-2-1.7.9-1Zm5 5.4h3v1h-3v-1Z"/>',
    charmap: '<path d="M4 4h4v4H4V4Zm6 0h6v2h-6V4Zm0 3h4v2h-4V7ZM4 10h12v2H4v-2Zm0 4h8v2H4v-2Z"/>',
    spacing: '<path d="M3 3h14v14H3V3Zm1.5 1.5v11h11v-11h-11ZM9 7h2v1.6H9V7Zm0 4.4h2V13H9v-1.6ZM6 9.2h1.6v1.6H6V9.2Zm6.4 0H14v1.6h-1.6V9.2Z"/>',
    textColor: '<path d="M7.8 3h2.4l4.3 11h-2l-1-2.7H6.5l-1 2.7h-2L7.8 3Zm-.6 6.7h3.6L9 5.4 7.2 9.7ZM3 16h14v2H3v-2Z"/>',
    backColor: '<path d="M7.8 2h2.4l4.3 11h-2l-1-2.7H6.5l-1 2.7h-2L7.8 2Zm-.6 6.7h3.6L9 4.4 7.2 8.7ZM2 14.5h16V18H2v-3.5Z"/>',
    embed: '<path d="M3 4h14v12H3V4Zm1.5 1.5v9h11v-9h-11ZM7 9l-2.2 1.5L7 12v-1.4l1-.7-1-.7V9Zm6 0v1.2l-1 .7 1 .7V13l2.2-1.5L13 9Zm-3.6-1.2h1.2l-1.2 6H8.4l1-6Z"/>'
  };
  function getIconSvg(name) {
    const path = ICONS[name];
    if (!path) return "";
    return `<svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor" aria-hidden="true">${path}</svg>`;
  }
  function hasIcon(name) {
    return name in ICONS;
  }

  // src/ui/ColorPicker.ts
  var PALETTE = [
    "#000000",
    "#424242",
    "#636363",
    "#9c9c9c",
    "#cccccc",
    "#efefef",
    "#f7f7f7",
    "#ffffff",
    "#c0392b",
    "#e74c3c",
    "#e67e22",
    "#f1c40f",
    "#2ecc71",
    "#1abc9c",
    "#3498db",
    "#9b59b6",
    "#7f1d1d",
    "#991b1b",
    "#9a3412",
    "#854d0e",
    "#166534",
    "#115e59",
    "#1e3a8a",
    "#581c87"
  ];
  var ColorPicker = class {
    constructor(anchor, options) {
      this.handleOutsideClick = (e) => {
        if (!this.el.contains(e.target)) this.close();
      };
      this.handleKeydown = (e) => {
        if (e.key === "Escape") this.close();
      };
      this.el = document.createElement("div");
      this.el.className = "omar-text-editor-color-picker";
      const grid = document.createElement("div");
      grid.className = "omar-text-editor-color-picker-grid";
      for (const color of PALETTE) {
        const swatch = document.createElement("button");
        swatch.type = "button";
        swatch.className = "omar-text-editor-color-picker-swatch";
        swatch.style.backgroundColor = color;
        swatch.title = color;
        swatch.addEventListener("mousedown", (e) => e.preventDefault());
        swatch.addEventListener("click", () => {
          options.onPick(color);
          this.close();
        });
        grid.appendChild(swatch);
      }
      this.el.appendChild(grid);
      const customRow = document.createElement("label");
      customRow.className = "omar-text-editor-color-picker-custom";
      const customInput = document.createElement("input");
      customInput.type = "color";
      customInput.addEventListener("input", () => {
        options.onPick(customInput.value);
      });
      customInput.addEventListener("change", () => this.close());
      customRow.appendChild(customInput);
      customRow.appendChild(document.createTextNode("Custom\u2026"));
      this.el.appendChild(customRow);
      if (options.onClear) {
        const clearBtn = document.createElement("button");
        clearBtn.type = "button";
        clearBtn.className = "omar-text-editor-color-picker-clear";
        clearBtn.textContent = "Remove color";
        clearBtn.addEventListener("mousedown", (e) => e.preventDefault());
        clearBtn.addEventListener("click", () => {
          options.onClear?.();
          this.close();
        });
        this.el.appendChild(clearBtn);
      }
      document.body.appendChild(this.el);
      const rect = anchor.getBoundingClientRect();
      this.el.style.left = `${rect.left}px`;
      this.el.style.top = `${rect.bottom + 4}px`;
      document.addEventListener("mousedown", this.handleOutsideClick);
      document.addEventListener("keydown", this.handleKeydown);
    }
    close() {
      document.removeEventListener("mousedown", this.handleOutsideClick);
      document.removeEventListener("keydown", this.handleKeydown);
      this.el.remove();
    }
  };

  // src/ui/Toolbar.ts
  var BUILTIN_BUTTONS = {
    bold: {
      name: "bold",
      label: "Bold",
      icon: "bold",
      command: "bold",
      isActive: (e) => e.isInlineFormatActive("strong")
    },
    italic: {
      name: "italic",
      label: "Italic",
      icon: "italic",
      command: "italic",
      isActive: (e) => e.isInlineFormatActive("em")
    },
    underline: {
      name: "underline",
      label: "Underline",
      icon: "underline",
      command: "underline",
      isActive: (e) => e.isInlineFormatActive("u")
    },
    strikethrough: {
      name: "strikethrough",
      label: "Strikethrough",
      icon: "strikethrough",
      command: "strikethrough",
      isActive: (e) => e.isInlineFormatActive("s")
    },
    superscript: {
      name: "superscript",
      label: "Superscript",
      icon: "superscript",
      command: "superscript",
      isActive: (e) => e.isInlineFormatActive("sup")
    },
    subscript: {
      name: "subscript",
      label: "Subscript",
      icon: "subscript",
      command: "subscript",
      isActive: (e) => e.isInlineFormatActive("sub")
    },
    code: {
      name: "code",
      label: "Inline code",
      icon: "code",
      command: "inlineCode",
      isActive: (e) => e.isInlineFormatActive("code")
    },
    alignleft: {
      name: "alignleft",
      label: "Align left",
      icon: "alignLeft",
      command: "align",
      commandValue: "left",
      isActive: (e) => e.getCurrentAlign() === "left"
    },
    aligncenter: {
      name: "aligncenter",
      label: "Align center",
      icon: "alignCenter",
      command: "align",
      commandValue: "center",
      isActive: (e) => e.getCurrentAlign() === "center"
    },
    alignright: {
      name: "alignright",
      label: "Align right",
      icon: "alignRight",
      command: "align",
      commandValue: "right",
      isActive: (e) => e.getCurrentAlign() === "right"
    },
    alignjustify: {
      name: "alignjustify",
      label: "Justify",
      icon: "alignJustify",
      command: "align",
      commandValue: "justify",
      isActive: (e) => e.getCurrentAlign() === "justify"
    },
    forecolor: {
      name: "forecolor",
      label: "Text color",
      icon: "textColor",
      command: "foreColor",
      onClick: (editor, buttonEl) => {
        new ColorPicker(buttonEl, {
          onPick: (color) => editor.execCommand("foreColor", color),
          onClear: () => editor.execCommand("foreColor", "clear")
        });
      }
    },
    backcolor: {
      name: "backcolor",
      label: "Background color",
      icon: "backColor",
      command: "backColor",
      onClick: (editor, buttonEl) => {
        new ColorPicker(buttonEl, {
          onPick: (color) => editor.execCommand("backColor", color),
          onClear: () => editor.execCommand("backColor", "clear")
        });
      }
    },
    removeformat: { name: "removeformat", label: "Clear formatting", icon: "clearFormat", command: "removeFormat" },
    bullist: {
      name: "bullist",
      label: "Bulleted list",
      icon: "bulletList",
      command: "bullist",
      isActive: (e) => e.isListActive("ul")
    },
    numlist: {
      name: "numlist",
      label: "Numbered list",
      icon: "numberList",
      command: "numlist",
      isActive: (e) => e.isListActive("ol")
    },
    blockquote: {
      name: "blockquote",
      label: "Blockquote",
      icon: "blockquote",
      command: "blockquote",
      isActive: (e) => e.isBlockquoteActive()
    },
    hr: { name: "hr", label: "Horizontal rule", icon: "hr", command: "hr" },
    indent: { name: "indent", label: "Indent", icon: "indent", command: "indent" },
    outdent: { name: "outdent", label: "Outdent", icon: "outdent", command: "outdent" },
    undo: { name: "undo", label: "Undo", icon: "undo", command: "undo" },
    redo: { name: "redo", label: "Redo", icon: "redo", command: "redo" }
  };
  var BUILTIN_SELECTS = {
    blockformat: {
      name: "blockformat",
      label: "Block format",
      command: "blockFormat",
      options: [
        { label: "Paragraph", value: "p" },
        { label: "Heading 1", value: "h1" },
        { label: "Heading 2", value: "h2" },
        { label: "Heading 3", value: "h3" },
        { label: "Heading 4", value: "h4" },
        { label: "Heading 5", value: "h5" },
        { label: "Heading 6", value: "h6" },
        { label: "Preformatted", value: "pre" }
      ],
      getCurrentValue: (e) => e.getCurrentBlockFormat()
    },
    fontfamily: {
      name: "fontfamily",
      label: "Font family",
      command: "fontFamily",
      options: [
        { label: "System Font", value: "system" },
        { label: "Arial", value: "Arial, Helvetica, sans-serif" },
        { label: "Georgia", value: "Georgia, serif" },
        { label: "Times New Roman", value: '"Times New Roman", Times, serif' },
        { label: "Courier New", value: '"Courier New", Courier, monospace' },
        { label: "Verdana", value: "Verdana, Geneva, sans-serif" },
        { label: "Trebuchet MS", value: '"Trebuchet MS", sans-serif' }
      ],
      getCurrentValue: (e) => e.getCurrentFontFamily()
    },
    fontsize: {
      name: "fontsize",
      label: "Font size",
      command: "fontSize",
      options: [
        { label: "8pt", value: "8pt" },
        { label: "10pt", value: "10pt" },
        { label: "12pt", value: "12pt" },
        { label: "14pt", value: "14pt" },
        { label: "18pt", value: "18pt" },
        { label: "24pt", value: "24pt" },
        { label: "36pt", value: "36pt" },
        { label: "48pt", value: "48pt" }
      ],
      getCurrentValue: (e) => e.getCurrentFontSize()
    },
    lineheight: {
      name: "lineheight",
      label: "Line height",
      command: "lineHeight",
      options: [
        { label: "1", value: "1" },
        { label: "1.15", value: "1.15" },
        { label: "1.5", value: "1.5" },
        { label: "2", value: "2" },
        { label: "2.5", value: "2.5" },
        { label: "3", value: "3" }
      ],
      getCurrentValue: (e) => e.getCurrentLineHeight()
    }
  };
  function registerToolbarButton(def) {
    BUILTIN_BUTTONS[def.name] = def;
  }
  var Toolbar = class {
    constructor(editor, config) {
      this.buttons = [];
      this.selects = [];
      this.editor = editor;
      this.el = document.createElement("div");
      this.el.className = "omar-text-editor-toolbar";
      this.el.setAttribute("role", "toolbar");
      this.render(config);
      this.refresh();
      editor.on("SelectionChange", () => this.refresh());
      editor.on("change", () => this.refresh());
    }
    render(config) {
      const groups = config.trim().split("|").map((g) => g.trim()).filter(Boolean);
      groups.forEach((group, i) => {
        const groupEl = document.createElement("div");
        groupEl.className = "omar-text-editor-toolbar-group";
        group.split(/\s+/).forEach((name) => {
          if (BUILTIN_SELECTS[name]) {
            groupEl.appendChild(this.createSelect(BUILTIN_SELECTS[name]));
            return;
          }
          const def = BUILTIN_BUTTONS[name];
          if (!def) return;
          groupEl.appendChild(this.createButton(def));
        });
        this.el.appendChild(groupEl);
        if (i < groups.length - 1) {
          const divider = document.createElement("div");
          divider.className = "omar-text-editor-toolbar-divider";
          this.el.appendChild(divider);
        }
      });
    }
    createButton(def) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "omar-text-editor-toolbar-btn";
      const shortcut = this.editor.getKeymap().labelFor(def.command);
      btn.title = shortcut ? `${def.label} (${shortcut})` : def.label;
      btn.setAttribute("aria-label", def.label);
      btn.innerHTML = hasIcon(def.icon) ? getIconSvg(def.icon) : def.label;
      btn.addEventListener("mousedown", (e) => e.preventDefault());
      btn.addEventListener("click", () => {
        if (def.onClick) {
          def.onClick(this.editor, btn);
        } else {
          this.editor.execCommand(def.command, def.commandValue);
        }
        this.refresh();
      });
      this.buttons.push({ def, el: btn });
      return btn;
    }
    createSelect(def) {
      const select = document.createElement("select");
      select.className = "omar-text-editor-toolbar-select";
      select.title = def.label;
      select.setAttribute("aria-label", def.label);
      for (const opt of def.options) {
        const optionEl = document.createElement("option");
        optionEl.value = opt.value;
        optionEl.textContent = opt.label;
        select.appendChild(optionEl);
      }
      select.addEventListener("mousedown", (e) => e.stopPropagation());
      select.addEventListener("change", () => {
        this.editor.execCommand(def.command, select.value);
        this.refresh();
      });
      this.selects.push({ def, el: select });
      return select;
    }
    refresh() {
      for (const { def, el } of this.buttons) {
        if (!def.isActive) continue;
        el.classList.toggle("is-active", def.isActive(this.editor));
      }
      for (const { def, el } of this.selects) {
        const current = def.getCurrentValue?.(this.editor);
        if (current && [...el.options].some((o) => o.value === current)) {
          el.value = current;
        }
      }
    }
    destroy() {
      this.el.remove();
    }
  };

  // src/ui/StatusBar.ts
  var StatusBar = class {
    constructor() {
      this.segments = /* @__PURE__ */ new Map();
      this.el = document.createElement("div");
      this.el.className = "omar-text-editor-statusbar";
      this.branding = document.createElement("a");
      this.branding.className = "omar-text-editor-statusbar-branding";
      this.branding.href = "https://github.com/wholeomarfaruk";
      this.branding.target = "_blank";
      this.branding.rel = "noopener noreferrer";
      this.branding.textContent = "Powered by Omar Text Editor";
      this.el.appendChild(this.branding);
    }
    setSegment(name, text) {
      const segment = this.getOrCreateSegment(name);
      segment.textContent = text;
    }
    // Like setSegment, but lets the caller populate the segment with child
    // elements (e.g. clickable breadcrumb links) instead of plain text.
    setSegmentContent(name, children) {
      const segment = this.getOrCreateSegment(name);
      segment.replaceChildren(...children);
    }
    getOrCreateSegment(name) {
      let segment = this.segments.get(name);
      if (!segment) {
        segment = document.createElement("span");
        segment.className = "omar-text-editor-statusbar-segment";
        this.segments.set(name, segment);
        this.el.insertBefore(segment, this.branding);
      }
      return segment;
    }
    destroy() {
      this.el.remove();
    }
  };

  // src/ui/MenuBar.ts
  var MenuBar = class {
    constructor(editor, menus) {
      this.editor = editor;
      this.menus = menus;
      this.openMenu = null;
      this.handleOutsideClick = (e) => {
        if (!this.openMenu) return;
        const target = e.target;
        if (this.el.contains(target) || this.openMenu.contains(target)) return;
        this.closeAll();
      };
      this.handleKeydown = (e) => {
        if (e.key === "Escape" && this.openMenu) this.closeAll();
      };
      this.el = document.createElement("div");
      this.el.className = "omar-text-editor-menubar";
      this.el.setAttribute("role", "menubar");
      this.menus.forEach((menu) => this.el.appendChild(this.renderMenuLabel(menu)));
      document.addEventListener("mousedown", this.handleOutsideClick);
      document.addEventListener("keydown", this.handleKeydown);
    }
    renderMenuLabel(menu) {
      const label = document.createElement("button");
      label.type = "button";
      label.className = "omar-text-editor-menubar-label";
      label.textContent = menu.label;
      label.setAttribute("aria-haspopup", "true");
      label.addEventListener("mousedown", (e) => e.preventDefault());
      label.addEventListener("click", () => {
        if (this.openMenu) {
          const wasThisOne = label.nextElementSibling === this.openMenu;
          this.closeAll();
          if (wasThisOne) return;
        }
        this.openDropdown(label, menu.items);
      });
      label.addEventListener("mouseenter", () => {
        if (this.openMenu) {
          this.closeAll();
          this.openDropdown(label, menu.items);
        }
      });
      return label;
    }
    openDropdown(anchor, items) {
      const dropdown = this.buildDropdown(items, 0);
      anchor.insertAdjacentElement("afterend", dropdown);
      const rect = anchor.getBoundingClientRect();
      dropdown.style.left = `${rect.left}px`;
      dropdown.style.top = `${rect.bottom}px`;
      this.openMenu = dropdown;
    }
    buildDropdown(items, depth) {
      const dropdown = document.createElement("div");
      dropdown.className = "omar-text-editor-menubar-dropdown";
      dropdown.style.position = "fixed";
      dropdown.style.zIndex = String(1e3 + depth);
      for (const item of items) {
        if (item.separator) {
          const sep = document.createElement("div");
          sep.className = "omar-text-editor-menubar-separator";
          dropdown.appendChild(sep);
          continue;
        }
        const entry = document.createElement("button");
        entry.type = "button";
        entry.className = "omar-text-editor-menubar-item";
        entry.textContent = item.label;
        if (item.isActive?.(this.editor)) entry.classList.add("is-active");
        if (item.submenu) {
          entry.classList.add("has-submenu");
          let submenuEl = null;
          entry.addEventListener("mouseenter", () => {
            submenuEl?.remove();
            submenuEl = this.buildDropdown(item.submenu, depth + 1);
            entry.appendChild(submenuEl);
            const rect = entry.getBoundingClientRect();
            submenuEl.style.left = `${rect.right}px`;
            submenuEl.style.top = `${rect.top}px`;
          });
          entry.addEventListener("mouseleave", (e) => {
            const related = e.relatedTarget;
            if (related && submenuEl?.contains(related)) return;
            submenuEl?.remove();
            submenuEl = null;
          });
        } else {
          entry.addEventListener("mousedown", (e) => e.preventDefault());
          entry.addEventListener("click", () => {
            if (item.action) {
              item.action(this.editor, entry);
            } else if (item.command) {
              this.editor.execCommand(item.command, item.commandValue);
            }
            this.closeAll();
          });
        }
        dropdown.appendChild(entry);
      }
      return dropdown;
    }
    closeAll() {
      this.openMenu?.remove();
      this.openMenu = null;
    }
    destroy() {
      this.closeAll();
      document.removeEventListener("mousedown", this.handleOutsideClick);
      document.removeEventListener("keydown", this.handleKeydown);
      this.el.remove();
    }
  };

  // src/ui/MenuBarDefaults.ts
  function buildDefaultMenus() {
    return [
      {
        label: "File",
        items: [
          {
            label: "New document",
            action: (editor) => {
              if (confirm("Clear all content and start a new document?")) {
                editor.setContent("<p></p>");
              }
            }
          }
        ]
      },
      {
        label: "Edit",
        items: [
          { label: "Undo", command: "undo" },
          { label: "Redo", command: "redo" },
          { separator: true, label: "" },
          {
            label: "Select all",
            action: (editor) => {
              const root = editor.getEditableElement();
              root.focus();
              const range = document.createRange();
              range.selectNodeContents(root);
              const sel = window.getSelection();
              sel?.removeAllRanges();
              sel?.addRange(range);
            }
          },
          { separator: true, label: "" },
          { label: "Clear formatting", command: "removeFormat" }
        ]
      },
      {
        label: "View",
        items: [
          { label: "Toggle visual blocks", command: "visualblocks" },
          { label: "Show onboarding tour", command: "showOnboarding" }
        ]
      },
      {
        label: "Insert",
        items: [
          { label: "Link\u2026", command: "link" },
          { label: "Anchor\u2026", command: "anchor" },
          { separator: true, label: "" },
          { label: "Image\u2026", command: "image" },
          { label: "Media\u2026", command: "media" },
          { label: "Table", command: "table" },
          { label: "Code sample\u2026", command: "codesample" },
          { separator: true, label: "" },
          { label: "Emoji\u2026", command: "emoticons" },
          { label: "Special character\u2026", command: "charmap" },
          { label: "Horizontal rule", command: "hr" }
        ]
      },
      {
        label: "Format",
        items: [
          { label: "Bold", command: "bold", isActive: (e) => e.isInlineFormatActive("strong") },
          { label: "Italic", command: "italic", isActive: (e) => e.isInlineFormatActive("em") },
          { label: "Underline", command: "underline", isActive: (e) => e.isInlineFormatActive("u") },
          { label: "Strikethrough", command: "strikethrough", isActive: (e) => e.isInlineFormatActive("s") },
          { label: "Inline code", command: "inlineCode", isActive: (e) => e.isInlineFormatActive("code") },
          { separator: true, label: "" },
          {
            label: "Paragraph format",
            submenu: [
              { label: "Paragraph", command: "blockFormat", commandValue: "p" },
              { label: "Heading 1", command: "blockFormat", commandValue: "h1" },
              { label: "Heading 2", command: "blockFormat", commandValue: "h2" },
              { label: "Heading 3", command: "blockFormat", commandValue: "h3" },
              { label: "Heading 4", command: "blockFormat", commandValue: "h4" },
              { label: "Heading 5", command: "blockFormat", commandValue: "h5" },
              { label: "Heading 6", command: "blockFormat", commandValue: "h6" },
              { label: "Preformatted", command: "blockFormat", commandValue: "pre" }
            ]
          },
          {
            label: "Align",
            submenu: [
              { label: "Left", command: "align", commandValue: "left", isActive: (e) => e.getCurrentAlign() === "left" },
              { label: "Center", command: "align", commandValue: "center", isActive: (e) => e.getCurrentAlign() === "center" },
              { label: "Right", command: "align", commandValue: "right", isActive: (e) => e.getCurrentAlign() === "right" },
              { label: "Justify", command: "align", commandValue: "justify", isActive: (e) => e.getCurrentAlign() === "justify" }
            ]
          },
          { separator: true, label: "" },
          { label: "Paragraph spacing\u2026", command: "spacing" },
          { label: "Clear formatting", command: "removeFormat" }
        ]
      },
      {
        label: "Tools",
        items: [
          { label: "Search & Replace\u2026", command: "searchreplace" }
        ]
      },
      {
        label: "Table",
        items: [
          { label: "Insert table", command: "table" },
          { separator: true, label: "" },
          { label: "Row", submenu: [
            { label: "Insert row before", command: "tableInsertRowBefore" },
            { label: "Insert row after", command: "tableInsertRowAfter" },
            { label: "Delete row", command: "tableDeleteRow" }
          ] },
          { label: "Column", submenu: [
            { label: "Insert column before", command: "tableInsertColBefore" },
            { label: "Insert column after", command: "tableInsertColAfter" },
            { label: "Delete column", command: "tableDeleteCol" }
          ] },
          { separator: true, label: "" },
          { label: "Delete table", command: "tableDelete" }
        ]
      }
    ];
  }

  // src/core/Media.ts
  function getRange() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function toEmbedUrl(url) {
    const trimmed = url.trim();
    const youtube = trimmed.match(
      /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]{6,})/
    );
    if (youtube) return `https://www.youtube.com/embed/${youtube[1]}`;
    const vimeo = trimmed.match(/vimeo\.com\/(\d+)/);
    if (vimeo) return `https://player.vimeo.com/video/${vimeo[1]}`;
    if (/^https:\/\/www\.google\.com\/maps\/embed/i.test(trimmed)) return trimmed;
    const codepen = trimmed.match(/codepen\.io\/([\w-]+)\/pen\/([\w-]+)/);
    if (codepen) return `https://codepen.io/${codepen[1]}/embed/${codepen[2]}`;
    const slides = trimmed.match(/docs\.google\.com\/presentation\/d\/([\w-]+)/);
    if (slides) return `https://docs.google.com/presentation/d/${slides[1]}/embed`;
    const docs = trimmed.match(/docs\.google\.com\/document\/d\/([\w-]+)/);
    if (docs) return `https://docs.google.com/document/d/${docs[1]}/embed`;
    const sheets = trimmed.match(/docs\.google\.com\/spreadsheets\/d\/([\w-]+)/);
    if (sheets) return `https://docs.google.com/spreadsheets/d/${sheets[1]}/embed`;
    const spotify = trimmed.match(/open\.spotify\.com\/(track|album|playlist|episode|show)\/([\w-]+)/);
    if (spotify) return `https://open.spotify.com/embed/${spotify[1]}/${spotify[2]}`;
    return null;
  }
  var VIDEO_FILE_EXTENSIONS = ["mp4", "webm", "ogg", "ogv", "mov", "m4v"];
  var AUDIO_FILE_EXTENSIONS = ["mp3", "wav", "ogg", "oga", "m4a", "aac", "flac"];
  function isDirectVideoUrl(url) {
    const trimmed = url.trim().split(/[?#]/)[0] ?? "";
    const ext = trimmed.split(".").pop()?.toLowerCase();
    return !!ext && VIDEO_FILE_EXTENSIONS.includes(ext);
  }
  function isDirectAudioUrl(url) {
    const trimmed = url.trim().split(/[?#]/)[0] ?? "";
    const ext = trimmed.split(".").pop()?.toLowerCase();
    return !!ext && AUDIO_FILE_EXTENSIONS.includes(ext);
  }
  function classifyMediaUrl(url) {
    const trimmed = url.trim();
    if (!trimmed || !isSafeUrl(trimmed)) return null;
    if (toEmbedUrl(trimmed)) return "embed";
    if (isDirectAudioUrl(trimmed) && !isDirectVideoUrl(trimmed)) return "audio";
    if (/^https?:\/\//i.test(trimmed)) return "video";
    return null;
  }
  function buildEmbedWrapper(embedUrl) {
    const wrapper = document.createElement("div");
    wrapper.className = "omar-text-editor-media-embed";
    const iframe = document.createElement("iframe");
    iframe.src = embedUrl;
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("allowfullscreen", "true");
    iframe.setAttribute("sandbox", "allow-scripts allow-same-origin allow-presentation");
    wrapper.appendChild(iframe);
    return wrapper;
  }
  function buildVideoWrapper(attrs) {
    const wrapper = document.createElement("div");
    wrapper.className = "omar-text-editor-media-video";
    const video = document.createElement("video");
    video.src = attrs.url;
    video.setAttribute("controls", "");
    video.setAttribute("preload", "metadata");
    if (attrs.poster && isSafeUrl(attrs.poster)) video.setAttribute("poster", attrs.poster);
    if (attrs.width) video.style.width = /^\d+$/.test(attrs.width) ? `${attrs.width}px` : attrs.width;
    if (attrs.height) video.style.height = /^\d+$/.test(attrs.height) ? `${attrs.height}px` : attrs.height;
    wrapper.appendChild(video);
    return wrapper;
  }
  function buildAudioWrapper(attrs) {
    const wrapper = document.createElement("div");
    wrapper.className = "omar-text-editor-media-audio";
    const audio = document.createElement("audio");
    audio.src = attrs.url;
    audio.setAttribute("controls", "");
    audio.setAttribute("preload", "metadata");
    if (attrs.width) audio.style.width = /^\d+$/.test(attrs.width) ? `${attrs.width}px` : attrs.width;
    wrapper.appendChild(audio);
    return wrapper;
  }
  function buildWrapper(attrs) {
    const kind = classifyMediaUrl(attrs.url);
    if (!kind) return null;
    if (kind === "embed") {
      const embedUrl = toEmbedUrl(attrs.url);
      return { wrapper: buildEmbedWrapper(embedUrl), kind };
    }
    if (kind === "audio") {
      return { wrapper: buildAudioWrapper(attrs), kind };
    }
    return { wrapper: buildVideoWrapper(attrs), kind };
  }
  function insertMediaEmbed(root, attrs) {
    const built = buildWrapper(attrs);
    if (!built) return false;
    const range = getRange();
    if (range) {
      range.deleteContents();
      range.insertNode(built.wrapper);
      range.setStartAfter(built.wrapper);
      range.collapse(true);
      const sel = window.getSelection();
      sel?.removeAllRanges();
      sel?.addRange(range);
    } else {
      root.appendChild(built.wrapper);
    }
    return true;
  }
  function getSelectedMediaEmbed(root) {
    const findWrapper = (node) => {
      let current = node;
      while (current && current !== root.parentNode) {
        if (current.nodeType === Node.ELEMENT_NODE && (current.classList.contains("omar-text-editor-media-embed") || current.classList.contains("omar-text-editor-media-video") || current.classList.contains("omar-text-editor-media-audio"))) {
          return current;
        }
        current = current.parentNode;
      }
      return null;
    };
    const range = getRange();
    if (range) {
      const fromRange = findWrapper(range.startContainer);
      if (fromRange) return fromRange;
      const node = range.startContainer.nodeType === Node.ELEMENT_NODE ? range.startContainer.childNodes[range.startOffset] : null;
      if (node) {
        const fromChild = findWrapper(node);
        if (fromChild) return fromChild;
      }
    }
    const selection = window.getSelection();
    return findWrapper(selection?.anchorNode ?? null);
  }
  function getEmbedUrl(wrapper) {
    const iframe = wrapper.querySelector("iframe");
    if (iframe) return iframe.getAttribute("src") ?? "";
    const video = wrapper.querySelector("video");
    if (video) return video.getAttribute("src") ?? "";
    return wrapper.querySelector("audio")?.getAttribute("src") ?? "";
  }
  function getMediaAttrs(wrapper) {
    const wrapperWidth = wrapper.style.width || void 0;
    const video = wrapper.querySelector("video");
    if (video) {
      return {
        url: video.getAttribute("src") ?? "",
        poster: video.getAttribute("poster") ?? void 0,
        width: wrapperWidth ?? (video.style.width || void 0),
        height: video.style.height || void 0
      };
    }
    const audio = wrapper.querySelector("audio");
    if (audio) {
      return {
        url: audio.getAttribute("src") ?? "",
        width: wrapperWidth ?? (audio.style.width || void 0)
      };
    }
    return { url: getEmbedUrl(wrapper), width: wrapperWidth };
  }
  function updateMediaEmbed(wrapper, attrs) {
    const built = buildWrapper(attrs);
    if (!built) return false;
    wrapper.className = built.wrapper.className;
    wrapper.replaceChildren(...Array.from(built.wrapper.childNodes));
    wrapper.style.removeProperty("width");
    wrapper.style.removeProperty("max-width");
    return true;
  }
  function removeMediaEmbed(wrapper) {
    wrapper.remove();
  }
  function setMediaAlign(wrapper, align) {
    wrapper.style.removeProperty("float");
    wrapper.style.removeProperty("margin-left");
    wrapper.style.removeProperty("margin-right");
    if (align === "left") {
      wrapper.style.float = "left";
      wrapper.style.marginRight = "12px";
    } else if (align === "right") {
      wrapper.style.float = "right";
      wrapper.style.marginLeft = "12px";
    } else if (align === "center") {
      wrapper.style.marginLeft = "auto";
      wrapper.style.marginRight = "auto";
    }
  }
  function setMediaWidth(wrapper, widthPx) {
    const clamped = Math.max(80, Math.round(widthPx));
    wrapper.style.width = `${clamped}px`;
    wrapper.style.maxWidth = `${clamped}px`;
  }
  function setMediaFullWidth(wrapper) {
    wrapper.style.width = "100%";
    wrapper.style.maxWidth = "none";
  }

  // src/ui/ContextMenu.ts
  var ContextMenu = class {
    constructor(x, y, items) {
      this.handleOutsideClick = (e) => {
        if (!this.el.contains(e.target)) this.close();
      };
      this.handleKeydown = (e) => {
        if (e.key === "Escape") this.close();
      };
      this.el = document.createElement("div");
      this.el.className = "omar-text-editor-context-menu";
      this.el.style.left = `${x}px`;
      this.el.style.top = `${y}px`;
      for (const item of items) {
        if (item.separator) {
          const sep = document.createElement("div");
          sep.className = "omar-text-editor-context-menu-separator";
          this.el.appendChild(sep);
          continue;
        }
        const entry = document.createElement("button");
        entry.type = "button";
        entry.className = "omar-text-editor-context-menu-item";
        entry.textContent = item.label;
        entry.addEventListener("click", () => {
          item.onSelect();
          this.close();
        });
        this.el.appendChild(entry);
      }
      document.body.appendChild(this.el);
      this.clampToViewport();
      document.addEventListener("mousedown", this.handleOutsideClick);
      document.addEventListener("keydown", this.handleKeydown);
    }
    clampToViewport() {
      const rect = this.el.getBoundingClientRect();
      const overflowX = rect.right - window.innerWidth;
      const overflowY = rect.bottom - window.innerHeight;
      if (overflowX > 0) this.el.style.left = `${rect.left - overflowX}px`;
      if (overflowY > 0) this.el.style.top = `${rect.top - overflowY}px`;
    }
    close() {
      document.removeEventListener("mousedown", this.handleOutsideClick);
      document.removeEventListener("keydown", this.handleKeydown);
      this.el.remove();
    }
  };

  // src/ui/MediaResizer.ts
  var MEDIA_WRAPPER_SELECTOR = ".omar-text-editor-media-embed, .omar-text-editor-media-video, .omar-text-editor-media-audio, .omar-text-editor-embed";
  var MediaResizer = class {
    constructor(root) {
      this.root = root;
      this.handle = null;
      this.menuButton = null;
      this.activeWrapper = null;
      this.onCommit = null;
      // Multiple media-capable plugins (media, embed) can share one resizer;
      // each registers a builder that returns null for a wrapper it doesn't
      // own (a different wrapper class), so the first matching builder wins.
      this.menuBuilders = [];
      this.handleHover = (e) => {
        const target = e.target;
        const wrapper = target.closest?.(MEDIA_WRAPPER_SELECTOR);
        if (wrapper && this.root.contains(wrapper)) {
          this.select(wrapper);
        }
      };
      this.handleOutsideMouseDown = (e) => {
        if (!this.activeWrapper) return;
        const target = e.target;
        if (this.activeWrapper.contains(target) || this.handle?.contains(target) || this.menuButton?.contains(target)) {
          return;
        }
        this.deselect();
      };
      this.openMenu = () => {
        const wrapper = this.activeWrapper;
        if (!wrapper || !this.menuButton) return;
        for (const builder of this.menuBuilders) {
          const items = builder(wrapper);
          if (items) {
            const rect = this.menuButton.getBoundingClientRect();
            new ContextMenu(rect.right, rect.bottom, items);
            return;
          }
        }
      };
      this.reposition = () => {
        if (!this.activeWrapper) return;
        const rect = this.activeWrapper.getBoundingClientRect();
        if (this.handle) {
          this.handle.style.left = `${rect.right - 6}px`;
          this.handle.style.top = `${rect.bottom - 6}px`;
        }
        if (this.menuButton) {
          this.menuButton.style.left = `${rect.right - 26}px`;
          this.menuButton.style.top = `${rect.top + 6}px`;
        }
      };
      this.startDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const wrapper = this.activeWrapper;
        if (!wrapper) return;
        const startX = e.clientX;
        const startWidth = wrapper.getBoundingClientRect().width;
        const onMouseMove = (moveEvent) => {
          const delta = moveEvent.clientX - startX;
          setMediaWidth(wrapper, startWidth + delta);
          this.reposition();
        };
        const onMouseUp = () => {
          document.removeEventListener("mousemove", onMouseMove);
          document.removeEventListener("mouseup", onMouseUp);
          this.onCommit?.();
        };
        document.addEventListener("mousemove", onMouseMove);
        document.addEventListener("mouseup", onMouseUp);
      };
      root.addEventListener("mouseover", this.handleHover);
      document.addEventListener("mousedown", this.handleOutsideMouseDown, true);
      document.addEventListener("scroll", this.reposition, true);
    }
    onResizeCommit(cb) {
      this.onCommit = cb;
    }
    onMenuRequest(builder) {
      this.menuBuilders.push(builder);
    }
    select(wrapper) {
      if (this.activeWrapper === wrapper) return;
      this.deselect();
      this.activeWrapper = wrapper;
      wrapper.classList.add("omar-text-editor-media-selected");
      this.handle = document.createElement("div");
      this.handle.className = "omar-text-editor-media-resize-handle";
      document.body.appendChild(this.handle);
      this.handle.addEventListener("mousedown", this.startDrag);
      this.menuButton = document.createElement("button");
      this.menuButton.type = "button";
      this.menuButton.className = "omar-text-editor-media-menu-button";
      this.menuButton.setAttribute("aria-label", "Media options");
      this.menuButton.innerHTML = '<svg viewBox="0 0 20 20" width="14" height="14" fill="currentColor" aria-hidden="true"><circle cx="10" cy="4.5" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="15.5" r="1.6"/></svg>';
      this.menuButton.addEventListener("mousedown", (ev) => ev.preventDefault());
      this.menuButton.addEventListener("click", this.openMenu);
      document.body.appendChild(this.menuButton);
      this.reposition();
    }
    deselect() {
      this.activeWrapper?.classList.remove("omar-text-editor-media-selected");
      this.activeWrapper = null;
      this.handle?.remove();
      this.handle = null;
      this.menuButton?.remove();
      this.menuButton = null;
    }
    destroy() {
      this.deselect();
      this.root.removeEventListener("mouseover", this.handleHover);
      document.removeEventListener("mousedown", this.handleOutsideMouseDown, true);
      document.removeEventListener("scroll", this.reposition, true);
    }
  };

  // src/core/Formatter.ts
  var INLINE_TAG_ALIASES = {
    strong: ["strong", "b"],
    em: ["em", "i"],
    u: ["u"],
    s: ["s", "strike"],
    sup: ["sup"],
    sub: ["sub"],
    code: ["code"]
  };
  function getRange2() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function findAncestorTag(root, node, tags) {
    let current = node;
    while (current && current !== root.parentNode) {
      if (current.nodeType === Node.ELEMENT_NODE && tags.includes(current.tagName.toLowerCase())) {
        return current;
      }
      current = current.parentNode;
    }
    return null;
  }
  function toggleInline(root, canonicalTag) {
    const range = getRange2();
    if (!range) return;
    const aliases = INLINE_TAG_ALIASES[canonicalTag] ?? [canonicalTag];
    const existing = findAncestorTag(root, range.commonAncestorContainer, aliases);
    if (existing) {
      if (range.collapsed) unwrapCollapsed(existing);
      else unwrapElement(existing);
      return;
    }
    if (range.collapsed) {
      toggleInlineAtCaret(canonicalTag, range);
      return;
    }
    const wrapper = document.createElement(canonicalTag);
    try {
      range.surroundContents(wrapper);
    } catch {
      const fragment = range.extractContents();
      wrapper.appendChild(fragment);
      range.insertNode(wrapper);
    }
    const newRange = document.createRange();
    newRange.selectNodeContents(wrapper);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(newRange);
  }
  var CARET_MARKER = "\u200B";
  function toggleInlineAtCaret(canonicalTag, range) {
    const wrapper = document.createElement(canonicalTag);
    const marker = document.createTextNode(CARET_MARKER);
    wrapper.appendChild(marker);
    range.insertNode(wrapper);
    const newRange = document.createRange();
    newRange.setStart(marker, marker.length);
    newRange.collapse(true);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(newRange);
  }
  function unwrapCollapsed(existing) {
    const marker = document.createTextNode(CARET_MARKER);
    existing.parentNode?.insertBefore(marker, existing.nextSibling);
    const newRange = document.createRange();
    newRange.setStart(marker, 0);
    newRange.collapse(true);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(newRange);
  }
  function unwrapElement(el) {
    const parent = el.parentNode;
    if (!parent) return;
    const range = document.createRange();
    range.selectNodeContents(el);
    while (el.firstChild) parent.insertBefore(el.firstChild, el);
    parent.removeChild(el);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(range);
  }
  function isInlineActive(root, canonicalTag) {
    const range = getRange2();
    if (!range) return false;
    const aliases = INLINE_TAG_ALIASES[canonicalTag] ?? [canonicalTag];
    return findAncestorTag(root, range.commonAncestorContainer, aliases) !== null;
  }
  var BLOCK_TAGS2 = ["p", "h1", "h2", "h3", "h4", "h5", "h6", "blockquote", "pre"];
  function findBlockAncestor(root, node) {
    let current = node;
    while (current && current !== root.parentNode) {
      if (current.nodeType === Node.ELEMENT_NODE && BLOCK_TAGS2.includes(current.tagName.toLowerCase())) {
        return current;
      }
      current = current.parentNode;
    }
    return null;
  }
  function setBlockFormat(root, tag) {
    const range = getRange2();
    if (!range) return;
    const block = findBlockAncestor(root, range.commonAncestorContainer) ?? root;
    if (block === root) return;
    if (block.tagName.toLowerCase() === tag) return;
    const replacement = document.createElement(tag);
    while (block.firstChild) replacement.appendChild(block.firstChild);
    block.parentNode?.replaceChild(replacement, block);
    const newRange = document.createRange();
    newRange.selectNodeContents(replacement);
    newRange.collapse(false);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(newRange);
  }
  function getCurrentBlockFormat(root) {
    const range = getRange2();
    if (!range) return null;
    const block = findBlockAncestor(root, range.commonAncestorContainer);
    return block ? block.tagName.toLowerCase() : null;
  }
  function findExactWrappingSpan(range) {
    const startEl = range.startContainer.nodeType === Node.ELEMENT_NODE ? range.startContainer : range.startContainer.parentElement;
    const endEl = range.endContainer.nodeType === Node.ELEMENT_NODE ? range.endContainer : range.endContainer.parentElement;
    if (!startEl || !endEl) return null;
    let span = startEl.closest("span");
    while (span) {
      if (span.contains(endEl)) {
        if (rangeExactlySpansElement(range, span)) return span;
        return null;
      }
      span = span.parentElement?.closest("span") ?? null;
    }
    return null;
  }
  function resolveLeafBoundary(container, offset) {
    if (container.nodeType === Node.TEXT_NODE) return { node: container, offset };
    const child = container.childNodes[offset];
    if (child) {
      let node2 = child;
      while (node2.firstChild) node2 = node2.firstChild;
      return { node: node2, offset: 0 };
    }
    let node = container.lastChild ?? container;
    while (node.lastChild) node = node.lastChild;
    const endOffset = node.nodeType === Node.TEXT_NODE ? node.length : node.childNodes.length;
    return { node, offset: endOffset };
  }
  function rangeExactlySpansElement(range, el) {
    let firstLeaf = el;
    while (firstLeaf.firstChild) firstLeaf = firstLeaf.firstChild;
    let lastLeaf = el;
    while (lastLeaf.lastChild) lastLeaf = lastLeaf.lastChild;
    const lastLength = lastLeaf.nodeType === Node.TEXT_NODE ? lastLeaf.length : lastLeaf.childNodes.length;
    const start = resolveLeafBoundary(range.startContainer, range.startOffset);
    const end = resolveLeafBoundary(range.endContainer, range.endOffset);
    return start.node === firstLeaf && start.offset === 0 && end.node === lastLeaf && end.offset === lastLength;
  }
  function applyInlineStyle(root, prop, value) {
    const range = getRange2();
    if (!range || range.collapsed) return;
    const reusable = findExactWrappingSpan(range);
    if (reusable) {
      reusable.style.setProperty(prop, value);
      const newRange2 = document.createRange();
      newRange2.selectNodeContents(reusable);
      const sel2 = window.getSelection();
      sel2?.removeAllRanges();
      sel2?.addRange(newRange2);
      return;
    }
    const span = document.createElement("span");
    span.style.setProperty(prop, value);
    try {
      range.surroundContents(span);
    } catch {
      const fragment = range.extractContents();
      span.appendChild(fragment);
      range.insertNode(span);
    }
    const newRange = document.createRange();
    newRange.selectNodeContents(span);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(newRange);
  }
  function clearInlineStyle(root, prop) {
    const range = getRange2();
    if (!range || range.collapsed) return;
    const stripAndMaybeUnwrap = (el) => {
      el.style.removeProperty(prop);
      if (el.tagName.toLowerCase() === "span" && el.getAttribute("style") === "") {
        const parent = el.parentNode;
        if (!parent) return;
        while (el.firstChild) parent.insertBefore(el.firstChild, el);
        parent.removeChild(el);
      }
    };
    let ancestor = range.commonAncestorContainer;
    while (ancestor && ancestor !== root.parentNode) {
      if (ancestor.nodeType === Node.ELEMENT_NODE) stripAndMaybeUnwrap(ancestor);
      ancestor = ancestor.parentNode;
    }
    const container = range.commonAncestorContainer.nodeType === Node.ELEMENT_NODE ? range.commonAncestorContainer : range.commonAncestorContainer.parentElement;
    container?.querySelectorAll("*").forEach((el) => {
      if (range.intersectsNode(el)) stripAndMaybeUnwrap(el);
    });
  }
  function applyBlockStyle(root, prop, value) {
    const range = getRange2();
    if (!range) return;
    const block = findBlockAncestor(root, range.commonAncestorContainer);
    if (!block) return;
    block.style.setProperty(prop, value);
  }
  function applyBlockStyles(root, styles) {
    const range = getRange2();
    if (!range) return false;
    const block = findBlockAncestor(root, range.commonAncestorContainer);
    if (!block) return false;
    for (const [prop, value] of Object.entries(styles)) {
      if (value) block.style.setProperty(prop, value);
      else block.style.removeProperty(prop);
    }
    return true;
  }
  function getCurrentBlockStyle(root, prop) {
    const range = getRange2();
    if (!range) return null;
    const block = findBlockAncestor(root, range.commonAncestorContainer);
    if (!block) return null;
    return block.style.getPropertyValue(prop) || null;
  }
  function getCurrentInlineStyle(root, prop) {
    const range = getRange2();
    if (!range) return null;
    let current = range.commonAncestorContainer;
    while (current && current !== root.parentNode) {
      if (current.nodeType === Node.ELEMENT_NODE) {
        const value = current.style.getPropertyValue(prop);
        if (value) return value;
      }
      current = current.parentNode;
    }
    return null;
  }
  function clearFormatting(root) {
    const range = getRange2();
    if (!range || range.collapsed) return;
    const text = range.toString();
    const startContainer = range.startContainer;
    range.deleteContents();
    const originalParent = startContainer.nodeType === Node.ELEMENT_NODE ? startContainer : startContainer.parentNode;
    const textNode = document.createTextNode(text);
    if (originalParent && originalParent !== root) {
      originalParent.parentNode?.insertBefore(textNode, originalParent.nextSibling);
    } else {
      range.insertNode(textNode);
    }
    let ancestor = originalParent;
    while (ancestor && ancestor !== root) {
      const next = ancestor.parentNode;
      if (ancestor.nodeType === Node.ELEMENT_NODE && ancestor.childNodes.length === 0) {
        next?.removeChild(ancestor);
      }
      ancestor = next;
    }
    const newRange = document.createRange();
    newRange.selectNode(textNode);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(newRange);
  }

  // src/core/Lists.ts
  function getRange3() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function findAncestor(root, node, tags) {
    let current = node;
    while (current && current !== root.parentNode) {
      if (current.nodeType === Node.ELEMENT_NODE && tags.includes(current.tagName.toLowerCase())) {
        return current;
      }
      current = current.parentNode;
    }
    return null;
  }
  function findBlock(root, node) {
    return findAncestor(root, node, ["p", "div", "h1", "h2", "h3", "h4", "h5", "h6", "li", "blockquote"]);
  }
  function setCaretAt(node, offset) {
    const range = document.createRange();
    range.setStart(node, offset);
    range.setEnd(node, offset);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(range);
  }
  function toggleList(root, listTag) {
    const range = getRange3();
    if (!range) return;
    const existingLi = findAncestor(root, range.commonAncestorContainer, ["li"]);
    if (existingLi) {
      const list2 = existingLi.parentElement;
      if (list2 && list2.tagName.toLowerCase() === listTag) {
        unwrapListItem(root, existingLi, list2);
      } else if (list2) {
        const replacement = document.createElement(listTag);
        while (list2.firstChild) replacement.appendChild(list2.firstChild);
        list2.parentNode?.replaceChild(replacement, list2);
      }
      return;
    }
    const block = findBlock(root, range.commonAncestorContainer);
    if (!block) return;
    const list = document.createElement(listTag);
    const li = document.createElement("li");
    while (block.firstChild) li.appendChild(block.firstChild);
    list.appendChild(li);
    block.parentNode?.replaceChild(list, block);
    setCaretAt(li, li.childNodes.length);
  }
  function unwrapListItem(root, li, list) {
    const p = document.createElement("p");
    while (li.firstChild) p.appendChild(li.firstChild);
    const isOnlyItem = list.children.length === 1;
    if (isOnlyItem) {
      list.parentNode?.replaceChild(p, list);
    } else {
      list.parentNode?.insertBefore(p, list.nextSibling);
      li.remove();
    }
    setCaretAt(p, p.childNodes.length);
  }
  function isListActive(root, listTag) {
    const range = getRange3();
    if (!range) return false;
    const li = findAncestor(root, range.commonAncestorContainer, ["li"]);
    if (!li) return false;
    return li.parentElement?.tagName.toLowerCase() === listTag;
  }
  function indentListItem(root) {
    const range = getRange3();
    if (!range) return false;
    const li = findAncestor(root, range.commonAncestorContainer, ["li"]);
    if (!li) return false;
    const prevLi = li.previousElementSibling;
    if (!prevLi || prevLi.tagName.toLowerCase() !== "li") return false;
    const parentList = li.parentElement;
    const listTag = parentList.tagName.toLowerCase();
    let subList = prevLi.querySelector(`:scope > ${listTag}`);
    if (!subList) {
      subList = document.createElement(listTag);
      prevLi.appendChild(subList);
    }
    subList.appendChild(li);
    return true;
  }
  function outdentListItem(root) {
    const range = getRange3();
    if (!range) return false;
    const li = findAncestor(root, range.commonAncestorContainer, ["li"]);
    if (!li) return false;
    const parentList = li.parentElement;
    if (!parentList) return false;
    const grandparentLi = parentList.parentElement;
    if (!grandparentLi || grandparentLi.tagName.toLowerCase() !== "li") return false;
    const outerList = grandparentLi.parentElement;
    if (!outerList) return false;
    outerList.insertBefore(li, grandparentLi.nextSibling);
    if (parentList.children.length === 0) {
      parentList.remove();
    }
    return true;
  }
  function handleListEnter(root) {
    const range = getRange3();
    if (!range || !range.collapsed) return false;
    const li = findAncestor(root, range.commonAncestorContainer, ["li"]);
    if (!li) return false;
    if (li.textContent?.trim() !== "") return false;
    const list = li.parentElement;
    if (!list) return false;
    const p = document.createElement("p");
    p.innerHTML = "<br>";
    if (li.nextElementSibling) {
      list.parentNode?.insertBefore(p, list);
    } else {
      list.parentNode?.insertBefore(p, list.nextSibling);
    }
    li.remove();
    if (list.children.length === 0) list.remove();
    setCaretAt(p, 0);
    return true;
  }
  function handleListBackspace(root) {
    const range = getRange3();
    if (!range || !range.collapsed || range.startOffset !== 0) return false;
    const li = findAncestor(root, range.commonAncestorContainer, ["li"]);
    if (!li) return false;
    if (li.previousElementSibling) return false;
    const list = li.parentElement;
    if (!list) return false;
    const p = document.createElement("p");
    while (li.firstChild) p.appendChild(li.firstChild);
    if (!p.firstChild) p.innerHTML = "<br>";
    list.parentNode?.insertBefore(p, list);
    li.remove();
    if (list.children.length === 0) list.remove();
    setCaretAt(p, 0);
    return true;
  }

  // src/core/BlockOps.ts
  function getRange4() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function findAncestor2(root, node, tags) {
    let current = node;
    while (current && current !== root.parentNode) {
      if (current.nodeType === Node.ELEMENT_NODE && tags.includes(current.tagName.toLowerCase())) {
        return current;
      }
      current = current.parentNode;
    }
    return null;
  }
  function findBlock2(root, node) {
    return findAncestor2(root, node, ["p", "div", "h1", "h2", "h3", "h4", "h5", "h6", "blockquote"]);
  }
  function setCaretAt2(node, offset) {
    const range = document.createRange();
    range.setStart(node, offset);
    range.setEnd(node, offset);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(range);
  }
  function toggleBlockquote(root) {
    const range = getRange4();
    if (!range) return;
    const existing = findAncestor2(root, range.commonAncestorContainer, ["blockquote"]);
    if (existing) {
      const p = document.createElement("p");
      while (existing.firstChild) p.appendChild(existing.firstChild);
      existing.parentNode?.replaceChild(p, existing);
      setCaretAt2(p, p.childNodes.length);
      return;
    }
    const block = findBlock2(root, range.commonAncestorContainer);
    if (!block) return;
    const quote = document.createElement("blockquote");
    block.parentNode?.replaceChild(quote, block);
    quote.appendChild(block);
    setCaretAt2(block, block.childNodes.length);
  }
  function isBlockquoteActive(root) {
    const range = getRange4();
    if (!range) return false;
    return findAncestor2(root, range.commonAncestorContainer, ["blockquote"]) !== null;
  }
  function insertHorizontalRule(root) {
    const range = getRange4();
    if (!range) return;
    const hr = document.createElement("hr");
    const block = findBlock2(root, range.commonAncestorContainer);
    if (block) {
      block.parentNode?.insertBefore(hr, block.nextSibling);
    } else {
      range.insertNode(hr);
    }
    const p = document.createElement("p");
    p.innerHTML = "<br>";
    hr.parentNode?.insertBefore(p, hr.nextSibling);
    setCaretAt2(p, 0);
  }
  var INDENT_STEP_PX = 40;
  function indentBlock(root) {
    const range = getRange4();
    if (!range) return;
    const block = findBlock2(root, range.commonAncestorContainer);
    if (!block) return;
    const current = parseInt(block.style.marginLeft || "0", 10);
    block.style.marginLeft = `${current + INDENT_STEP_PX}px`;
  }
  function outdentBlock(root) {
    const range = getRange4();
    if (!range) return;
    const block = findBlock2(root, range.commonAncestorContainer);
    if (!block) return;
    const current = parseInt(block.style.marginLeft || "0", 10);
    const next = Math.max(0, current - INDENT_STEP_PX);
    if (next === 0) {
      block.style.removeProperty("margin-left");
    } else {
      block.style.marginLeft = `${next}px`;
    }
  }

  // src/core/Links.ts
  function getRange5() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function findAncestorLink(root, node) {
    let current = node;
    while (current && current !== root.parentNode) {
      if (current.nodeType === Node.ELEMENT_NODE && current.tagName === "A") {
        return current;
      }
      current = current.parentNode;
    }
    return null;
  }
  function insertOrUpdateLink(root, attrs) {
    if (!attrs.href || !isSafeUrl(attrs.href)) return false;
    const range = getRange5();
    if (!range) return false;
    const existing = findAncestorLink(root, range.commonAncestorContainer);
    if (existing) {
      applyAttrs(existing, attrs);
      return true;
    }
    const a = document.createElement("a");
    applyAttrs(a, attrs);
    if (range.collapsed) {
      a.textContent = attrs.text || attrs.href;
      range.insertNode(a);
    } else if (attrs.text) {
      range.deleteContents();
      a.textContent = attrs.text;
      range.insertNode(a);
    } else {
      try {
        range.surroundContents(a);
      } catch {
        const fragment = range.extractContents();
        a.appendChild(fragment);
        range.insertNode(a);
      }
    }
    const newRange = document.createRange();
    newRange.selectNodeContents(a);
    newRange.collapse(false);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(newRange);
    return true;
  }
  function applyAttrs(a, attrs) {
    a.setAttribute("href", attrs.href);
    if (attrs.title) a.setAttribute("title", attrs.title);
    else a.removeAttribute("title");
    if (attrs.newTab) {
      a.setAttribute("target", "_blank");
      a.setAttribute("rel", "noopener noreferrer");
    } else {
      a.removeAttribute("target");
      a.removeAttribute("rel");
    }
  }
  function removeLink(root) {
    const range = getRange5();
    if (!range) return false;
    const link = findAncestorLink(root, range.commonAncestorContainer);
    if (!link) return false;
    const parent = link.parentNode;
    if (!parent) return false;
    while (link.firstChild) parent.insertBefore(link.firstChild, link);
    parent.removeChild(link);
    return true;
  }
  function getCurrentLink(root) {
    const range = getRange5();
    if (!range) return null;
    return findAncestorLink(root, range.commonAncestorContainer);
  }
  function isLinkActive(root) {
    return getCurrentLink(root) !== null;
  }

  // src/core/Editor.ts
  var PLUGIN_REGISTRY = /* @__PURE__ */ new Map();
  function registerPlugin(name, setup) {
    PLUGIN_REGISTRY.set(name, setup);
  }
  var Editor = class {
    constructor(options) {
      this.events = new EventBus();
      this.commands = new Commands();
      this.keymap = createDefaultKeymap();
      this.toolbar = null;
      this.statusBar = null;
      this.menuBar = null;
      this.mediaResizer = null;
      this.options = options;
      const target = document.querySelector(options.selector);
      if (!target) {
        throw new Error(`OmarTextEditor: no element found for selector "${options.selector}"`);
      }
      let sibling = target.nextElementSibling;
      while (sibling) {
        if (sibling.classList.contains("omar-text-editor-content")) {
          throw new Error(
            `OmarTextEditor: an editor is already mounted for selector "${options.selector}". Call destroy() on the existing instance before creating a new one.`
          );
        }
        sibling = sibling.nextElementSibling;
      }
      this.targetEl = target;
      this.mount();
      this.registerBaseCommands();
      this.bindEvents();
      this.loadPlugins();
      this.mountMenuBar();
      this.mountToolbar();
      this.events.fire("init");
    }
    // Mounted after loadPlugins() (unlike the toolbar, which only needs
    // command names that are registered up front) because the menu's Insert
    // items call plugin dialog-opener functions directly — harmless to wire
    // even if that plugin wasn't enabled, but keeping the same load order as
    // dependency makes the intent clear.
    mountMenuBar() {
      if (!this.options.menubar) return;
      this.menuBar = new MenuBar(this, buildDefaultMenus());
      this.editableEl.insertAdjacentElement("beforebegin", this.menuBar.el);
    }
    loadPlugins() {
      for (const name of this.options.plugins ?? []) {
        PLUGIN_REGISTRY.get(name)?.(this);
      }
    }
    mountToolbar() {
      if (!this.options.toolbar) return;
      this.toolbar = new Toolbar(this, this.options.toolbar);
      this.editableEl.insertAdjacentElement("beforebegin", this.toolbar.el);
    }
    mount() {
      const initialHtml = this.targetEl instanceof HTMLTextAreaElement ? this.targetEl.value : this.targetEl.innerHTML;
      this.editableEl = document.createElement("div");
      this.editableEl.className = "omar-text-editor-content";
      this.editableEl.contentEditable = "true";
      setContent(this.editableEl, initialHtml);
      this.targetEl.style.display = "none";
      this.targetEl.insertAdjacentElement("afterend", this.editableEl);
      this.history = new History(getContent(this.editableEl));
    }
    pushHistory(coalesce = false) {
      this.history.push(getContent(this.editableEl), bookmarkSelection(this.editableEl), coalesce);
    }
    bindEvents() {
      this.editableEl.addEventListener("input", () => {
        this.syncToTarget();
        this.pushHistory(true);
        this.events.fire("change");
      });
      this.editableEl.addEventListener("keydown", (e) => {
        if (this.handleStructuralKeydown(e)) return;
        const command = this.keymap.match(e);
        if (!command) return;
        e.preventDefault();
        this.commands.exec(command);
      });
      this.editableEl.addEventListener("paste", (e) => {
        e.preventDefault();
        const html = e.clipboardData?.getData("text/html");
        const text = e.clipboardData?.getData("text/plain") ?? "";
        if (html) {
          this.insertFragmentAtCursor(sanitizeHtml(html));
        } else {
          this.insertFragmentAtCursor(document.createTextNode(text));
        }
        const bookmark = bookmarkSelection(this.editableEl);
        wrapLooseInlineContent(this.editableEl);
        if (bookmark) restoreSelection(this.editableEl, bookmark);
        this.syncToTarget();
        this.pushHistory();
        this.events.fire("change");
      });
      this.editableEl.addEventListener("keyup", () => this.fireSelectionChange());
      this.editableEl.addEventListener("mouseup", () => this.fireSelectionChange());
      this.editableEl.addEventListener("focus", () => this.fireSelectionChange());
    }
    // Structural keys (Tab/Shift+Tab for list nesting, Enter/Backspace for
    // list-exit behavior) need first refusal before the generic Keymap match,
    // since they depend on document structure rather than a fixed binding.
    // Returns true if the key was handled (and preventDefault'd).
    handleStructuralKeydown(e) {
      if (e.key === "Tab") {
        const handled = e.shiftKey ? outdentListItem(this.editableEl) : indentListItem(this.editableEl);
        if (handled) {
          e.preventDefault();
          this.afterStructuralEdit();
          return true;
        }
        return false;
      }
      if (e.key === "Enter" && !e.shiftKey) {
        if (handleListEnter(this.editableEl)) {
          e.preventDefault();
          this.afterStructuralEdit();
          return true;
        }
        return false;
      }
      if (e.key === "Backspace") {
        if (handleListBackspace(this.editableEl)) {
          e.preventDefault();
          this.afterStructuralEdit();
          return true;
        }
        return false;
      }
      return false;
    }
    afterStructuralEdit() {
      this.syncToTarget();
      this.pushHistory();
      this.events.fire("change");
    }
    fireSelectionChange() {
      if (isSelectionInside(this.editableEl)) {
        this.events.fire("SelectionChange");
      }
    }
    insertFragmentAtCursor(node) {
      const sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return;
      const range = sel.getRangeAt(0);
      range.deleteContents();
      range.insertNode(node);
      range.collapse(false);
    }
    registerBaseCommands() {
      const afterEdit = () => {
        this.syncToTarget();
        this.pushHistory();
        this.events.fire("change");
      };
      const toggleInlineTag = (tag) => {
        this.editableEl.focus();
        toggleInline(this.editableEl, tag);
        afterEdit();
      };
      this.commands.register("bold", () => toggleInlineTag("strong"));
      this.commands.register("italic", () => toggleInlineTag("em"));
      this.commands.register("underline", () => toggleInlineTag("u"));
      this.commands.register("strikethrough", () => toggleInlineTag("s"));
      this.commands.register("superscript", () => toggleInlineTag("sup"));
      this.commands.register("subscript", () => toggleInlineTag("sub"));
      this.commands.register("inlineCode", () => toggleInlineTag("code"));
      this.commands.register("blockFormat", (tag) => {
        if (!tag) return;
        this.editableEl.focus();
        setBlockFormat(this.editableEl, tag);
        afterEdit();
      });
      this.commands.register("foreColor", (color) => {
        if (!color) return;
        this.editableEl.focus();
        if (color === "clear") clearInlineStyle(this.editableEl, "color");
        else applyInlineStyle(this.editableEl, "color", color);
        afterEdit();
      });
      this.commands.register("backColor", (color) => {
        if (!color) return;
        this.editableEl.focus();
        if (color === "clear") clearInlineStyle(this.editableEl, "background-color");
        else applyInlineStyle(this.editableEl, "background-color", color);
        afterEdit();
      });
      this.commands.register("align", (value) => {
        if (!value) return;
        this.editableEl.focus();
        applyBlockStyle(this.editableEl, "text-align", value);
        afterEdit();
      });
      this.commands.register("fontFamily", (family) => {
        if (!family) return;
        this.editableEl.focus();
        if (family === "system") {
          clearInlineStyle(this.editableEl, "font-family");
        } else {
          applyInlineStyle(this.editableEl, "font-family", family);
        }
        afterEdit();
      });
      this.commands.register("fontSize", (size) => {
        if (!size) return;
        this.editableEl.focus();
        applyInlineStyle(this.editableEl, "font-size", size);
        afterEdit();
      });
      this.commands.register("lineHeight", (value) => {
        if (!value) return;
        this.editableEl.focus();
        applyBlockStyle(this.editableEl, "line-height", value);
        afterEdit();
      });
      this.commands.register("removeFormat", () => {
        this.editableEl.focus();
        clearFormatting(this.editableEl);
        afterEdit();
      });
      this.commands.register("bullist", () => {
        this.editableEl.focus();
        toggleList(this.editableEl, "ul");
        afterEdit();
      });
      this.commands.register("numlist", () => {
        this.editableEl.focus();
        toggleList(this.editableEl, "ol");
        afterEdit();
      });
      this.commands.register("blockquote", () => {
        this.editableEl.focus();
        toggleBlockquote(this.editableEl);
        afterEdit();
      });
      this.commands.register("hr", () => {
        this.editableEl.focus();
        insertHorizontalRule(this.editableEl);
        afterEdit();
      });
      this.commands.register("indent", () => {
        this.editableEl.focus();
        if (!indentListItem(this.editableEl)) indentBlock(this.editableEl);
        afterEdit();
      });
      this.commands.register("outdent", () => {
        this.editableEl.focus();
        if (!outdentListItem(this.editableEl)) outdentBlock(this.editableEl);
        afterEdit();
      });
      this.commands.register("__syncAfterExternalEdit", () => afterEdit());
      this.commands.register("undo", () => {
        const entry = this.history.undo();
        if (entry !== null) {
          setContent(this.editableEl, entry.html);
          if (entry.bookmark) restoreSelection(this.editableEl, entry.bookmark);
          this.syncToTarget();
          this.events.fire("change");
        }
      });
      this.commands.register("redo", () => {
        const entry = this.history.redo();
        if (entry !== null) {
          setContent(this.editableEl, entry.html);
          if (entry.bookmark) restoreSelection(this.editableEl, entry.bookmark);
          this.syncToTarget();
          this.events.fire("change");
        }
      });
    }
    syncToTarget() {
      if (this.targetEl instanceof HTMLTextAreaElement) {
        this.targetEl.value = this.getContent();
        this.targetEl.dispatchEvent(new Event("input", { bubbles: true }));
        this.targetEl.dispatchEvent(new Event("change", { bubbles: true }));
      }
    }
    getContent() {
      return getContent(this.editableEl);
    }
    setContent(html) {
      setContent(this.editableEl, html);
      this.syncToTarget();
      this.pushHistory();
      this.events.fire("change");
    }
    execCommand(name, value) {
      return this.commands.exec(name, value);
    }
    isActive(tagPredicate) {
      return closestAncestor(this.editableEl, tagPredicate) !== null;
    }
    isInlineFormatActive(canonicalTag) {
      return isInlineActive(this.editableEl, canonicalTag);
    }
    getCurrentBlockFormat() {
      return getCurrentBlockFormat(this.editableEl);
    }
    getCurrentAlign() {
      return getCurrentBlockStyle(this.editableEl, "text-align") || "left";
    }
    getCurrentLineHeight() {
      return getCurrentBlockStyle(this.editableEl, "line-height") || "1.15";
    }
    getCurrentFontFamily() {
      return getCurrentInlineStyle(this.editableEl, "font-family") || "system";
    }
    getCurrentFontSize() {
      return getCurrentInlineStyle(this.editableEl, "font-size") || "12pt";
    }
    getBlockSpacing() {
      return {
        top: getCurrentBlockStyle(this.editableEl, "padding-top") || "0",
        bottom: getCurrentBlockStyle(this.editableEl, "padding-bottom") || "0",
        left: getCurrentBlockStyle(this.editableEl, "padding-left") || "0",
        right: getCurrentBlockStyle(this.editableEl, "padding-right") || "0"
      };
    }
    // Sets the four padding sides on the current block independently — each
    // omitted/empty side is left untouched, not cleared, so a "Spacing"
    // dialog that only edits e.g. top/bottom doesn't wipe out left/right
    // that a previous submit set. Always writes onto the same block's style
    // in place; never wraps or adds an element, so repeated submits cannot
    // accumulate duplicate spacing.
    setBlockSpacing(sides) {
      this.editableEl.focus();
      const styles = {};
      if (sides.top !== void 0) styles["padding-top"] = sides.top;
      if (sides.bottom !== void 0) styles["padding-bottom"] = sides.bottom;
      if (sides.left !== void 0) styles["padding-left"] = sides.left;
      if (sides.right !== void 0) styles["padding-right"] = sides.right;
      return applyBlockStyles(this.editableEl, styles);
    }
    isListActive(listTag) {
      return isListActive(this.editableEl, listTag);
    }
    isBlockquoteActive() {
      return isBlockquoteActive(this.editableEl);
    }
    isLinkActive() {
      return isLinkActive(this.editableEl);
    }
    on(event, handler) {
      this.events.on(event, handler);
    }
    off(event, handler) {
      this.events.off(event, handler);
    }
    destroy() {
      this.syncToTarget();
      this.toolbar?.destroy();
      this.menuBar?.destroy();
      this.statusBar?.destroy();
      this.mediaResizer?.destroy();
      this.editableEl.remove();
      this.targetEl.style.display = "";
      this.events.destroy();
    }
    // Lazily creates and mounts a single shared status bar below the
    // editable area the first time any plugin (e.g. wordcount) asks for it,
    // so multiple status-producing plugins share one bar instead of each
    // creating their own.
    getStatusBar() {
      if (!this.statusBar) {
        this.statusBar = new StatusBar();
        this.editableEl.insertAdjacentElement("afterend", this.statusBar.el);
      }
      return this.statusBar;
    }
    // Lazily creates a single shared MediaResizer the first time any
    // media-like plugin (media, embed) asks for it, so having both plugins
    // enabled doesn't attach two independent click/resize handlers to the
    // same wrapper elements.
    getMediaResizer() {
      if (!this.mediaResizer) {
        this.mediaResizer = new MediaResizer(this.editableEl);
      }
      return this.mediaResizer;
    }
    getOptions() {
      return this.options;
    }
    getKeymap() {
      return this.keymap;
    }
    getEditableElement() {
      return this.editableEl;
    }
  };

  // src/ui/Dialog.ts
  var Dialog = class {
    constructor(config) {
      this.config = config;
      this.inputs = /* @__PURE__ */ new Map();
      this.handleKeydown = (e) => {
        if (e.key === "Escape") {
          e.preventDefault();
          this.cancel();
          return;
        }
        if (e.key === "Tab") {
          this.trapFocus(e);
        }
      };
      this.previouslyFocused = document.activeElement;
      this.overlayEl = document.createElement("div");
      this.overlayEl.className = "omar-text-editor-dialog-overlay";
      this.dialogEl = document.createElement("div");
      this.dialogEl.className = "omar-text-editor-dialog";
      this.dialogEl.setAttribute("role", "dialog");
      this.dialogEl.setAttribute("aria-modal", "true");
      this.render();
      this.overlayEl.appendChild(this.dialogEl);
      document.body.appendChild(this.overlayEl);
      this.overlayEl.addEventListener("mousedown", (e) => {
        if (e.target === this.overlayEl) this.cancel();
      });
      document.addEventListener("keydown", this.handleKeydown);
      this.focusFirstField();
    }
    render() {
      const header = document.createElement("div");
      header.className = "omar-text-editor-dialog-header";
      header.textContent = this.config.title;
      this.dialogEl.appendChild(header);
      const form = document.createElement("form");
      form.className = "omar-text-editor-dialog-body";
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        this.submit();
      });
      for (const field of this.config.fields) {
        form.appendChild(this.renderField(field));
      }
      const footer = document.createElement("div");
      footer.className = "omar-text-editor-dialog-footer";
      const cancelBtn = document.createElement("button");
      cancelBtn.type = "button";
      cancelBtn.textContent = "Cancel";
      cancelBtn.addEventListener("click", () => this.cancel());
      const submitBtn = document.createElement("button");
      submitBtn.type = "submit";
      submitBtn.className = "omar-text-editor-dialog-primary";
      submitBtn.textContent = this.config.submitLabel ?? "OK";
      footer.appendChild(cancelBtn);
      footer.appendChild(submitBtn);
      form.appendChild(footer);
      this.dialogEl.appendChild(form);
    }
    renderField(field) {
      if (field.type === "note") {
        const note = document.createElement("p");
        note.className = "omar-text-editor-dialog-note";
        note.textContent = field.label;
        return note;
      }
      const wrapper = document.createElement("label");
      wrapper.className = "omar-text-editor-dialog-field";
      const labelText = document.createElement("span");
      labelText.textContent = field.label;
      wrapper.appendChild(labelText);
      let input;
      if (field.type === "select") {
        input = document.createElement("select");
        for (const opt of field.options ?? []) {
          const optionEl = document.createElement("option");
          optionEl.value = opt.value;
          optionEl.textContent = opt.label;
          input.appendChild(optionEl);
        }
        if (typeof field.defaultValue === "string") input.value = field.defaultValue;
      } else if (field.type === "textarea") {
        input = document.createElement("textarea");
        if (typeof field.defaultValue === "string") input.value = field.defaultValue;
      } else {
        input = document.createElement("input");
        input.type = field.type === "checkbox" ? "checkbox" : "text";
        if (field.type === "checkbox") {
          input.checked = !!field.defaultValue;
        } else if (typeof field.defaultValue === "string") {
          input.value = field.defaultValue;
        }
      }
      input.name = field.name;
      this.inputs.set(field.name, input);
      wrapper.appendChild(input);
      return wrapper;
    }
    focusFirstField() {
      const first = this.inputs.values().next().value;
      first?.focus();
    }
    trapFocus(e) {
      const focusable = Array.from(
        this.dialogEl.querySelectorAll("input, select, textarea, button")
      );
      if (focusable.length === 0) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
    collectValues() {
      const values = {};
      for (const [name, input] of this.inputs) {
        values[name] = input instanceof HTMLInputElement && input.type === "checkbox" ? input.checked : input.value;
      }
      return values;
    }
    submit() {
      const values = this.collectValues();
      this.close();
      this.config.onSubmit(values);
    }
    cancel() {
      this.close();
      this.config.onCancel?.();
    }
    close() {
      document.removeEventListener("keydown", this.handleKeydown);
      this.overlayEl.remove();
      if (this.previouslyFocused instanceof HTMLElement) {
        this.previouslyFocused.focus();
      }
    }
  };
  var PickerDialog = class {
    constructor(config) {
      this.config = config;
      this.searchInput = null;
      this.handleKeydown = (e) => {
        if (e.key === "Escape") {
          e.preventDefault();
          this.close();
        }
      };
      this.previouslyFocused = document.activeElement;
      this.overlayEl = document.createElement("div");
      this.overlayEl.className = "omar-text-editor-dialog-overlay";
      this.dialogEl = document.createElement("div");
      this.dialogEl.className = "omar-text-editor-dialog omar-text-editor-picker-dialog";
      this.dialogEl.setAttribute("role", "dialog");
      this.dialogEl.setAttribute("aria-modal", "true");
      const header = document.createElement("div");
      header.className = "omar-text-editor-dialog-header";
      header.textContent = config.title;
      this.dialogEl.appendChild(header);
      if (config.searchPlaceholder !== void 0) {
        this.searchInput = document.createElement("input");
        this.searchInput.type = "text";
        this.searchInput.className = "omar-text-editor-picker-search";
        this.searchInput.placeholder = config.searchPlaceholder;
        this.dialogEl.appendChild(this.searchInput);
      }
      this.bodyEl = document.createElement("div");
      this.bodyEl.className = "omar-text-editor-picker-body";
      this.dialogEl.appendChild(this.bodyEl);
      this.overlayEl.appendChild(this.dialogEl);
      document.body.appendChild(this.overlayEl);
      this.overlayEl.addEventListener("mousedown", (e) => {
        if (e.target === this.overlayEl) this.close();
      });
      document.addEventListener("keydown", this.handleKeydown);
      (this.searchInput ?? this.dialogEl).focus();
    }
    close() {
      document.removeEventListener("keydown", this.handleKeydown);
      this.overlayEl.remove();
      if (this.previouslyFocused instanceof HTMLElement) {
        this.previouslyFocused.focus();
      }
      this.config.onClose?.();
    }
  };

  // src/ui/Notification.ts
  var _Notification = class _Notification {
    constructor(options) {
      this.timer = null;
      const container = _Notification.getContainer();
      this.el = document.createElement("div");
      this.el.className = `omar-text-editor-notification omar-text-editor-notification-${options.type ?? "info"}`;
      this.el.textContent = options.message;
      this.el.setAttribute("role", "status");
      const closeBtn = document.createElement("button");
      closeBtn.type = "button";
      closeBtn.className = "omar-text-editor-notification-close";
      closeBtn.textContent = "\xD7";
      closeBtn.addEventListener("click", () => this.close());
      this.el.appendChild(closeBtn);
      container.appendChild(this.el);
      const duration = options.durationMs ?? 4e3;
      if (duration > 0) {
        this.timer = setTimeout(() => this.close(), duration);
      }
    }
    static getContainer() {
      if (!_Notification.container || !document.body.contains(_Notification.container)) {
        _Notification.container = document.createElement("div");
        _Notification.container.className = "omar-text-editor-notification-container";
        document.body.appendChild(_Notification.container);
      }
      return _Notification.container;
    }
    close() {
      if (this.timer) clearTimeout(this.timer);
      this.el.remove();
    }
  };
  _Notification.container = null;
  var Notification = _Notification;

  // src/core/Autolink.ts
  var URL_PATTERN = /(https?:\/\/[^\s<]+|www\.[^\s<]+)$/i;
  var EMAIL_PATTERN = /([\w.+-]+@[\w-]+\.[a-z]{2,})$/i;
  function normalizeHref(match) {
    if (EMAIL_PATTERN.test(match) && !match.includes("://")) {
      return `mailto:${match}`;
    }
    if (match.toLowerCase().startsWith("www.")) {
      return `https://${match}`;
    }
    return match;
  }
  function tryAutolink(root, textNode, caretOffset) {
    const textBefore = textNode.data.slice(0, caretOffset);
    const urlMatch = textBefore.match(URL_PATTERN);
    const emailMatch = !urlMatch ? textBefore.match(EMAIL_PATTERN) : null;
    const match = urlMatch?.[0] ?? emailMatch?.[0];
    if (!match) return false;
    const matchStart = caretOffset - match.length;
    const range = document.createRange();
    range.setStart(textNode, matchStart);
    range.setEnd(textNode, caretOffset);
    const a = document.createElement("a");
    a.setAttribute("href", normalizeHref(match));
    a.textContent = match;
    range.deleteContents();
    range.insertNode(a);
    const caretRange = document.createRange();
    caretRange.setStartAfter(a);
    caretRange.collapse(true);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(caretRange);
    return true;
  }

  // src/plugins/links/index.ts
  function openLinkDialog(editor) {
    const root = editor.getEditableElement();
    const existing = getCurrentLink(root);
    const sel = window.getSelection();
    const selectedText = sel && !sel.isCollapsed ? sel.toString() : "";
    const bookmark = bookmarkSelection(root);
    new Dialog({
      title: existing ? "Edit Link" : "Insert Link",
      submitLabel: existing ? "Update" : "Insert",
      fields: [
        { name: "href", label: "URL", type: "text", defaultValue: existing?.getAttribute("href") ?? "" },
        {
          name: "text",
          label: "Text to display",
          type: "text",
          defaultValue: existing?.textContent ?? selectedText
        },
        { name: "title", label: "Title", type: "text", defaultValue: existing?.getAttribute("title") ?? "" },
        {
          name: "newTab",
          label: "Open in new tab",
          type: "checkbox",
          defaultValue: existing?.getAttribute("target") === "_blank"
        }
      ],
      onSubmit: (values) => {
        root.focus();
        if (bookmark) restoreSelection(root, bookmark);
        const ok = insertOrUpdateLink(root, {
          href: String(values.href).trim(),
          text: String(values.text ?? "").trim(),
          title: String(values.title ?? "").trim() || void 0,
          newTab: !!values.newTab
        });
        if (!ok) {
          new Notification({ message: "Please enter a valid URL.", type: "error" });
          return;
        }
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  function removeLinkAtCaret(editor) {
    const root = editor.getEditableElement();
    root.focus();
    if (removeLink(root)) {
      editor.execCommand("__syncAfterExternalEdit");
    }
  }
  function setupAutolink(editor) {
    const root = editor.getEditableElement();
    root.addEventListener("keyup", (e) => {
      if (e.key !== " " && e.key !== "Enter") return;
      const sel = window.getSelection();
      if (!sel || sel.rangeCount === 0 || !sel.isCollapsed) return;
      const range = sel.getRangeAt(0);
      const node = range.startContainer;
      if (node.nodeType !== Node.TEXT_NODE) return;
      const offset = Math.max(0, range.startOffset - 1);
      if (tryAutolink(root, node, offset)) {
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  registerToolbarButton({
    name: "link",
    label: "Insert/edit link",
    icon: "link",
    command: "link",
    isActive: (e) => e.isLinkActive()
  });
  registerToolbarButton({ name: "unlink", label: "Remove link", icon: "unlink", command: "unlink" });
  registerPlugin("link", (editor) => {
    editor.commands.register("link", () => openLinkDialog(editor));
    editor.commands.register("unlink", () => removeLinkAtCaret(editor));
    setupAutolink(editor);
  });
  registerPlugin("autolink", (editor) => {
    setupAutolink(editor);
  });

  // src/core/Anchor.ts
  function getRange6() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  var ANCHOR_ID_PATTERN = /^[A-Za-z][\w-]*$/;
  function isValidAnchorName(name) {
    return ANCHOR_ID_PATTERN.test(name);
  }
  function insertAnchor(root, name) {
    if (!isValidAnchorName(name)) return false;
    if (root.querySelector(`#${name}`)) return false;
    const range = getRange6();
    if (!range) return false;
    const anchor = document.createElement("a");
    anchor.id = name;
    anchor.className = "omar-text-editor-anchor";
    const collapsed = range.cloneRange();
    collapsed.collapse(true);
    collapsed.insertNode(anchor);
    const caretRange = document.createRange();
    caretRange.setStartAfter(anchor);
    caretRange.collapse(true);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(caretRange);
    return true;
  }
  function removeAnchor(root, name) {
    if (!isValidAnchorName(name)) return false;
    const el = root.querySelector(`#${name}.omar-text-editor-anchor`);
    if (!el) return false;
    el.remove();
    return true;
  }
  function getSelectedAnchor(root) {
    const findAnchor = (node) => {
      let current = node;
      while (current && current !== root.parentNode) {
        if (current.nodeType === Node.ELEMENT_NODE && current.classList.contains("omar-text-editor-anchor")) {
          return current;
        }
        current = current.parentNode;
      }
      return null;
    };
    const range = getRange6();
    if (range) {
      const fromRange = findAnchor(range.startContainer);
      if (fromRange) return fromRange;
      const node = range.startContainer.nodeType === Node.ELEMENT_NODE ? range.startContainer.childNodes[range.startOffset] : null;
      if (node) {
        const fromChild = findAnchor(node);
        if (fromChild) return fromChild;
      }
    }
    const selection = window.getSelection();
    return findAnchor(selection?.anchorNode ?? null);
  }
  function renameAnchor(root, anchor, name) {
    if (!isValidAnchorName(name)) return false;
    const existing = root.querySelector(`#${name}`);
    if (existing && existing !== anchor) return false;
    anchor.id = name;
    return true;
  }

  // src/plugins/anchor/index.ts
  function openAnchorDialog(editor, target) {
    const root = editor.getEditableElement();
    const existing = target ?? getSelectedAnchor(root);
    const bookmark = bookmarkSelection(root);
    new Dialog({
      title: existing ? "Edit Anchor" : "Insert Anchor",
      submitLabel: existing ? "Update" : "Insert",
      fields: [{ name: "name", label: "Anchor name", type: "text", defaultValue: existing?.id ?? "" }],
      onSubmit: (values) => {
        root.focus();
        const name = String(values.name ?? "").trim();
        if (!isValidAnchorName(name)) {
          new Notification({
            message: "Anchor name must start with a letter and contain only letters, numbers, - or _.",
            type: "error"
          });
          return;
        }
        const ok = existing ? renameAnchor(root, existing, name) : (() => {
          if (bookmark) restoreSelection(root, bookmark);
          return insertAnchor(root, name);
        })();
        if (!ok) {
          new Notification({ message: `An anchor named "${name}" already exists.`, type: "error" });
          return;
        }
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  function buildContextMenuItems(editor, anchor) {
    const root = editor.getEditableElement();
    return [
      { label: "Edit anchor\u2026", onSelect: () => openAnchorDialog(editor, anchor) },
      { label: "", onSelect: () => {
      }, separator: true },
      {
        label: "Remove anchor",
        onSelect: () => {
          removeAnchor(root, anchor.id);
          editor.execCommand("__syncAfterExternalEdit");
        }
      }
    ];
  }
  function setupAnchorContextMenu(editor) {
    const root = editor.getEditableElement();
    root.addEventListener("contextmenu", (e) => {
      const target = e.target;
      const anchor = target.closest?.(".omar-text-editor-anchor");
      if (!anchor || !root.contains(anchor)) return;
      e.preventDefault();
      new ContextMenu(e.clientX, e.clientY, buildContextMenuItems(editor, anchor));
    });
  }
  registerToolbarButton({ name: "anchor", label: "Insert anchor", icon: "anchor", command: "anchor" });
  registerPlugin("anchor", (editor) => {
    editor.commands.register("anchor", () => openAnchorDialog(editor));
    setupAnchorContextMenu(editor);
  });

  // src/ui/ImageResizer.ts
  var ImageResizer = class {
    constructor(root) {
      this.root = root;
      this.handle = null;
      this.activeImg = null;
      this.onCommit = null;
      this.handleClick = (e) => {
        const target = e.target;
        if (target instanceof HTMLImageElement && this.root.contains(target)) {
          this.select(target);
        } else {
          this.deselect();
        }
      };
      this.reposition = () => {
        if (!this.activeImg || !this.handle) return;
        const rect = this.activeImg.getBoundingClientRect();
        this.handle.style.left = `${rect.right - 6}px`;
        this.handle.style.top = `${rect.bottom - 6}px`;
      };
      this.startDrag = (e) => {
        e.preventDefault();
        const img = this.activeImg;
        if (!img) return;
        const startX = e.clientX;
        const startWidth = img.getBoundingClientRect().width;
        const aspectRatio = img.naturalHeight / img.naturalWidth || 1;
        const onMouseMove = (moveEvent) => {
          const delta = moveEvent.clientX - startX;
          const newWidth = Math.max(20, startWidth + delta);
          img.setAttribute("width", String(Math.round(newWidth)));
          img.setAttribute("height", String(Math.round(newWidth * aspectRatio)));
          this.reposition();
        };
        const onMouseUp = () => {
          document.removeEventListener("mousemove", onMouseMove);
          document.removeEventListener("mouseup", onMouseUp);
          this.onCommit?.();
        };
        document.addEventListener("mousemove", onMouseMove);
        document.addEventListener("mouseup", onMouseUp);
      };
      root.addEventListener("click", this.handleClick);
      document.addEventListener("scroll", this.reposition, true);
    }
    onResizeCommit(cb) {
      this.onCommit = cb;
    }
    select(img) {
      this.deselect();
      this.activeImg = img;
      img.classList.add("omar-text-editor-img-selected");
      this.handle = document.createElement("div");
      this.handle.className = "omar-text-editor-img-resize-handle";
      document.body.appendChild(this.handle);
      this.reposition();
      this.handle.addEventListener("mousedown", this.startDrag);
    }
    deselect() {
      this.activeImg?.classList.remove("omar-text-editor-img-selected");
      this.activeImg = null;
      this.handle?.remove();
      this.handle = null;
    }
    destroy() {
      this.deselect();
      this.root.removeEventListener("click", this.handleClick);
      document.removeEventListener("scroll", this.reposition, true);
    }
  };

  // src/core/Images.ts
  function getRange7() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function applyAlign(img, align) {
    img.style.removeProperty("float");
    img.style.removeProperty("display");
    img.style.removeProperty("margin-left");
    img.style.removeProperty("margin-right");
    if (align === "left") {
      img.style.float = "left";
      img.style.marginRight = "12px";
    } else if (align === "right") {
      img.style.float = "right";
      img.style.marginLeft = "12px";
    } else if (align === "center") {
      img.style.display = "block";
      img.style.marginLeft = "auto";
      img.style.marginRight = "auto";
    }
  }
  function insertOrUpdateImage(root, attrs, existing) {
    if (!attrs.src || !isSafeUrl(attrs.src)) return false;
    const img = existing ?? document.createElement("img");
    img.setAttribute("src", attrs.src);
    img.setAttribute("alt", attrs.alt ?? "");
    if (attrs.width) img.setAttribute("width", attrs.width);
    else img.removeAttribute("width");
    if (attrs.height) img.setAttribute("height", attrs.height);
    else img.removeAttribute("height");
    applyAlign(img, attrs.align);
    if (!existing) {
      const range = getRange7();
      if (!range) {
        root.appendChild(img);
      } else {
        range.deleteContents();
        range.insertNode(img);
        range.setStartAfter(img);
        range.collapse(true);
        const sel = window.getSelection();
        sel?.removeAllRanges();
        sel?.addRange(range);
      }
    }
    return true;
  }
  function setImageAlign(img, align) {
    applyAlign(img, align);
  }
  function getImageAlign(img) {
    if (img.style.float === "left") return "left";
    if (img.style.float === "right") return "right";
    if (img.style.display === "block" && img.style.marginLeft === "auto") return "center";
    return "none";
  }
  function removeImage(img) {
    img.remove();
  }
  function getSelectedImage(root) {
    const range = getRange7();
    if (range && range.startContainer.nodeType === Node.ELEMENT_NODE) {
      const node = range.startContainer.childNodes[range.startOffset];
      if (node instanceof HTMLImageElement) return node;
    }
    const selection = window.getSelection();
    const anchorNode = selection?.anchorNode;
    if (anchorNode instanceof HTMLImageElement && root.contains(anchorNode)) return anchorNode;
    return null;
  }
  function readImageFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = () => reject(reader.error);
      reader.readAsDataURL(file);
    });
  }

  // src/plugins/image/index.ts
  function openImageDialog(editor, target) {
    const root = editor.getEditableElement();
    const existing = target ?? getSelectedImage(root);
    const bookmark = bookmarkSelection(root);
    new Dialog({
      title: existing ? "Edit Image" : "Insert Image",
      submitLabel: existing ? "Update" : "Insert",
      fields: [
        { name: "src", label: "Image URL", type: "text", defaultValue: existing?.getAttribute("src") ?? "" },
        { name: "alt", label: "Alt text", type: "text", defaultValue: existing?.getAttribute("alt") ?? "" },
        { name: "width", label: "Width (px)", type: "text", defaultValue: existing?.getAttribute("width") ?? "" },
        { name: "height", label: "Height (px)", type: "text", defaultValue: existing?.getAttribute("height") ?? "" },
        {
          name: "align",
          label: "Alignment",
          type: "select",
          options: [
            { label: "None", value: "none" },
            { label: "Left", value: "left" },
            { label: "Center", value: "center" },
            { label: "Right", value: "right" }
          ],
          defaultValue: existing ? getImageAlign(existing) : "none"
        }
      ],
      onSubmit: (values) => {
        root.focus();
        if (!existing && bookmark) restoreSelection(root, bookmark);
        const ok = insertOrUpdateImage(
          root,
          {
            src: String(values.src).trim(),
            alt: String(values.alt ?? "").trim(),
            width: String(values.width ?? "").trim() || void 0,
            height: String(values.height ?? "").trim() || void 0,
            align: values.align
          },
          existing
        );
        if (!ok) {
          new Notification({ message: "Please enter a valid image URL.", type: "error" });
          return;
        }
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  async function insertImageFile(editor, file) {
    if (!file.type.startsWith("image/")) return;
    const root = editor.getEditableElement();
    root.focus();
    try {
      const dataUrl = await readImageFileAsDataUrl(file);
      insertOrUpdateImage(root, { src: dataUrl, alt: file.name });
      editor.execCommand("__syncAfterExternalEdit");
    } catch {
      new Notification({ message: "Could not read the image file.", type: "error" });
    }
  }
  function buildContextMenuItems2(editor, img) {
    const commit = () => editor.execCommand("__syncAfterExternalEdit");
    return [
      { label: "Edit image\u2026", onSelect: () => openImageDialog(editor, img) },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Align left", onSelect: () => {
        setImageAlign(img, "left");
        commit();
      } },
      { label: "Align center", onSelect: () => {
        setImageAlign(img, "center");
        commit();
      } },
      { label: "Align right", onSelect: () => {
        setImageAlign(img, "right");
        commit();
      } },
      { label: "No alignment", onSelect: () => {
        setImageAlign(img, "none");
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Remove image", onSelect: () => {
        removeImage(img);
        commit();
      } }
    ];
  }
  function setupImageContextMenu(editor) {
    const root = editor.getEditableElement();
    root.addEventListener("contextmenu", (e) => {
      const target = e.target;
      if (!(target instanceof HTMLImageElement) || !root.contains(target)) return;
      e.preventDefault();
      new ContextMenu(e.clientX, e.clientY, buildContextMenuItems2(editor, target));
    });
  }
  function setupDragAndDrop(editor) {
    const root = editor.getEditableElement();
    root.addEventListener("dragover", (e) => {
      if (e.dataTransfer?.types.includes("Files")) e.preventDefault();
    });
    root.addEventListener("drop", (e) => {
      const files = e.dataTransfer?.files;
      if (!files || files.length === 0) return;
      e.preventDefault();
      Array.from(files).forEach((file) => void insertImageFile(editor, file));
    });
  }
  function setupPasteImage(editor) {
    const root = editor.getEditableElement();
    root.addEventListener("paste", (e) => {
      const items = e.clipboardData?.items;
      if (!items) return;
      for (const item of Array.from(items)) {
        if (item.kind === "file" && item.type.startsWith("image/")) {
          const file = item.getAsFile();
          if (file) {
            e.preventDefault();
            void insertImageFile(editor, file);
          }
          return;
        }
      }
    });
  }
  registerToolbarButton({ name: "image", label: "Insert image", icon: "image", command: "image" });
  registerPlugin("image", (editor) => {
    editor.commands.register("image", () => openImageDialog(editor));
    setupDragAndDrop(editor);
    setupPasteImage(editor);
    setupImageContextMenu(editor);
    const resizer = new ImageResizer(editor.getEditableElement());
    resizer.onResizeCommit(() => editor.execCommand("__syncAfterExternalEdit"));
  });

  // src/plugins/media/index.ts
  var MEDIA_WRAPPER_CLASSES = [
    "omar-text-editor-media-embed",
    "omar-text-editor-media-video",
    "omar-text-editor-media-audio"
  ];
  function openMediaDialog(editor, target) {
    const root = editor.getEditableElement();
    const existing = target ?? getSelectedMediaEmbed(root);
    const current = existing ? getMediaAttrs(existing) : { url: "" };
    const bookmark = bookmarkSelection(root);
    new Dialog({
      title: existing ? "Edit Media" : "Insert Media",
      submitLabel: existing ? "Update" : "Insert",
      fields: [
        {
          name: "url",
          label: "Video URL",
          type: "text",
          defaultValue: current.url
        },
        {
          name: "_note",
          label: "YouTube: paste any watch/share/embed link (e.g. https://www.youtube.com/watch?v=... or https://youtu.be/...). Vimeo: paste the video page link (e.g. https://vimeo.com/123456789). Also supported: Google Maps embed links, CodePen pens, Google Slides/Docs/Sheets share links, and Spotify track/album/playlist links. Self-hosted / any other host: paste a direct link to the video or audio file itself (e.g. https://your-cdn.com/video.mp4 or .../track.mp3) - it plays with the browser's built-in player.",
          type: "note"
        },
        {
          name: "poster",
          label: "Poster image URL (optional, self-hosted video only)",
          type: "text",
          defaultValue: current.poster ?? ""
        },
        { name: "width", label: "Width (optional, e.g. 640)", type: "text", defaultValue: current.width ?? "" },
        { name: "height", label: "Height (optional, e.g. 360)", type: "text", defaultValue: current.height ?? "" }
      ],
      onSubmit: (values) => {
        root.focus();
        const attrs = {
          url: String(values.url ?? "").trim(),
          poster: String(values.poster ?? "").trim() || void 0,
          width: String(values.width ?? "").trim() || void 0,
          height: String(values.height ?? "").trim() || void 0
        };
        const ok = existing ? updateMediaEmbed(existing, attrs) : (() => {
          if (bookmark) restoreSelection(root, bookmark);
          return insertMediaEmbed(root, attrs);
        })();
        if (!ok) {
          new Notification({
            message: "Enter a link from a supported provider (YouTube, Vimeo, Google Maps/Slides/Docs/Sheets, Spotify, CodePen), or a direct video/audio URL (https://\u2026).",
            type: "error"
          });
          return;
        }
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  function buildMenuItems(editor, wrapper) {
    const commit = () => editor.execCommand("__syncAfterExternalEdit");
    return [
      { label: "Edit media\u2026", onSelect: () => openMediaDialog(editor, wrapper) },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Align left", onSelect: () => {
        setMediaAlign(wrapper, "left");
        commit();
      } },
      { label: "Align center", onSelect: () => {
        setMediaAlign(wrapper, "center");
        commit();
      } },
      { label: "Align right", onSelect: () => {
        setMediaAlign(wrapper, "right");
        commit();
      } },
      { label: "No alignment", onSelect: () => {
        setMediaAlign(wrapper, "none");
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Full width", onSelect: () => {
        setMediaFullWidth(wrapper);
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      {
        label: "Remove media",
        onSelect: () => {
          removeMediaEmbed(wrapper);
          commit();
        }
      }
    ];
  }
  registerToolbarButton({ name: "media", label: "Insert media", icon: "video", command: "media" });
  registerPlugin("media", (editor) => {
    editor.commands.register("media", () => openMediaDialog(editor));
    const resizer = editor.getMediaResizer();
    resizer.onResizeCommit(() => editor.execCommand("__syncAfterExternalEdit"));
    resizer.onMenuRequest((wrapper) => {
      if (!MEDIA_WRAPPER_CLASSES.some((cls) => wrapper.classList.contains(cls))) return null;
      return buildMenuItems(editor, wrapper);
    });
  });

  // src/core/Embed.ts
  function getRange8() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function extractIframeSrc(pastedText) {
    const match = pastedText.match(/<iframe[^>]*\ssrc\s*=\s*["']([^"']+)["']/i);
    return match ? match[1] : null;
  }
  function resolveEmbedSrc(pastedText) {
    const trimmed = pastedText.trim();
    if (!trimmed) return null;
    const iframeSrc = extractIframeSrc(trimmed);
    const candidate = iframeSrc ?? toEmbedUrl(trimmed) ?? (/^https:\/\/www\.google\.com\/maps\/embed/i.test(trimmed) ? trimmed : null);
    if (!candidate) return null;
    if (!isSafeUrl(candidate) || !isAllowedIframeSrc(candidate)) return null;
    return candidate;
  }
  function buildWrapper2(src) {
    const wrapper = document.createElement("div");
    wrapper.className = "omar-text-editor-embed";
    const iframe = document.createElement("iframe");
    iframe.src = src;
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("allowfullscreen", "true");
    iframe.setAttribute("sandbox", "allow-scripts allow-same-origin allow-presentation");
    wrapper.appendChild(iframe);
    return wrapper;
  }
  function insertEmbed(root, pastedText) {
    const src = resolveEmbedSrc(pastedText);
    if (!src) return false;
    const wrapper = buildWrapper2(src);
    const range = getRange8();
    if (range) {
      range.deleteContents();
      range.insertNode(wrapper);
      range.setStartAfter(wrapper);
      range.collapse(true);
      const sel = window.getSelection();
      sel?.removeAllRanges();
      sel?.addRange(range);
    } else {
      root.appendChild(wrapper);
    }
    return true;
  }
  function getSelectedEmbed(root) {
    const findWrapper = (node) => {
      let current = node;
      while (current && current !== root.parentNode) {
        if (current.nodeType === Node.ELEMENT_NODE && current.classList.contains("omar-text-editor-embed")) {
          return current;
        }
        current = current.parentNode;
      }
      return null;
    };
    const range = getRange8();
    if (range) {
      const fromRange = findWrapper(range.startContainer);
      if (fromRange) return fromRange;
      const node = range.startContainer.nodeType === Node.ELEMENT_NODE ? range.startContainer.childNodes[range.startOffset] : null;
      if (node) {
        const fromChild = findWrapper(node);
        if (fromChild) return fromChild;
      }
    }
    const selection = window.getSelection();
    return findWrapper(selection?.anchorNode ?? null);
  }
  function getEmbedSrc(wrapper) {
    return wrapper.querySelector("iframe")?.getAttribute("src") ?? "";
  }
  function updateEmbed(wrapper, pastedText) {
    const src = resolveEmbedSrc(pastedText);
    if (!src) return false;
    const built = buildWrapper2(src);
    wrapper.replaceChildren(...Array.from(built.childNodes));
    return true;
  }
  function removeEmbed(wrapper) {
    wrapper.remove();
  }

  // src/plugins/embed/index.ts
  var PROVIDER_LIST = IFRAME_EMBED_PROVIDER_NAMES.join(", ");
  function openEmbedDialog(editor, target) {
    const root = editor.getEditableElement();
    const existing = target ?? getSelectedEmbed(root);
    const current = existing ? getEmbedSrc(existing) : "";
    const bookmark = bookmarkSelection(root);
    new Dialog({
      title: existing ? "Edit Embed" : "Insert Embed",
      submitLabel: existing ? "Update" : "Insert",
      fields: [
        { name: "code", label: "Embed code or URL", type: "textarea", defaultValue: current },
        {
          name: "_note",
          label: `Paste an "Embed" iframe snippet from a supported provider, or just its share URL. Supported: ${PROVIDER_LIST}.`,
          type: "note"
        }
      ],
      onSubmit: (values) => {
        root.focus();
        const code = String(values.code ?? "").trim();
        const ok = existing ? updateEmbed(existing, code) : (() => {
          if (bookmark) restoreSelection(root, bookmark);
          return insertEmbed(root, code);
        })();
        if (!ok) {
          new Notification({
            message: `Couldn't recognize that embed. Supported providers: ${PROVIDER_LIST}.`,
            type: "error"
          });
          return;
        }
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  function buildMenuItems2(editor, wrapper) {
    const commit = () => editor.execCommand("__syncAfterExternalEdit");
    return [
      { label: "Edit embed\u2026", onSelect: () => openEmbedDialog(editor, wrapper) },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Align left", onSelect: () => {
        setMediaAlign(wrapper, "left");
        commit();
      } },
      { label: "Align center", onSelect: () => {
        setMediaAlign(wrapper, "center");
        commit();
      } },
      { label: "Align right", onSelect: () => {
        setMediaAlign(wrapper, "right");
        commit();
      } },
      { label: "No alignment", onSelect: () => {
        setMediaAlign(wrapper, "none");
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Full width", onSelect: () => {
        setMediaFullWidth(wrapper);
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      {
        label: "Remove embed",
        onSelect: () => {
          removeEmbed(wrapper);
          commit();
        }
      }
    ];
  }
  registerToolbarButton({ name: "embed", label: "Insert embed", icon: "embed", command: "embed" });
  registerPlugin("embed", (editor) => {
    editor.commands.register("embed", () => openEmbedDialog(editor));
    const resizer = editor.getMediaResizer();
    resizer.onResizeCommit(() => editor.execCommand("__syncAfterExternalEdit"));
    resizer.onMenuRequest((wrapper) => {
      if (!wrapper.classList.contains("omar-text-editor-embed")) return null;
      return buildMenuItems2(editor, wrapper);
    });
  });

  // src/ui/GridPicker.ts
  var GridPicker = class {
    constructor(anchor, options) {
      this.cells = [];
      this.handleOutsideClick = (e) => {
        if (!this.el.contains(e.target)) this.close();
      };
      this.handleKeydown = (e) => {
        if (e.key === "Escape") this.close();
      };
      const maxRows = options.maxRows ?? 8;
      const maxCols = options.maxCols ?? 8;
      this.el = document.createElement("div");
      this.el.className = "omar-text-editor-grid-picker";
      const grid = document.createElement("div");
      grid.className = "omar-text-editor-grid-picker-grid";
      grid.style.gridTemplateColumns = `repeat(${maxCols}, 18px)`;
      for (let r = 0; r < maxRows; r++) {
        const row = [];
        for (let c = 0; c < maxCols; c++) {
          const cell = document.createElement("div");
          cell.className = "omar-text-editor-grid-picker-cell";
          cell.addEventListener("mouseenter", () => this.highlight(r, c));
          cell.addEventListener("click", () => {
            options.onPick(r + 1, c + 1);
            this.close();
          });
          grid.appendChild(cell);
          row.push(cell);
        }
        this.cells.push(row);
      }
      this.label = document.createElement("div");
      this.label.className = "omar-text-editor-grid-picker-label";
      this.label.textContent = "0 x 0";
      this.el.appendChild(grid);
      this.el.appendChild(this.label);
      document.body.appendChild(this.el);
      const rect = anchor.getBoundingClientRect();
      this.el.style.left = `${rect.left}px`;
      this.el.style.top = `${rect.bottom + 4}px`;
      document.addEventListener("mousedown", this.handleOutsideClick);
      document.addEventListener("keydown", this.handleKeydown);
    }
    highlight(rows, cols) {
      for (let r = 0; r < this.cells.length; r++) {
        for (let c = 0; c < this.cells[r].length; c++) {
          this.cells[r][c].classList.toggle("is-active", r <= rows && c <= cols);
        }
      }
      this.label.textContent = `${rows + 1} x ${cols + 1}`;
    }
    close() {
      document.removeEventListener("mousedown", this.handleOutsideClick);
      document.removeEventListener("keydown", this.handleKeydown);
      this.el.remove();
    }
  };

  // src/core/Tables.ts
  function getRange9() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function findAncestor3(node, tags) {
    let current = node;
    while (current) {
      if (current.nodeType === Node.ELEMENT_NODE && tags.includes(current.tagName.toLowerCase())) {
        return current;
      }
      current = current.parentNode;
    }
    return null;
  }
  function setCaretAt3(node, offset) {
    const range = document.createRange();
    range.setStart(node, offset);
    range.setEnd(node, offset);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(range);
  }
  function insertTable(root, rows, cols, withHeader = false) {
    const table = document.createElement("table");
    const tbody = document.createElement("tbody");
    for (let r = 0; r < rows; r++) {
      const tr = document.createElement("tr");
      for (let c = 0; c < cols; c++) {
        const useHeader = withHeader && r === 0;
        const cell = document.createElement(useHeader ? "th" : "td");
        cell.innerHTML = "<br>";
        tr.appendChild(cell);
      }
      tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    const range = getRange9();
    if (range) {
      range.deleteContents();
      range.insertNode(table);
    } else {
      root.appendChild(table);
    }
    const p = document.createElement("p");
    p.innerHTML = "<br>";
    table.parentNode?.insertBefore(p, table.nextSibling);
    const firstCell = table.querySelector("td, th");
    if (firstCell) setCaretAt3(firstCell, 0);
  }
  function getCurrentCell(node) {
    return findAncestor3(node, ["td", "th"]);
  }
  function getCurrentTable(node) {
    return findAncestor3(node, ["table"]);
  }
  function insertRow(table, afterRow, before = false) {
    const cols = afterRow.children.length;
    const newRow = document.createElement("tr");
    for (let i = 0; i < cols; i++) {
      const cell = document.createElement("td");
      cell.innerHTML = "<br>";
      newRow.appendChild(cell);
    }
    afterRow.parentNode?.insertBefore(newRow, before ? afterRow : afterRow.nextSibling);
  }
  function deleteRow(row) {
    const table = findAncestor3(row, ["table"]);
    const tbody = row.parentElement;
    row.remove();
    if (table && tbody && tbody.children.length === 0) {
      table.remove();
    }
  }
  function insertColumn(table, atIndex, before = false) {
    const rows = table.querySelectorAll("tr");
    rows.forEach((row) => {
      const referenceCell = row.children[atIndex];
      const cell = document.createElement(referenceCell?.tagName.toLowerCase() === "th" ? "th" : "td");
      cell.innerHTML = "<br>";
      if (referenceCell) {
        row.insertBefore(cell, before ? referenceCell : referenceCell.nextSibling);
      } else {
        row.appendChild(cell);
      }
    });
  }
  function deleteColumn(table, atIndex) {
    const rows = table.querySelectorAll("tr");
    rows.forEach((row) => {
      row.children[atIndex]?.remove();
    });
    if (table.querySelector("tr")?.children.length === 0) {
      table.remove();
    }
  }
  function deleteTable(table) {
    table.remove();
  }
  function mergeCellRight(cell) {
    const next = cell.nextElementSibling;
    if (!next || next.tagName !== "TD" && next.tagName !== "TH") return false;
    while (next.firstChild) cell.appendChild(next.firstChild);
    const currentSpan = parseInt(cell.getAttribute("colspan") || "1", 10);
    const nextSpan = parseInt(next.getAttribute("colspan") || "1", 10);
    cell.setAttribute("colspan", String(currentSpan + nextSpan));
    next.remove();
    return true;
  }
  function splitCell(cell) {
    const span = parseInt(cell.getAttribute("colspan") || "1", 10);
    if (span <= 1) return false;
    cell.removeAttribute("colspan");
    for (let i = 1; i < span; i++) {
      const newCell = document.createElement(cell.tagName.toLowerCase());
      newCell.innerHTML = "<br>";
      cell.parentNode?.insertBefore(newCell, cell.nextSibling);
    }
    return true;
  }
  function toggleHeaderRow(table) {
    const firstRow = table.querySelector("tr");
    if (!firstRow) return;
    const isHeader = firstRow.children[0]?.tagName === "TH";
    const newTag = isHeader ? "td" : "th";
    Array.from(firstRow.children).forEach((cell) => {
      const replacement = document.createElement(newTag);
      replacement.innerHTML = cell.innerHTML;
      for (const attr of Array.from(cell.attributes)) {
        replacement.setAttribute(attr.name, attr.value);
      }
      cell.parentNode?.replaceChild(replacement, cell);
    });
  }
  function navigateCell(table, cell, forward) {
    const allCells = Array.from(table.querySelectorAll("td, th"));
    const currentIndex = allCells.indexOf(cell);
    if (currentIndex === -1) return false;
    if (forward && currentIndex === allCells.length - 1) {
      const lastRow = table.querySelector("tr:last-child");
      insertRow(table, lastRow, false);
      const newFirstCell = table.querySelector("tr:last-child td, tr:last-child th");
      if (newFirstCell) setCaretAt3(newFirstCell, 0);
      return true;
    }
    const targetIndex = forward ? currentIndex + 1 : currentIndex - 1;
    const target = allCells[targetIndex];
    if (!target) return false;
    setCaretAt3(target, 0);
    return true;
  }
  var COLUMN_RESIZE_MIN_PX = 30;
  function resizeColumn(table, colIndex, widthPx) {
    const clamped = Math.max(COLUMN_RESIZE_MIN_PX, widthPx);
    const rows = table.querySelectorAll("tr");
    rows.forEach((row) => {
      const cell = row.children[colIndex];
      if (cell) cell.style.width = `${clamped}px`;
    });
  }
  var ROW_RESIZE_MIN_PX = 20;
  function resizeRow(table, rowIndex, heightPx) {
    const clamped = Math.max(ROW_RESIZE_MIN_PX, heightPx);
    const row = table.querySelectorAll("tr")[rowIndex];
    if (row) row.style.height = `${clamped}px`;
  }
  function getCellPosition(table, cell) {
    const rows = Array.from(table.querySelectorAll("tr"));
    const occupied = [];
    for (let r = 0; r < rows.length; r++) {
      occupied[r] = occupied[r] || [];
      const cellsInRow = Array.from(rows[r].children);
      let c = 0;
      for (const rowCell of cellsInRow) {
        while (occupied[r][c]) c++;
        const rowspan = parseInt(rowCell.getAttribute("rowspan") || "1", 10);
        const colspan = parseInt(rowCell.getAttribute("colspan") || "1", 10);
        for (let dr = 0; dr < rowspan; dr++) {
          occupied[r + dr] = occupied[r + dr] || [];
          for (let dc = 0; dc < colspan; dc++) {
            occupied[r + dr][c + dc] = true;
          }
        }
        if (rowCell === cell) return { row: r, col: c };
        c += colspan;
      }
    }
    return null;
  }
  function getCellRange(table, cellA, cellB) {
    const posA = getCellPosition(table, cellA);
    const posB = getCellPosition(table, cellB);
    if (!posA || !posB) return [];
    const minRow = Math.min(posA.row, posB.row);
    const maxRow = Math.max(posA.row, posB.row);
    const minCol = Math.min(posA.col, posB.col);
    const maxCol = Math.max(posA.col, posB.col);
    const result = [];
    const rows = table.querySelectorAll("tr");
    rows.forEach((row) => {
      Array.from(row.children).forEach((child) => {
        const cell = child;
        const pos = getCellPosition(table, cell);
        if (pos && pos.row >= minRow && pos.row <= maxRow && pos.col >= minCol && pos.col <= maxCol) {
          result.push(cell);
        }
      });
    });
    return result;
  }
  function mergeCellRange(table, cells) {
    if (cells.length === 0) return null;
    if (cells.length === 1) return cells[0];
    const positioned = cells.map((cell) => ({ cell, pos: getCellPosition(table, cell) })).filter((entry) => entry.pos).sort((a, b) => a.pos.row - b.pos.row || a.pos.col - b.pos.col);
    const minRow = Math.min(...positioned.map((p) => p.pos.row));
    const maxRow = Math.max(...positioned.map((p) => p.pos.row));
    const minCol = Math.min(...positioned.map((p) => p.pos.col));
    const maxCol = Math.max(...positioned.map((p) => p.pos.col));
    const target = positioned[0].cell;
    for (const { cell } of positioned) {
      if (cell === target) continue;
      while (cell.firstChild) target.appendChild(cell.firstChild);
      cell.remove();
    }
    const rowSpan = maxRow - minRow + 1;
    const colSpan = maxCol - minCol + 1;
    if (rowSpan > 1) target.setAttribute("rowspan", String(rowSpan));
    else target.removeAttribute("rowspan");
    if (colSpan > 1) target.setAttribute("colspan", String(colSpan));
    else target.removeAttribute("colspan");
    if (!target.firstChild) target.innerHTML = "<br>";
    return target;
  }
  function deleteRows(table, cells) {
    const rows = /* @__PURE__ */ new Set();
    cells.forEach((cell) => {
      const row = cell.parentElement;
      if (row) rows.add(row);
    });
    rows.forEach((row) => deleteRow(row));
  }
  function deleteColumns(table, cells) {
    const cols = /* @__PURE__ */ new Set();
    cells.forEach((cell) => {
      const pos = getCellPosition(table, cell);
      if (pos) cols.add(pos.col);
    });
    Array.from(cols).sort((a, b) => b - a).forEach((colIndex) => deleteColumn(table, colIndex));
  }

  // src/ui/ColumnResizer.ts
  var ColumnResizer = class {
    constructor(root) {
      this.root = root;
      this.handles = [];
      this.onCommit = null;
      // Re-syncs handle positions on ANY layout change to a tracked table —
      // not just the `input`/`change` events that trigger a full refresh()
      // rescan. Content reflow (e.g. long unbroken text wrapping, or a table
      // column growing to fit typed text) shifts a table's actual on-screen
      // box without firing either of those events, which otherwise leaves
      // handles pointing at stale coordinates from before the reflow.
      this.resizeObserver = typeof ResizeObserver !== "undefined" ? new ResizeObserver(() => this.repositionAll()) : null;
      this.refresh();
      root.addEventListener("input", () => this.refresh());
    }
    onResizeCommit(cb) {
      this.onCommit = cb;
    }
    refresh() {
      this.clearHandles();
      const tables = this.root.querySelectorAll("table");
      tables.forEach((table) => {
        this.resizeObserver?.observe(table);
        this.attachHandles(table);
      });
    }
    repositionAll() {
      for (const handle of this.handles) {
        this.positionHandle(handle.el, handle.table, handle.colIndex);
      }
    }
    clearHandles() {
      this.handles.forEach((h) => h.el.remove());
      this.handles = [];
      this.resizeObserver?.disconnect();
    }
    attachHandles(table) {
      const firstRow = table.querySelector("tr");
      if (!firstRow) return;
      const cellCount = firstRow.children.length;
      table.style.position = "relative";
      for (let i = 0; i < cellCount - 1; i++) {
        const handle = document.createElement("div");
        handle.className = "omar-text-editor-col-resize-handle";
        table.parentElement?.style.setProperty("position", "relative");
        this.positionHandle(handle, table, i);
        handle.addEventListener("mousedown", (e) => this.startDrag(e, table, i));
        table.parentElement?.appendChild(handle);
        this.handles.push({ el: handle, table, colIndex: i });
      }
    }
    positionHandle(handle, table, colIndex) {
      const firstRow = table.querySelector("tr");
      const cell = firstRow?.children[colIndex];
      const parent = table.parentElement;
      if (!cell || !parent) return;
      const tableRect = table.getBoundingClientRect();
      const cellRect = cell.getBoundingClientRect();
      const parentRect = parent.getBoundingClientRect();
      handle.style.left = `${cellRect.right - parentRect.left - 3}px`;
      handle.style.top = `${tableRect.top - parentRect.top}px`;
      handle.style.height = `${tableRect.height}px`;
    }
    // Locks every column's current rendered width as an explicit pixel value
    // before a drag starts, and switches the table itself from its default
    // 100%-of-container width to an explicit pixel width (the sum of those
    // columns). Without this, resizing only the dragged column (with
    // `table-layout: fixed`) leaves the other columns free to shrink — the
    // browser redistributes the table's fixed total width among them, so the
    // neighboring border visibly slides in too. Locking both the columns and
    // the table's own width means only the dragged column's width changes,
    // and the table grows/shrinks overall instead — the standard behavior.
    lockColumnWidths(table) {
      const firstRow = table.querySelector("tr");
      if (!firstRow) return;
      const rows = Array.from(table.querySelectorAll("tr"));
      let total = 0;
      Array.from(firstRow.children).forEach((headCell, colIndex) => {
        const headEl = headCell;
        const width = headEl.style.width ? parseFloat(headEl.style.width) : headEl.getBoundingClientRect().width;
        total += width;
        rows.forEach((row) => {
          const cell = row.children[colIndex];
          if (cell && !cell.style.width) cell.style.width = `${width}px`;
        });
      });
      if (!table.style.width) table.style.width = `${total}px`;
    }
    startDrag(e, table, colIndex) {
      e.preventDefault();
      e.stopPropagation();
      this.lockColumnWidths(table);
      const firstRow = table.querySelector("tr");
      const cell = firstRow.children[colIndex];
      const startX = e.clientX;
      const startWidth = cell.getBoundingClientRect().width;
      const startTableWidth = table.getBoundingClientRect().width;
      const onMouseMove = (moveEvent) => {
        const delta = moveEvent.clientX - startX;
        resizeColumn(table, colIndex, startWidth + delta);
        const newColWidth = Math.max(COLUMN_RESIZE_MIN_PX, startWidth + delta);
        table.style.width = `${Math.round(startTableWidth + (newColWidth - startWidth))}px`;
        this.repositionAll();
      };
      const onMouseUp = () => {
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
        this.onCommit?.();
      };
      document.addEventListener("mousemove", onMouseMove);
      document.addEventListener("mouseup", onMouseUp);
    }
    destroy() {
      this.clearHandles();
    }
  };

  // src/ui/RowResizer.ts
  var RowResizer = class {
    constructor(root) {
      this.root = root;
      this.handles = [];
      this.onCommit = null;
      // See ColumnResizer's identical field: keeps handle positions in sync
      // with layout changes (e.g. content reflow) that don't fire `input`.
      this.resizeObserver = typeof ResizeObserver !== "undefined" ? new ResizeObserver(() => this.repositionAll()) : null;
      this.refresh();
      root.addEventListener("input", () => this.refresh());
    }
    onResizeCommit(cb) {
      this.onCommit = cb;
    }
    refresh() {
      this.clearHandles();
      const tables = this.root.querySelectorAll("table");
      tables.forEach((table) => {
        this.resizeObserver?.observe(table);
        this.attachHandles(table);
      });
    }
    repositionAll() {
      for (const handle of this.handles) {
        this.positionHandle(handle.el, handle.table, handle.rowIndex);
      }
    }
    clearHandles() {
      this.handles.forEach((h) => h.el.remove());
      this.handles = [];
      this.resizeObserver?.disconnect();
    }
    attachHandles(table) {
      const rows = table.querySelectorAll("tr");
      if (rows.length === 0) return;
      table.style.position = "relative";
      for (let i = 0; i < rows.length - 1; i++) {
        const handle = document.createElement("div");
        handle.className = "omar-text-editor-row-resize-handle";
        table.parentElement?.style.setProperty("position", "relative");
        this.positionHandle(handle, table, i);
        handle.addEventListener("mousedown", (e) => this.startDrag(e, table, i));
        table.parentElement?.appendChild(handle);
        this.handles.push({ el: handle, table, rowIndex: i });
      }
    }
    positionHandle(handle, table, rowIndex) {
      const row = table.querySelectorAll("tr")[rowIndex];
      const parent = table.parentElement;
      if (!row || !parent) return;
      const tableRect = table.getBoundingClientRect();
      const rowRect = row.getBoundingClientRect();
      const parentRect = parent.getBoundingClientRect();
      handle.style.top = `${rowRect.bottom - parentRect.top - 3}px`;
      handle.style.left = `${tableRect.left - parentRect.left}px`;
      handle.style.width = `${tableRect.width}px`;
    }
    startDrag(e, table, rowIndex) {
      e.preventDefault();
      e.stopPropagation();
      const row = table.querySelectorAll("tr")[rowIndex];
      const startY = e.clientY;
      const startHeight = row.getBoundingClientRect().height;
      const onMouseMove = (moveEvent) => {
        const delta = moveEvent.clientY - startY;
        resizeRow(table, rowIndex, startHeight + delta);
        this.repositionAll();
      };
      const onMouseUp = () => {
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
        this.onCommit?.();
      };
      document.addEventListener("mousemove", onMouseMove);
      document.addEventListener("mouseup", onMouseUp);
    }
    destroy() {
      this.clearHandles();
    }
  };

  // src/ui/TableResizer.ts
  var TableResizer = class {
    constructor(root) {
      this.root = root;
      this.handle = null;
      this.moveHandle = null;
      this.activeTable = null;
      this.onCommit = null;
      this.onMoveCommit = null;
      this.handleClick = (e) => {
        const target = e.target;
        const table = target.closest("table");
        if (table && this.root.contains(table)) {
          this.select(table);
        } else {
          this.deselect();
        }
      };
      this.reposition = () => {
        if (!this.activeTable) return;
        const rect = this.activeTable.getBoundingClientRect();
        if (this.handle) {
          this.handle.style.left = `${rect.right - 6}px`;
          this.handle.style.top = `${rect.bottom - 6}px`;
        }
        if (this.moveHandle) {
          this.moveHandle.style.left = `${rect.left - 18}px`;
          this.moveHandle.style.top = `${rect.top}px`;
        }
      };
      this.startDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const table = this.activeTable;
        if (!table) return;
        const startX = e.clientX;
        const startWidth = table.getBoundingClientRect().width;
        const colWidths = this.explicitColumnWidths(table);
        const onMouseMove = (moveEvent) => {
          const delta = moveEvent.clientX - startX;
          const newWidth = Math.max(40, startWidth + delta);
          table.style.width = `${Math.round(newWidth)}px`;
          if (colWidths) {
            const ratio = newWidth / startWidth;
            colWidths.forEach(({ cell, width }) => {
              cell.style.width = `${Math.round(width * ratio)}px`;
            });
          }
          this.reposition();
        };
        const onMouseUp = () => {
          document.removeEventListener("mousemove", onMouseMove);
          document.removeEventListener("mouseup", onMouseUp);
          this.onCommit?.();
        };
        document.addEventListener("mousemove", onMouseMove);
        document.addEventListener("mouseup", onMouseUp);
      };
      this.handleDragStart = (e) => {
        if (!this.activeTable) return;
        e.dataTransfer?.setData("text/plain", "omar-table-move");
        e.dataTransfer.effectAllowed = "move";
      };
      this.handleDragOver = (e) => {
        if (!this.activeTable) return;
        e.preventDefault();
      };
      this.handleDrop = (e) => {
        const table = this.activeTable;
        if (!table) return;
        if (!e.dataTransfer?.types.includes("text/plain")) return;
        e.preventDefault();
        const target = this.findDropBlock(e.target);
        if (!target || target === table || table.contains(target)) return;
        const rect = target.getBoundingClientRect();
        const before = e.clientY < rect.top + rect.height / 2;
        target.parentNode?.insertBefore(table, before ? target : target.nextSibling);
        this.reposition();
        this.onMoveCommit?.();
      };
      root.addEventListener("click", this.handleClick);
      root.addEventListener("dragover", this.handleDragOver);
      root.addEventListener("drop", this.handleDrop);
      document.addEventListener("scroll", this.reposition, true);
    }
    onResizeCommit(cb) {
      this.onCommit = cb;
    }
    onMoveCommitCb(cb) {
      this.onMoveCommit = cb;
    }
    select(table) {
      if (this.activeTable === table) return;
      this.deselect();
      this.activeTable = table;
      table.classList.add("omar-text-editor-table-selected");
      this.handle = document.createElement("div");
      this.handle.className = "omar-text-editor-table-resize-handle";
      document.body.appendChild(this.handle);
      this.handle.addEventListener("mousedown", this.startDrag);
      this.moveHandle = document.createElement("div");
      this.moveHandle.className = "omar-text-editor-table-move-handle";
      this.moveHandle.title = "Drag to move table";
      this.moveHandle.setAttribute("draggable", "true");
      this.moveHandle.innerHTML = '<svg viewBox="0 0 20 20" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M7 3.5a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6Zm6 0a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6ZM7 8.7a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6Zm6 0a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6ZM7 13.9a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6Zm6 0a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6Z"/></svg>';
      this.moveHandle.addEventListener("dragstart", this.handleDragStart);
      document.body.appendChild(this.moveHandle);
      this.reposition();
    }
    deselect() {
      this.activeTable?.classList.remove("omar-text-editor-table-selected");
      this.activeTable = null;
      this.handle?.remove();
      this.handle = null;
      this.moveHandle?.remove();
      this.moveHandle = null;
    }
    // If the first row's cells already carry explicit pixel widths (e.g. from
    // ColumnResizer), scale them proportionally so a table-wide resize keeps
    // relative column proportions instead of leaving them stale.
    explicitColumnWidths(table) {
      const firstRow = table.querySelector("tr");
      if (!firstRow) return null;
      const cells = Array.from(firstRow.children);
      const withWidths = cells.filter((cell) => cell.style.width).map((cell) => ({ cell, width: parseFloat(cell.style.width) }));
      return withWidths.length > 0 ? withWidths : null;
    }
    findDropBlock(node) {
      let current = node;
      while (current && current !== this.root) {
        if (current.nodeType === Node.ELEMENT_NODE && current.parentElement === this.root) {
          return current;
        }
        current = current.parentNode;
      }
      return null;
    }
    destroy() {
      this.deselect();
      this.root.removeEventListener("click", this.handleClick);
      this.root.removeEventListener("dragover", this.handleDragOver);
      this.root.removeEventListener("drop", this.handleDrop);
      document.removeEventListener("scroll", this.reposition, true);
    }
  };

  // src/ui/TableCellSelection.ts
  var SELECTED_CLASS = "omar-text-editor-cell-selected";
  var TableCellSelection = class {
    constructor(root) {
      this.root = root;
      this.table = null;
      this.anchor = null;
      this.selected = [];
      this.dragging = false;
      this.onChangeCb = null;
      this.colStrips = [];
      this.rowStrips = [];
      this.hoveredTable = null;
      this.handleMouseDown = (e) => {
        const cell = getCurrentCell(e.target);
        if (!cell) {
          this.clear();
          return;
        }
        const table = getCurrentTable(cell);
        if (!table) return;
        this.clear();
        this.table = table;
        this.anchor = cell;
        this.dragging = true;
      };
      this.handleMouseOver = (e) => {
        if (!this.dragging || !this.anchor || !this.table) return;
        const cell = getCurrentCell(e.target);
        if (!cell || getCurrentTable(cell) !== this.table) return;
        if (cell === this.anchor) {
          this.applyHighlight([]);
          return;
        }
        const range = getCellRange(this.table, this.anchor, cell);
        this.applyHighlight(range);
      };
      this.handleMouseUp = () => {
        if (!this.dragging) return;
        this.dragging = false;
        if (this.selected.length <= 1) {
          this.clearHighlightOnly();
        } else {
          this.onChangeCb?.();
        }
      };
      this.handleOutsideMouseDown = (e) => {
        if (this.selected.length === 0) return;
        const target = e.target;
        if (this.root.contains(target)) return;
        this.clear();
      };
      this.handleKeydown = (e) => {
        if (e.key === "Escape" && this.selected.length > 0) {
          this.clear();
        }
      };
      // --- header select strips ---
      this.handleHoverForStrips = (e) => {
        const table = e.target.closest("table");
        if (table !== this.hoveredTable) {
          this.hoveredTable = table;
          this.refreshStrips();
        }
      };
      root.addEventListener("mousedown", this.handleMouseDown);
      root.addEventListener("mouseover", this.handleMouseOver);
      document.addEventListener("mouseup", this.handleMouseUp);
      document.addEventListener("mousedown", this.handleOutsideMouseDown, true);
      document.addEventListener("keydown", this.handleKeydown);
      root.addEventListener("mousemove", this.handleHoverForStrips);
      root.addEventListener("input", () => this.refreshStrips());
    }
    onChange(cb) {
      this.onChangeCb = cb;
    }
    getSelectedCells() {
      return this.selected.slice();
    }
    getSelectedTable() {
      return this.table;
    }
    applyHighlight(cells) {
      this.selected.forEach((c) => c.classList.remove(SELECTED_CLASS));
      this.selected = cells;
      this.selected.forEach((c) => c.classList.add(SELECTED_CLASS));
    }
    clearHighlightOnly() {
      this.selected.forEach((c) => c.classList.remove(SELECTED_CLASS));
      this.selected = [];
    }
    // Selects every cell belonging to the given column/row index, feeding the
    // same highlight state used by drag-selection.
    selectColumn(table, colIndex) {
      this.clear();
      this.table = table;
      const cells = [];
      table.querySelectorAll("tr").forEach((row) => {
        Array.from(row.children).forEach((child) => {
          const cell = child;
          const pos = getCellPosition(table, cell);
          if (pos && pos.col === colIndex) cells.push(cell);
        });
      });
      this.applyHighlight(cells);
      this.onChangeCb?.();
    }
    selectRow(table, rowIndex) {
      this.clear();
      this.table = table;
      const row = table.querySelectorAll("tr")[rowIndex];
      if (!row) return;
      this.applyHighlight(Array.from(row.children));
      this.onChangeCb?.();
    }
    clear() {
      this.clearHighlightOnly();
      this.table = null;
      this.anchor = null;
      this.dragging = false;
    }
    refreshStrips() {
      this.clearStrips();
      if (!this.hoveredTable || !this.root.contains(this.hoveredTable)) return;
      this.buildStrips(this.hoveredTable);
    }
    clearStrips() {
      this.colStrips.forEach((s) => s.remove());
      this.rowStrips.forEach((s) => s.remove());
      this.colStrips = [];
      this.rowStrips = [];
    }
    buildStrips(table) {
      const firstRow = table.querySelector("tr");
      if (!firstRow) return;
      const parent = table.parentElement;
      if (!parent) return;
      parent.style.setProperty("position", "relative");
      const parentRect = parent.getBoundingClientRect();
      const tableRect = table.getBoundingClientRect();
      const colCount = firstRow.children.length;
      for (let c = 0; c < colCount; c++) {
        const cell = firstRow.children[c];
        const cellRect = cell.getBoundingClientRect();
        const strip = document.createElement("div");
        strip.className = "omar-text-editor-col-select-strip";
        strip.style.left = `${cellRect.left - parentRect.left}px`;
        strip.style.top = `${tableRect.top - parentRect.top - 8}px`;
        strip.style.width = `${cellRect.width}px`;
        strip.addEventListener("mousedown", (e) => {
          e.preventDefault();
          this.selectColumn(table, c);
        });
        parent.appendChild(strip);
        this.colStrips.push(strip);
      }
      const rows = table.querySelectorAll("tr");
      rows.forEach((row, r) => {
        const rowRect = row.getBoundingClientRect();
        const strip = document.createElement("div");
        strip.className = "omar-text-editor-row-select-strip";
        strip.style.top = `${rowRect.top - parentRect.top}px`;
        strip.style.left = `${tableRect.left - parentRect.left - 8}px`;
        strip.style.height = `${rowRect.height}px`;
        strip.addEventListener("mousedown", (e) => {
          e.preventDefault();
          this.selectRow(table, r);
        });
        parent.appendChild(strip);
        this.rowStrips.push(strip);
      });
    }
    destroy() {
      this.root.removeEventListener("mousedown", this.handleMouseDown);
      this.root.removeEventListener("mouseover", this.handleMouseOver);
      document.removeEventListener("mouseup", this.handleMouseUp);
      document.removeEventListener("mousedown", this.handleOutsideMouseDown, true);
      document.removeEventListener("keydown", this.handleKeydown);
      this.root.removeEventListener("mousemove", this.handleHoverForStrips);
      this.clearStrips();
      this.clear();
    }
  };

  // src/plugins/table/index.ts
  function openGridPicker(editor, anchor) {
    const root = editor.getEditableElement();
    const bookmark = bookmarkSelection(root);
    new GridPicker(anchor, {
      onPick: (rows, cols) => {
        root.focus();
        if (bookmark) restoreSelection(root, bookmark);
        insertTable(root, rows, cols);
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  function buildContextMenuItems3(editor, cell) {
    const table = getCurrentTable(cell);
    const row = cell.parentElement;
    const colIndex = Array.prototype.indexOf.call(row.children, cell);
    const commit = () => editor.execCommand("__syncAfterExternalEdit");
    return [
      { label: "Insert row above", onSelect: () => {
        insertRow(table, row, true);
        commit();
      } },
      { label: "Insert row below", onSelect: () => {
        insertRow(table, row, false);
        commit();
      } },
      { label: "Delete row", onSelect: () => {
        deleteRow(row);
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Insert column left", onSelect: () => {
        insertColumn(table, colIndex, true);
        commit();
      } },
      { label: "Insert column right", onSelect: () => {
        insertColumn(table, colIndex, false);
        commit();
      } },
      { label: "Delete column", onSelect: () => {
        deleteColumn(table, colIndex);
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Merge with cell to the right", onSelect: () => {
        mergeCellRight(cell);
        commit();
      } },
      { label: "Split cell", onSelect: () => {
        splitCell(cell);
        commit();
      } },
      { label: "Toggle header row", onSelect: () => {
        toggleHeaderRow(table);
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Delete table", onSelect: () => {
        deleteTable(table);
        commit();
      } }
    ];
  }
  function buildSelectionContextMenuItems(editor, table, cells, selection, anchorX, anchorY) {
    const commit = () => {
      selection.clear();
      editor.execCommand("__syncAfterExternalEdit");
    };
    return [
      { label: "Delete selected rows", onSelect: () => {
        deleteRows(table, cells);
        commit();
      } },
      { label: "Delete selected columns", onSelect: () => {
        deleteColumns(table, cells);
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      { label: "Merge cells", onSelect: () => {
        mergeCellRange(table, cells);
        commit();
      } },
      { label: "", onSelect: () => {
      }, separator: true },
      {
        label: "Background color\u2026",
        onSelect: () => {
          const anchor = document.createElement("div");
          anchor.style.position = "fixed";
          anchor.style.left = `${anchorX}px`;
          anchor.style.top = `${anchorY}px`;
          document.body.appendChild(anchor);
          new ColorPicker(anchor, {
            onPick: (color) => {
              cells.forEach((c) => {
                c.style.backgroundColor = color;
              });
              commit();
              anchor.remove();
            },
            onClear: () => {
              cells.forEach((c) => {
                c.style.backgroundColor = "";
              });
              commit();
              anchor.remove();
            }
          });
        }
      }
    ];
  }
  function setupContextMenu(editor, selection) {
    const root = editor.getEditableElement();
    root.addEventListener("contextmenu", (e) => {
      const target = e.target;
      const cell = getCurrentCell(target);
      if (!cell) return;
      e.preventDefault();
      const selectedCells = selection.getSelectedCells();
      const table = selection.getSelectedTable();
      if (table && selectedCells.length > 1 && selectedCells.includes(cell)) {
        new ContextMenu(
          e.clientX,
          e.clientY,
          buildSelectionContextMenuItems(editor, table, selectedCells, selection, e.clientX, e.clientY)
        );
        return;
      }
      new ContextMenu(e.clientX, e.clientY, buildContextMenuItems3(editor, cell));
    });
  }
  function setupTabNavigation(editor) {
    const root = editor.getEditableElement();
    root.addEventListener("keydown", (e) => {
      if (e.key !== "Tab") return;
      const sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return;
      const cell = getCurrentCell(sel.getRangeAt(0).startContainer);
      if (!cell) return;
      const table = getCurrentTable(cell);
      if (!table) return;
      e.preventDefault();
      if (navigateCell(table, cell, !e.shiftKey)) {
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  registerToolbarButton({
    name: "table",
    label: "Insert table",
    icon: "table",
    command: "table",
    onClick: (editor, buttonEl) => openGridPicker(editor, buttonEl)
  });
  registerPlugin("table", (editor) => {
    editor.commands.register("table", () => {
      const root = editor.getEditableElement();
      root.focus();
      insertTable(root, 3, 3);
      editor.execCommand("__syncAfterExternalEdit");
    });
    const withCurrentCell = (fn) => {
      const sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return;
      const cell = getCurrentCell(sel.getRangeAt(0).startContainer);
      if (!cell) return;
      const table = getCurrentTable(cell);
      if (!table) return;
      const row = cell.parentElement;
      const colIndex = Array.prototype.indexOf.call(row.children, cell);
      fn(table, cell, colIndex);
      editor.execCommand("__syncAfterExternalEdit");
    };
    editor.commands.register("tableInsertRowBefore", () => withCurrentCell((table, cell) => {
      insertRow(table, cell.parentElement, true);
    }));
    editor.commands.register("tableInsertRowAfter", () => withCurrentCell((table, cell) => {
      insertRow(table, cell.parentElement, false);
    }));
    editor.commands.register("tableDeleteRow", () => withCurrentCell((_table, cell) => {
      deleteRow(cell.parentElement);
    }));
    editor.commands.register("tableInsertColBefore", () => withCurrentCell((table, _cell, colIndex) => {
      insertColumn(table, colIndex, true);
    }));
    editor.commands.register("tableInsertColAfter", () => withCurrentCell((table, _cell, colIndex) => {
      insertColumn(table, colIndex, false);
    }));
    editor.commands.register("tableDeleteCol", () => withCurrentCell((table, _cell, colIndex) => {
      deleteColumn(table, colIndex);
    }));
    editor.commands.register("tableDelete", () => withCurrentCell((table) => {
      deleteTable(table);
    }));
    const selection = new TableCellSelection(editor.getEditableElement());
    selection.onChange(() => {
    });
    setupContextMenu(editor, selection);
    setupTabNavigation(editor);
    const colResizer = new ColumnResizer(editor.getEditableElement());
    colResizer.onResizeCommit(() => editor.execCommand("__syncAfterExternalEdit"));
    const rowResizer = new RowResizer(editor.getEditableElement());
    rowResizer.onResizeCommit(() => editor.execCommand("__syncAfterExternalEdit"));
    const tableResizer = new TableResizer(editor.getEditableElement());
    tableResizer.onResizeCommit(() => editor.execCommand("__syncAfterExternalEdit"));
    tableResizer.onMoveCommitCb(() => editor.execCommand("__syncAfterExternalEdit"));
    editor.on("change", () => {
      colResizer.refresh();
      rowResizer.refresh();
    });
  });

  // src/core/Highlighter.ts
  var KEYWORDS = {
    javascript: ["const", "let", "var", "function", "return", "if", "else", "for", "while", "class", "extends", "new", "import", "export", "default", "from", "async", "await", "try", "catch", "throw", "typeof", "instanceof", "null", "undefined", "true", "false"],
    typescript: ["const", "let", "var", "function", "return", "if", "else", "for", "while", "class", "extends", "implements", "interface", "type", "new", "import", "export", "default", "from", "async", "await", "try", "catch", "throw", "typeof", "instanceof", "null", "undefined", "true", "false", "public", "private", "readonly", "enum"],
    python: ["def", "return", "if", "elif", "else", "for", "while", "class", "import", "from", "as", "try", "except", "finally", "raise", "with", "lambda", "None", "True", "False", "and", "or", "not", "in", "is"],
    java: ["public", "private", "protected", "class", "interface", "extends", "implements", "static", "final", "void", "return", "if", "else", "for", "while", "new", "try", "catch", "throw", "import", "package", "null", "true", "false"],
    csharp: ["public", "private", "protected", "class", "interface", "static", "void", "return", "if", "else", "for", "while", "new", "try", "catch", "throw", "using", "namespace", "null", "true", "false", "var"],
    css: ["important", "inherit", "initial", "unset"],
    html: [],
    markup: [],
    bash: ["if", "then", "else", "fi", "for", "while", "do", "done", "function", "echo", "export", "return"],
    json: ["true", "false", "null"],
    sql: ["select", "from", "where", "insert", "into", "values", "update", "set", "delete", "join", "on", "group", "by", "order", "having", "create", "table", "and", "or", "not", "null"]
  };
  var SUPPORTED_LANGUAGES = Object.keys(KEYWORDS);
  function escapeHtml(text) {
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
  }
  function highlight(code, language) {
    const keywords = new Set(KEYWORDS[language] ?? []);
    const keywordPattern = keywords.size > 0 ? Array.from(keywords).join("|") : null;
    const patterns = [
      { type: "comment", regex: /\/\/.*$|\/\*[\s\S]*?\*\/|#.*$/m },
      { type: "string", regex: /"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*'|`(?:[^`\\]|\\.)*`/ },
      { type: "number", regex: /\b\d+(\.\d+)?\b/ },
      ...keywordPattern ? [{ type: "keyword", regex: new RegExp(`\\b(${keywordPattern})\\b`) }] : []
    ];
    let result = "";
    let remaining = code;
    while (remaining.length > 0) {
      let earliestMatch = null;
      for (const { type: type2, regex } of patterns) {
        const match2 = remaining.match(regex);
        if (match2 && match2.index !== void 0) {
          if (!earliestMatch || match2.index < earliestMatch.match.index) {
            earliestMatch = { type: type2, match: match2 };
          }
        }
      }
      if (!earliestMatch) {
        result += escapeHtml(remaining);
        break;
      }
      const { type, match } = earliestMatch;
      const index = match.index;
      result += escapeHtml(remaining.slice(0, index));
      result += `<span class="tok-${type}">${escapeHtml(match[0])}</span>`;
      remaining = remaining.slice(index + match[0].length);
    }
    return result;
  }

  // src/core/CodeSample.ts
  function getRange10() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0);
  }
  function findAncestor4(node, tags) {
    let current = node;
    while (current) {
      if (current.nodeType === Node.ELEMENT_NODE && tags.includes(current.tagName.toLowerCase())) {
        return current;
      }
      current = current.parentNode;
    }
    return null;
  }
  function setCaretAt4(node, offset) {
    const range = document.createRange();
    range.setStart(node, offset);
    range.setEnd(node, offset);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(range);
  }
  function renderCodeBlock(pre, code, language) {
    pre.classList.add("omar-text-editor-codesample");
    pre.dataset.language = language;
    pre.dataset.code = code;
    const codeEl = document.createElement("code");
    codeEl.className = `language-${language}`;
    codeEl.innerHTML = highlight(code, language);
    pre.innerHTML = "";
    pre.appendChild(codeEl);
  }
  function insertCodeSample(root, code, language) {
    const pre = document.createElement("pre");
    renderCodeBlock(pre, code, language);
    const range = getRange10();
    if (range) {
      range.deleteContents();
      range.insertNode(pre);
    } else {
      root.appendChild(pre);
    }
    const p = document.createElement("p");
    p.innerHTML = "<br>";
    pre.parentNode?.insertBefore(p, pre.nextSibling);
    setCaretAt4(p, 0);
  }
  function updateCodeSample(pre, code, language) {
    renderCodeBlock(pre, code, language);
  }
  function getCurrentCodeSample(node) {
    const pre = findAncestor4(node, ["pre"]);
    if (!pre || !pre.classList.contains("omar-text-editor-codesample")) return null;
    return pre;
  }
  function getCodeSampleData(pre) {
    return {
      code: pre.dataset.code ?? "",
      language: pre.dataset.language ?? "javascript"
    };
  }

  // src/plugins/codesample/index.ts
  var LANGUAGE_LABELS = {
    javascript: "JavaScript",
    typescript: "TypeScript",
    python: "Python",
    java: "Java",
    csharp: "C#",
    css: "CSS",
    html: "HTML/XML",
    bash: "Bash",
    json: "JSON",
    sql: "SQL"
  };
  function openCodeSampleDialog(editor, existing) {
    const root = editor.getEditableElement();
    const current = existing ? getCodeSampleData(existing) : { code: "", language: "javascript" };
    const bookmark = bookmarkSelection(root);
    new Dialog({
      title: existing ? "Edit Code Sample" : "Insert Code Sample",
      submitLabel: existing ? "Update" : "Insert",
      fields: [
        {
          name: "language",
          label: "Language",
          type: "select",
          defaultValue: current.language,
          options: SUPPORTED_LANGUAGES.map((lang) => ({ label: LANGUAGE_LABELS[lang] ?? lang, value: lang }))
        },
        { name: "code", label: "Code", type: "textarea", defaultValue: current.code }
      ],
      onSubmit: (values) => {
        root.focus();
        const code = String(values.code ?? "");
        const language = String(values.language ?? "javascript");
        if (existing) {
          updateCodeSample(existing, code, language);
        } else {
          if (bookmark) restoreSelection(root, bookmark);
          insertCodeSample(root, code, language);
        }
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  function setupEditOnDoubleClick(editor) {
    const root = editor.getEditableElement();
    root.addEventListener("dblclick", (e) => {
      const pre = getCurrentCodeSample(e.target);
      if (pre) openCodeSampleDialog(editor, pre);
    });
  }
  registerToolbarButton({ name: "codesample", label: "Insert code sample", icon: "codeBlock", command: "codesample" });
  registerPlugin("codesample", (editor) => {
    editor.commands.register("codesample", () => {
      const root = editor.getEditableElement();
      const existing = getCurrentCodeSample(window.getSelection()?.anchorNode ?? root);
      openCodeSampleDialog(editor, existing);
    });
    setupEditOnDoubleClick(editor);
  });

  // src/core/Emoticons.ts
  var EMOJI_LIST = [
    { char: "\u{1F600}", name: "grinning face", keywords: ["happy", "smile"] },
    { char: "\u{1F602}", name: "face with tears of joy", keywords: ["laugh", "lol"] },
    { char: "\u{1F60A}", name: "smiling face", keywords: ["happy", "blush"] },
    { char: "\u{1F60D}", name: "heart eyes", keywords: ["love", "crush"] },
    { char: "\u{1F618}", name: "face blowing a kiss", keywords: ["love", "kiss"] },
    { char: "\u{1F622}", name: "crying face", keywords: ["sad", "tear"] },
    { char: "\u{1F62D}", name: "loudly crying face", keywords: ["sad", "cry"] },
    { char: "\u{1F621}", name: "angry face", keywords: ["mad", "anger"] },
    { char: "\u{1F62E}", name: "face with open mouth", keywords: ["surprise", "wow"] },
    { char: "\u{1F914}", name: "thinking face", keywords: ["think", "hmm"] },
    { char: "\u{1F634}", name: "sleeping face", keywords: ["sleep", "tired"] },
    { char: "\u{1F60E}", name: "smiling face with sunglasses", keywords: ["cool"] },
    { char: "\u{1F644}", name: "face with rolling eyes", keywords: ["annoyed"] },
    { char: "\u{1F605}", name: "grinning face with sweat", keywords: ["relief", "phew"] },
    { char: "\u{1F973}", name: "partying face", keywords: ["party", "celebrate"] },
    { char: "\u{1F44D}", name: "thumbs up", keywords: ["like", "approve"] },
    { char: "\u{1F44E}", name: "thumbs down", keywords: ["dislike"] },
    { char: "\u{1F44F}", name: "clapping hands", keywords: ["applause", "bravo"] },
    { char: "\u{1F64F}", name: "folded hands", keywords: ["please", "thanks", "pray"] },
    { char: "\u{1F4AA}", name: "flexed biceps", keywords: ["strong", "muscle"] },
    { char: "\u{1F44B}", name: "waving hand", keywords: ["hello", "bye"] },
    { char: "\u270C\uFE0F", name: "victory hand", keywords: ["peace"] },
    { char: "\u{1F91D}", name: "handshake", keywords: ["deal", "agreement"] },
    { char: "\u2764\uFE0F", name: "red heart", keywords: ["love"] },
    { char: "\u{1F525}", name: "fire", keywords: ["hot", "lit"] },
    { char: "\u{1F389}", name: "party popper", keywords: ["celebrate", "congrats"] },
    { char: "\u2B50", name: "star", keywords: ["favorite"] },
    { char: "\u2705", name: "check mark", keywords: ["done", "correct"] },
    { char: "\u274C", name: "cross mark", keywords: ["wrong", "no"] },
    { char: "\u26A1", name: "high voltage", keywords: ["fast", "lightning"] },
    { char: "\u{1F4A1}", name: "light bulb", keywords: ["idea"] },
    { char: "\u{1F4CC}", name: "pushpin", keywords: ["pin", "note"] },
    { char: "\u{1F4CE}", name: "paperclip", keywords: ["attach"] },
    { char: "\u{1F680}", name: "rocket", keywords: ["launch", "fast"] },
    { char: "\u2615", name: "hot beverage", keywords: ["coffee", "tea"] },
    { char: "\u{1F355}", name: "pizza", keywords: ["food"] },
    { char: "\u{1F436}", name: "dog face", keywords: ["pet", "animal"] },
    { char: "\u{1F431}", name: "cat face", keywords: ["pet", "animal"] },
    { char: "\u{1F31E}", name: "sun", keywords: ["sunny", "weather"] },
    { char: "\u{1F327}\uFE0F", name: "cloud with rain", keywords: ["rain", "weather"] },
    { char: "\u2744\uFE0F", name: "snowflake", keywords: ["snow", "cold", "weather"] }
  ];
  var RECENT_KEY = "omar-text-editor-recent-emoji";
  var MAX_RECENT = 16;
  function searchEmoji(query) {
    const q = query.trim().toLowerCase();
    if (!q) return EMOJI_LIST;
    return EMOJI_LIST.filter(
      (e) => e.name.includes(q) || e.keywords.some((k) => k.includes(q))
    );
  }
  function getRecentEmoji() {
    try {
      const raw = localStorage.getItem(RECENT_KEY);
      if (!raw) return [];
      const chars = JSON.parse(raw);
      return chars.map((c) => EMOJI_LIST.find((e) => e.char === c)).filter((e) => !!e);
    } catch {
      return [];
    }
  }
  function recordRecentEmoji(char) {
    try {
      const current = getRecentEmoji().map((e) => e.char).filter((c) => c !== char);
      const updated = [char, ...current].slice(0, MAX_RECENT);
      localStorage.setItem(RECENT_KEY, JSON.stringify(updated));
    } catch {
    }
  }

  // src/plugins/emoticons/index.ts
  function insertEmoji(editor, char, bookmark) {
    const root = editor.getEditableElement();
    root.focus();
    if (bookmark) restoreSelection(root, bookmark);
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0) {
      const range = sel.getRangeAt(0);
      range.deleteContents();
      const textNode = document.createTextNode(char);
      range.insertNode(textNode);
      range.setStartAfter(textNode);
      range.collapse(true);
      sel.removeAllRanges();
      sel.addRange(range);
    }
    recordRecentEmoji(char);
    editor.execCommand("__syncAfterExternalEdit");
  }
  function openEmoticonsDialog(editor) {
    const root = editor.getEditableElement();
    const bookmark = bookmarkSelection(root);
    const dialog = new PickerDialog({ title: "Insert Emoji", searchPlaceholder: "Search emoji\u2026" });
    const render = (list, heading) => {
      dialog.bodyEl.innerHTML = "";
      if (heading) {
        const h = document.createElement("div");
        h.className = "omar-text-editor-picker-heading";
        h.textContent = heading;
        dialog.bodyEl.appendChild(h);
      }
      if (list.length === 0) {
        const empty = document.createElement("div");
        empty.className = "omar-text-editor-picker-empty";
        empty.textContent = "No matches";
        dialog.bodyEl.appendChild(empty);
        return;
      }
      for (const emoji of list) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "omar-text-editor-picker-item";
        btn.textContent = emoji.char;
        btn.title = emoji.name;
        btn.addEventListener("click", () => {
          insertEmoji(editor, emoji.char, bookmark);
          dialog.close();
        });
        dialog.bodyEl.appendChild(btn);
      }
    };
    const recent = getRecentEmoji();
    render(recent.length > 0 ? recent : searchEmoji(""), recent.length > 0 ? "Recently used" : void 0);
    dialog.searchInput?.addEventListener("input", () => {
      const query = dialog.searchInput.value;
      render(query ? searchEmoji(query) : searchEmoji(""));
    });
  }
  registerToolbarButton({ name: "emoticons", label: "Insert emoji", icon: "emoji", command: "emoticons" });
  registerPlugin("emoticons", (editor) => {
    editor.commands.register("emoticons", () => openEmoticonsDialog(editor));
  });

  // src/core/CharMap.ts
  var CHAR_MAP_LIST = [
    { char: "\xA9", name: "copyright sign", code: "U+00A9" },
    { char: "\xAE", name: "registered sign", code: "U+00AE" },
    { char: "\u2122", name: "trade mark sign", code: "U+2122" },
    { char: "\xA7", name: "section sign", code: "U+00A7" },
    { char: "\xB6", name: "pilcrow sign", code: "U+00B6" },
    { char: "\u2020", name: "dagger", code: "U+2020" },
    { char: "\u2021", name: "double dagger", code: "U+2021" },
    { char: "\u2022", name: "bullet", code: "U+2022" },
    { char: "\u2026", name: "horizontal ellipsis", code: "U+2026" },
    { char: "\u2030", name: "per mille sign", code: "U+2030" },
    { char: "\u20AC", name: "euro sign", code: "U+20AC" },
    { char: "\xA3", name: "pound sign", code: "U+00A3" },
    { char: "\xA5", name: "yen sign", code: "U+00A5" },
    { char: "\xA2", name: "cent sign", code: "U+00A2" },
    { char: "\xA4", name: "currency sign", code: "U+00A4" },
    { char: "\xB1", name: "plus-minus sign", code: "U+00B1" },
    { char: "\xD7", name: "multiplication sign", code: "U+00D7" },
    { char: "\xF7", name: "division sign", code: "U+00F7" },
    { char: "\u2248", name: "almost equal to", code: "U+2248" },
    { char: "\u2260", name: "not equal to", code: "U+2260" },
    { char: "\u2264", name: "less-than or equal to", code: "U+2264" },
    { char: "\u2265", name: "greater-than or equal to", code: "U+2265" },
    { char: "\u221E", name: "infinity", code: "U+221E" },
    { char: "\u221A", name: "square root", code: "U+221A" },
    { char: "\u03C0", name: "greek small letter pi", code: "U+03C0" },
    { char: "\xBD", name: "vulgar fraction one half", code: "U+00BD" },
    { char: "\xBC", name: "vulgar fraction one quarter", code: "U+00BC" },
    { char: "\xBE", name: "vulgar fraction three quarters", code: "U+00BE" },
    { char: "\xB0", name: "degree sign", code: "U+00B0" },
    { char: "\u2190", name: "leftwards arrow", code: "U+2190" },
    { char: "\u2192", name: "rightwards arrow", code: "U+2192" },
    { char: "\u2191", name: "upwards arrow", code: "U+2191" },
    { char: "\u2193", name: "downwards arrow", code: "U+2193" },
    { char: "\u2194", name: "left right arrow", code: "U+2194" },
    { char: "\xAB", name: "left-pointing double angle quotation mark", code: "U+00AB" },
    { char: "\xBB", name: "right-pointing double angle quotation mark", code: "U+00BB" },
    { char: '"', name: "left double quotation mark", code: "U+201C" },
    { char: '"', name: "right double quotation mark", code: "U+201D" },
    { char: "\u2018", name: "left single quotation mark", code: "U+2018" },
    { char: "\u2019", name: "right single quotation mark", code: "U+2019" },
    { char: "\u2013", name: "en dash", code: "U+2013" },
    { char: "\u2014", name: "em dash", code: "U+2014" },
    { char: "\xE1", name: "latin small letter a with acute", code: "U+00E1" },
    { char: "\xE9", name: "latin small letter e with acute", code: "U+00E9" },
    { char: "\xED", name: "latin small letter i with acute", code: "U+00ED" },
    { char: "\xF3", name: "latin small letter o with acute", code: "U+00F3" },
    { char: "\xFA", name: "latin small letter u with acute", code: "U+00FA" },
    { char: "\xF1", name: "latin small letter n with tilde", code: "U+00F1" },
    { char: "\xFC", name: "latin small letter u with diaeresis", code: "U+00FC" },
    { char: "\xE7", name: "latin small letter c with cedilla", code: "U+00E7" },
    { char: "\xDF", name: "latin small letter sharp s", code: "U+00DF" }
  ];
  function searchCharMap(query) {
    const q = query.trim().toLowerCase();
    if (!q) return CHAR_MAP_LIST;
    return CHAR_MAP_LIST.filter(
      (c) => c.name.includes(q) || c.code.toLowerCase().includes(q)
    );
  }

  // src/plugins/charmap/index.ts
  function insertChar(editor, char, bookmark) {
    const root = editor.getEditableElement();
    root.focus();
    if (bookmark) restoreSelection(root, bookmark);
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0) {
      const range = sel.getRangeAt(0);
      range.deleteContents();
      const textNode = document.createTextNode(char);
      range.insertNode(textNode);
      range.setStartAfter(textNode);
      range.collapse(true);
      sel.removeAllRanges();
      sel.addRange(range);
    }
    editor.execCommand("__syncAfterExternalEdit");
  }
  function openCharMapDialog(editor) {
    const root = editor.getEditableElement();
    const bookmark = bookmarkSelection(root);
    const dialog = new PickerDialog({ title: "Special Character", searchPlaceholder: "Search by name\u2026" });
    const render = (list) => {
      dialog.bodyEl.innerHTML = "";
      if (list.length === 0) {
        const empty = document.createElement("div");
        empty.className = "omar-text-editor-picker-empty";
        empty.textContent = "No matches";
        dialog.bodyEl.appendChild(empty);
        return;
      }
      for (const entry of list) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "omar-text-editor-picker-item";
        btn.textContent = entry.char;
        btn.title = `${entry.name} (${entry.code})`;
        btn.addEventListener("click", () => {
          insertChar(editor, entry.char, bookmark);
          dialog.close();
        });
        dialog.bodyEl.appendChild(btn);
      }
    };
    render(searchCharMap(""));
    dialog.searchInput?.addEventListener("input", () => {
      render(searchCharMap(dialog.searchInput.value));
    });
  }
  registerToolbarButton({ name: "charmap", label: "Special character", icon: "charmap", command: "charmap" });
  registerPlugin("charmap", (editor) => {
    editor.commands.register("charmap", () => openCharMapDialog(editor));
  });

  // src/core/SearchReplace.ts
  function buildPattern(query, options) {
    if (!query) return null;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const pattern = options.wholeWord ? `\\b${escaped}\\b` : escaped;
    return new RegExp(pattern, options.matchCase ? "g" : "gi");
  }
  function findAllMatches(root, query, options = {}) {
    const regex = buildPattern(query, options);
    if (!regex) return [];
    const matches = [];
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let node = walker.nextNode();
    while (node) {
      const text = node.data;
      let match;
      regex.lastIndex = 0;
      while ((match = regex.exec(text)) !== null) {
        matches.push({ node, start: match.index, end: match.index + match[0].length });
        if (match[0].length === 0) regex.lastIndex++;
      }
      node = walker.nextNode();
    }
    return matches;
  }
  var HIGHLIGHT_CLASS = "omar-text-editor-search-highlight";
  var ACTIVE_CLASS = "omar-text-editor-search-highlight-active";
  function highlightMatches(matches) {
    const byNode = /* @__PURE__ */ new Map();
    for (const m of matches) {
      if (!byNode.has(m.node)) byNode.set(m.node, []);
      byNode.get(m.node).push(m);
    }
    const marksByMatch = /* @__PURE__ */ new Map();
    for (const [node, nodeMatches] of byNode) {
      const sorted = [...nodeMatches].sort((a, b) => b.start - a.start);
      for (const m of sorted) {
        const range = document.createRange();
        range.setStart(m.node, m.start);
        range.setEnd(m.node, m.end);
        const mark = document.createElement("mark");
        mark.className = HIGHLIGHT_CLASS;
        range.surroundContents(mark);
        marksByMatch.set(m, mark);
      }
    }
    return matches.map((m) => marksByMatch.get(m));
  }
  function clearHighlights(root) {
    const marks = root.querySelectorAll(`mark.${HIGHLIGHT_CLASS}`);
    marks.forEach((mark) => {
      const parent = mark.parentNode;
      if (!parent) return;
      while (mark.firstChild) parent.insertBefore(mark.firstChild, mark);
      parent.removeChild(mark);
      parent.normalize();
    });
  }
  function setActiveHighlight(marks, index) {
    marks.forEach((mark, i) => mark.classList.toggle(ACTIVE_CLASS, i === index));
    marks[index]?.scrollIntoView?.({ block: "nearest" });
  }
  function replaceMatchAt(mark, replacement) {
    const textNode = document.createTextNode(replacement);
    mark.parentNode?.replaceChild(textNode, mark);
    textNode.parentElement?.normalize();
  }
  function replaceAll(root, query, replacement, options = {}) {
    const matches = findAllMatches(root, query, options);
    const marks = highlightMatches(matches);
    marks.forEach((mark) => replaceMatchAt(mark, replacement));
    return matches.length;
  }

  // src/plugins/searchreplace/index.ts
  var SearchSession = class {
    constructor(editor) {
      this.editor = editor;
      this.matches = [];
      this.marks = [];
      this.activeIndex = -1;
    }
    root() {
      return this.editor.getEditableElement();
    }
    runSearch(query, matchCase, wholeWord) {
      this.clear();
      this.matches = findAllMatches(this.root(), query, { matchCase, wholeWord });
      this.marks = highlightMatches(this.matches);
      this.activeIndex = this.marks.length > 0 ? 0 : -1;
      if (this.activeIndex >= 0) setActiveHighlight(this.marks, this.activeIndex);
      return this.matches.length;
    }
    next() {
      if (this.marks.length === 0) return;
      this.activeIndex = (this.activeIndex + 1) % this.marks.length;
      setActiveHighlight(this.marks, this.activeIndex);
    }
    prev() {
      if (this.marks.length === 0) return;
      this.activeIndex = (this.activeIndex - 1 + this.marks.length) % this.marks.length;
      setActiveHighlight(this.marks, this.activeIndex);
    }
    replaceActive(replacement) {
      if (this.activeIndex === -1) return;
      const mark = this.marks[this.activeIndex];
      replaceMatchAt(mark, replacement);
      this.marks.splice(this.activeIndex, 1);
      this.matches.splice(this.activeIndex, 1);
      if (this.activeIndex >= this.marks.length) this.activeIndex = this.marks.length - 1;
      if (this.activeIndex >= 0) setActiveHighlight(this.marks, this.activeIndex);
      this.editor.execCommand("__syncAfterExternalEdit");
    }
    replaceAllMatches(query, replacement, matchCase, wholeWord) {
      this.clear();
      const count = replaceAll(this.root(), query, replacement, { matchCase, wholeWord });
      this.editor.execCommand("__syncAfterExternalEdit");
      return count;
    }
    matchCount() {
      return this.matches.length;
    }
    activePosition() {
      return this.activeIndex;
    }
    clear() {
      clearHighlights(this.root());
      this.matches = [];
      this.marks = [];
      this.activeIndex = -1;
    }
  };
  function openSearchReplaceDialog(editor) {
    const session = new SearchSession(editor);
    const dialog = new PickerDialog({
      title: "Find & Replace",
      onClose: () => session.clear()
    });
    dialog.bodyEl.classList.add("omar-text-editor-searchreplace-body");
    const findInput = document.createElement("input");
    findInput.type = "text";
    findInput.placeholder = "Find";
    findInput.className = "omar-text-editor-searchreplace-input";
    const replaceInput = document.createElement("input");
    replaceInput.type = "text";
    replaceInput.placeholder = "Replace with";
    replaceInput.className = "omar-text-editor-searchreplace-input";
    const optionsRow = document.createElement("div");
    optionsRow.className = "omar-text-editor-searchreplace-options";
    const matchCaseLabel = document.createElement("label");
    const matchCaseCheckbox = document.createElement("input");
    matchCaseCheckbox.type = "checkbox";
    matchCaseLabel.appendChild(matchCaseCheckbox);
    matchCaseLabel.append(" Match case");
    const wholeWordLabel = document.createElement("label");
    const wholeWordCheckbox = document.createElement("input");
    wholeWordCheckbox.type = "checkbox";
    wholeWordLabel.appendChild(wholeWordCheckbox);
    wholeWordLabel.append(" Whole word");
    optionsRow.appendChild(matchCaseLabel);
    optionsRow.appendChild(wholeWordLabel);
    const statusText = document.createElement("div");
    statusText.className = "omar-text-editor-searchreplace-status";
    const buttonsRow = document.createElement("div");
    buttonsRow.className = "omar-text-editor-searchreplace-buttons";
    const findNextBtn = document.createElement("button");
    findNextBtn.type = "button";
    findNextBtn.textContent = "Find next";
    const findPrevBtn = document.createElement("button");
    findPrevBtn.type = "button";
    findPrevBtn.textContent = "Find prev";
    const replaceBtn = document.createElement("button");
    replaceBtn.type = "button";
    replaceBtn.textContent = "Replace";
    const replaceAllBtn = document.createElement("button");
    replaceAllBtn.type = "button";
    replaceAllBtn.textContent = "Replace all";
    buttonsRow.append(findPrevBtn, findNextBtn, replaceBtn, replaceAllBtn);
    const runSearch = () => {
      const count = session.runSearch(findInput.value, matchCaseCheckbox.checked, wholeWordCheckbox.checked);
      statusText.textContent = count === 0 ? "No matches" : `${session.activePosition() + 1} of ${count}`;
    };
    findInput.addEventListener("input", runSearch);
    matchCaseCheckbox.addEventListener("change", runSearch);
    wholeWordCheckbox.addEventListener("change", runSearch);
    findNextBtn.addEventListener("click", () => {
      session.next();
      statusText.textContent = `${session.activePosition() + 1} of ${session.matchCount()}`;
    });
    findPrevBtn.addEventListener("click", () => {
      session.prev();
      statusText.textContent = `${session.activePosition() + 1} of ${session.matchCount()}`;
    });
    replaceBtn.addEventListener("click", () => {
      session.replaceActive(replaceInput.value);
      statusText.textContent = session.matchCount() === 0 ? "No matches" : `${session.activePosition() + 1} of ${session.matchCount()}`;
    });
    replaceAllBtn.addEventListener("click", () => {
      const count = session.replaceAllMatches(findInput.value, replaceInput.value, matchCaseCheckbox.checked, wholeWordCheckbox.checked);
      statusText.textContent = `Replaced ${count} occurrence${count === 1 ? "" : "s"}`;
    });
    dialog.bodyEl.append(findInput, replaceInput, optionsRow, buttonsRow, statusText);
  }
  registerToolbarButton({ name: "searchreplace", label: "Find and replace", icon: "search", command: "searchreplace" });
  registerPlugin("searchreplace", (editor) => {
    editor.commands.register("searchreplace", () => openSearchReplaceDialog(editor));
  });

  // src/core/WordCount.ts
  function extractWordCountText(node) {
    let result = "";
    for (const child of Array.from(node.childNodes)) {
      if (child.nodeType === Node.TEXT_NODE) {
        result += child.textContent ?? "";
      } else if (child.nodeType === Node.ELEMENT_NODE) {
        const tag = child.tagName.toLowerCase();
        result += extractWordCountText(child);
        if (isBlockTag(tag) || tag === "br") result += " ";
      }
    }
    return result;
  }
  function countText(root) {
    const wordText = extractWordCountText(root);
    const trimmedWordText = wordText.trim();
    const words = trimmedWordText.length === 0 ? 0 : trimmedWordText.split(/\s+/).length;
    const rawText = root.textContent ?? "";
    const characters = rawText.length;
    const charactersNoSpaces = rawText.replace(/\s/g, "").length;
    return { words, characters, charactersNoSpaces };
  }

  // src/plugins/wordcount/index.ts
  function updateStatus(editor) {
    const { words, characters } = countText(editor.getEditableElement());
    editor.getStatusBar().setSegment("wordcount", `${words} word${words === 1 ? "" : "s"}, ${characters} character${characters === 1 ? "" : "s"}`);
  }
  registerPlugin("wordcount", (editor) => {
    updateStatus(editor);
    editor.on("change", () => updateStatus(editor));
  });

  // src/core/ElementPath.ts
  function getElementPath(root, node) {
    const path = [];
    let current = node;
    while (current && current !== root) {
      if (current.nodeType === Node.ELEMENT_NODE) {
        path.unshift(current);
      }
      current = current.parentNode;
    }
    return path;
  }

  // src/plugins/elementpath/index.ts
  function selectElement(editor, el) {
    const root = editor.getEditableElement();
    root.focus();
    const range = document.createRange();
    range.selectNode(el);
    setRange(range);
  }
  function updatePath(editor) {
    const root = editor.getEditableElement();
    const sel = window.getSelection();
    const anchorNode = sel && sel.rangeCount > 0 ? sel.getRangeAt(0).startContainer : null;
    if (!anchorNode || !root.contains(anchorNode)) return;
    const path = getElementPath(root, anchorNode);
    const nodes = [];
    path.forEach((el, i) => {
      if (i > 0) nodes.push(document.createTextNode(" \u203A "));
      const link = document.createElement("button");
      link.type = "button";
      link.className = "omar-text-editor-statusbar-path-item";
      link.textContent = el.tagName.toLowerCase();
      link.addEventListener("click", () => selectElement(editor, el));
      nodes.push(link);
    });
    if (nodes.length === 0) nodes.push(document.createTextNode("\xA0"));
    editor.getStatusBar().setSegmentContent("elementpath", nodes);
  }
  registerPlugin("elementpath", (editor) => {
    updatePath(editor);
    editor.on("SelectionChange", () => updatePath(editor));
    editor.on("change", () => updatePath(editor));
  });

  // src/plugins/spacing/index.ts
  var SPACING_OPTIONS = [
    { label: "None", value: "0" },
    { label: "Small", value: "8px" },
    { label: "Medium", value: "16px" },
    { label: "Large", value: "24px" },
    { label: "Extra Large", value: "32px" }
  ];
  function openSpacingDialog(editor) {
    const root = editor.getEditableElement();
    const current = editor.getBlockSpacing();
    const bookmark = bookmarkSelection(root);
    new Dialog({
      title: "Paragraph Spacing",
      submitLabel: "Apply",
      fields: [
        { name: "top", label: "Top", type: "select", options: SPACING_OPTIONS, defaultValue: current.top },
        { name: "bottom", label: "Bottom", type: "select", options: SPACING_OPTIONS, defaultValue: current.bottom },
        { name: "left", label: "Left", type: "select", options: SPACING_OPTIONS, defaultValue: current.left },
        { name: "right", label: "Right", type: "select", options: SPACING_OPTIONS, defaultValue: current.right }
      ],
      onSubmit: (values) => {
        root.focus();
        if (bookmark) restoreSelection(root, bookmark);
        editor.setBlockSpacing({
          top: String(values.top ?? ""),
          bottom: String(values.bottom ?? ""),
          left: String(values.left ?? ""),
          right: String(values.right ?? "")
        });
        editor.execCommand("__syncAfterExternalEdit");
      }
    });
  }
  registerToolbarButton({ name: "spacing", label: "Paragraph spacing", icon: "spacing", command: "spacing" });
  registerPlugin("spacing", (editor) => {
    editor.commands.register("spacing", () => openSpacingDialog(editor));
  });

  // src/core/VisualBlocks.ts
  var VISUAL_BLOCKS_CLASS = "omar-text-editor-visual-blocks";
  function toggleVisualBlocks(root) {
    return root.classList.toggle(VISUAL_BLOCKS_CLASS);
  }
  function isVisualBlocksActive(root) {
    return root.classList.contains(VISUAL_BLOCKS_CLASS);
  }

  // src/plugins/visualblocks/index.ts
  registerToolbarButton({
    name: "visualblocks",
    label: "Show blocks",
    icon: "visualBlocks",
    command: "visualblocks",
    isActive: (e) => isVisualBlocksActive(e.getEditableElement())
  });
  registerPlugin("visualblocks", (editor) => {
    editor.commands.register("visualblocks", () => {
      toggleVisualBlocks(editor.getEditableElement());
    });
  });

  // src/ui/Tour.ts
  var HIGHLIGHT_CLASS2 = "omar-text-editor-tour-highlight";
  var Tour = class {
    constructor(options) {
      this.options = options;
      this.currentIndex = 0;
      this.highlightedEl = null;
      this.handleKeydown = (e) => {
        if (e.key === "Escape") this.finish();
      };
      this.overlayEl = document.createElement("div");
      this.overlayEl.className = "omar-text-editor-tour-overlay";
      this.calloutEl = document.createElement("div");
      this.calloutEl.className = "omar-text-editor-tour-callout";
      this.overlayEl.appendChild(this.calloutEl);
      document.body.appendChild(this.overlayEl);
      document.addEventListener("keydown", this.handleKeydown);
      this.renderStep();
    }
    steps() {
      return this.options.steps;
    }
    renderStep() {
      const step = this.steps()[this.currentIndex];
      if (!step) {
        this.finish();
        return;
      }
      this.clearHighlight();
      const target = document.querySelector(step.selector);
      if (target) {
        target.classList.add(HIGHLIGHT_CLASS2);
        this.highlightedEl = target;
      }
      this.calloutEl.innerHTML = "";
      const title = document.createElement("div");
      title.className = "omar-text-editor-tour-title";
      title.textContent = step.title;
      const body = document.createElement("div");
      body.className = "omar-text-editor-tour-body";
      body.textContent = step.body;
      const progress = document.createElement("div");
      progress.className = "omar-text-editor-tour-progress";
      progress.textContent = `${this.currentIndex + 1} / ${this.steps().length}`;
      const controls = document.createElement("div");
      controls.className = "omar-text-editor-tour-controls";
      const skipBtn = document.createElement("button");
      skipBtn.type = "button";
      skipBtn.textContent = "Skip";
      skipBtn.addEventListener("click", () => this.finish());
      const isLast = this.currentIndex === this.steps().length - 1;
      const nextBtn = document.createElement("button");
      nextBtn.type = "button";
      nextBtn.className = "omar-text-editor-tour-primary";
      nextBtn.textContent = isLast ? "Done" : "Next";
      nextBtn.addEventListener("click", () => {
        if (isLast) {
          this.finish();
        } else {
          this.currentIndex += 1;
          this.renderStep();
        }
      });
      controls.appendChild(skipBtn);
      controls.appendChild(nextBtn);
      this.calloutEl.appendChild(title);
      this.calloutEl.appendChild(body);
      this.calloutEl.appendChild(progress);
      this.calloutEl.appendChild(controls);
      this.positionCallout(target);
    }
    positionCallout(target) {
      if (!target) {
        this.calloutEl.style.left = "50%";
        this.calloutEl.style.top = "50%";
        this.calloutEl.style.transform = "translate(-50%, -50%)";
        return;
      }
      const rect = target.getBoundingClientRect();
      this.calloutEl.style.transform = "none";
      this.calloutEl.style.left = `${rect.left}px`;
      this.calloutEl.style.top = `${rect.bottom + 10}px`;
    }
    clearHighlight() {
      this.highlightedEl?.classList.remove(HIGHLIGHT_CLASS2);
      this.highlightedEl = null;
    }
    finish() {
      this.clearHighlight();
      document.removeEventListener("keydown", this.handleKeydown);
      this.overlayEl.remove();
      this.options.onComplete();
    }
  };

  // src/core/Onboarding.ts
  var STORAGE_KEY_PREFIX = "omar-text-editor-tour-seen-";
  function hasTourBeenSeen(tourId) {
    try {
      return localStorage.getItem(STORAGE_KEY_PREFIX + tourId) === "1";
    } catch {
      return false;
    }
  }
  function markTourSeen(tourId) {
    try {
      localStorage.setItem(STORAGE_KEY_PREFIX + tourId, "1");
    } catch {
    }
  }

  // src/plugins/onboarding/index.ts
  var TOUR_ID = "default";
  function buildSteps(editor) {
    const steps = [];
    if (document.querySelector(".omar-text-editor-toolbar")) {
      steps.push({
        selector: ".omar-text-editor-toolbar",
        title: "Toolbar",
        body: "Format text, insert links/images/tables, and more from here."
      });
    }
    steps.push({
      selector: ".omar-text-editor-content",
      title: "Start typing",
      body: "This is your document. Click here and start writing."
    });
    if (document.querySelector(".omar-text-editor-statusbar")) {
      steps.push({
        selector: ".omar-text-editor-statusbar",
        title: "Status bar",
        body: "Word and character counts update here as you type."
      });
    }
    return steps;
  }
  function startOnboardingTour(editor) {
    const steps = buildSteps(editor);
    if (steps.length === 0) return;
    new Tour({
      steps,
      onComplete: () => markTourSeen(TOUR_ID)
    });
  }
  registerPlugin("onboarding", (editor) => {
    editor.commands.register("showOnboarding", () => startOnboardingTour(editor));
    const disabled = editor.getOptions().onboarding === false;
    if (disabled || hasTourBeenSeen(TOUR_ID)) return;
    setTimeout(() => startOnboardingTour(editor), 0);
  });

  // src/index.ts
  function init(options) {
    return new Editor(options);
  }
  var index_default = { init, Editor };
  return __toCommonJS(index_exports);
})();
//# sourceMappingURL=omar-text-editor.js.map
