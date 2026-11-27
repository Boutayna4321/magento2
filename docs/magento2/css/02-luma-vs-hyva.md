# Magento 2 from Scratch — Luma vs Hyvä

> **Series**: [Learning Path](../README.md) → Phase 2 (CSS), Article 2 of 2
> **Previous**: [`01-themes-and-less.md`](01-themes-and-less.md) — Themes & LESS
> **Next**: [`../js/01-requirejs.md`](../js/01-requirejs.md) — RequireJS
> **Deep dive**: [`../magento-styles.md`](../magento-styles.md)

---

AlpineCommerce uses **two frontend stacks** on the same site. This might seem
confusing at first. This article explains **why**, and what each stack brings.

---

## 1. The two stacks at a glance

| | **Luma** (legacy) | **Hyvä** (modern) |
|---|---|---|
| CSS | LESS + Magento UI library | Tailwind CSS v4 |
| JS | jQuery, RequireJS, Knockout | Alpine.js (+ Magewire) |
| Templates | PHTML (.php + .html) | PHTML for Hyvä modules |
| Performance | Heavy (loads jQuery, Knockout on every page) | Light (Alpine.js is tiny) |
| Learning curve | Steep (Magento-specific LESS mixins, Knockout bindings) | Moderate (Tailwind + vanilla JS) |

---

## 2. Why AlpineCommerce uses both

The project uses **Hyvä for the storefront** (product pages, cart, etc.) and
**Luma for the checkout only**. Here is why:

### Hyvä for the storefront

Hyvä is faster and simpler:
- Tailwind CSS means you style with HTML classes (`class="bg-blue text-white"`).
- Alpine.js replaces Knockout — reactive behavior in 5 lines instead of 50.
- No jQuery, no RequireJS overhead on regular pages.

```html
<!-- Hyvä template with Alpine.js -->
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content here</div>
</div>
```

### Luma for the checkout

The Magento **checkout** is a complex, payment-integration-heavy flow built on
jQuery + Knockout + UI Components. Reimplementing it in Hyvä requires custom
work or paid extensions (Hyvä Checkout). AlpineCommerce uses the **Luma theme
fallback** to keep the standard Magento checkout while serving Hyvä everywhere
else.

The **LumaCheckout** theme (`AlpineCommerce/LumaCheckout`) is a minimal Luma
child theme that only contains `_extend.less` — it overrides checkout colors to
match the AlpineCommerce brand (`#006bb4`).

---

## 3. How the fallback works

**Admin → Hyvä Themes → Theme Fallback → General Settings**:

| Setting | Value |
|---------|-------|
| Enable | `1` |
| Theme full path | `frontend/AlpineCommerce/LumaCheckout` |
| URL parts | `[{"path":"checkout\/index"}]` |

This means:
- `/checkout/*` → rendered by `AlpineCommerce/LumaCheckout` (Luma, LESS, RequireJS)
- Everything else → rendered by `Hyva/default` (Tailwind, Alpine.js)

> **Important**: On fallback pages, Tailwind and Alpine.js are **not loaded**.
> Only Magento's standard Luma stack (jQuery, RequireJS, Knockout) is available.
> Any styling must use LESS (`_extend.less`), not Tailwind.

---

## 4. The LumaCheckout theme — why only `_extend.less`?

Looking at the theme directory:

```
AlpineCommerce/LumaCheckout/
├── registration.php
├── theme.xml
└── web/css/source/_extend.less
```

That is it. Three files. Here is why:

1. **Parent inheritance** — `theme.xml` sets `<parent>Magento/luma</parent>`.
   All templates, layout XML, and base CSS come from the parent chain. The theme
   only needs to declare its existence and its one override file.

2. **CSS is the only customization** — the `_extend.less` file adds brand
   colors (`#006bb4`) to the checkout step titles, buttons, and form field
   focus states. No HTML changes needed; no JavaScript needed.

3. **Layout XML is inherited** — `checkout_index_index.xml` from
   `Magento/Checkout` is used as-is. The theme does not need to override it.

---

## 5. LESS vs Tailwind — the key difference

### Luma (LESS)

```less
// _extend.less
.checkout-index-index {
    .step-title {
        border-bottom: 3px solid #006bb4;
        color: #006bb4;
    }
    
    .action.primary {
        background: #006bb4;
    }
}
```

You write **CSS rules** using LESS variables and mixins. The LESS compiler
produces `styles-m.css` and `styles-l.css`.

### Hyvä (Tailwind)

```html
<!-- Template file -->
<h1 class="text-2xl font-bold text-primary">Checkout</h1>
<button class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded">
    Place Order
</button>
```

You write **HTML classes** and Tailwind generates the CSS at build time. No
LESS compilation step; just `npm run build` in `web/tailwind/`.

---

## 6. When to use which stack

| Task | Stack | Files |
|------|-------|-------|
| Storefront page (catalog, cart) | Hyvä | `web/tailwind/`, `*.phtml` |
| Checkout page | Luma | `web/css/source/_extend.less` |
| Admin panel | Luma | Module `view/adminhtml/` |
| Payment method UI | Luma | Module `view/frontend/web/js/` (RequireJS/Knockout) |

---

## 7. What you should remember

1. **Hyvä** = modern, fast, Tailwind + Alpine.js. Used for the storefront.
2. **Luma** = legacy, jQuery + RequireJS + Knockout + LESS. Used for checkout
   and admin.
3. AlpineCommerce uses **both** via the Hyvä Luma theme fallback — checkout
   URLs get Luma, everything else gets Hyvä.
4. On fallback (Luma) pages, **only** LESS works — Tailwind and Alpine.js are
   not loaded.
5. The `LumaCheckout` theme only has three files because it inherits everything
   from `Magento/luma` and only customizes CSS.

---

## 8. Where to go next

- **Previous**: [Themes & LESS](01-themes-and-less.md)
- **Phase 2 JS**: [`../js/01-requirejs.md`](../js/01-requirejs.md) — RequireJS
  (Luma JS), then [`../js/02-knockoutjs.md`](../js/02-knockoutjs.md)
- **Deep dive**: [`../magento-styles.md`](../magento-styles.md) — the complete
  2000-line guide covering Hyvä theme creation, Tailwind config, npm scripts,
  fallback setup, and deployment troubleshooting.

---

*Sources: `src/app/design/frontend/AlpineCommerce/LumaCheckout/theme.xml`,
`src/app/design/frontend/AlpineCommerce/LumaCheckout/web/css/source/_extend.less`,
`src/vendor/hyva-themes/magento2-luma-checkout/etc/config.xml`,
`src/app/design/frontend/AlpineCommerce/LumaCheckout/docs/existing-classes-reference.md`*
