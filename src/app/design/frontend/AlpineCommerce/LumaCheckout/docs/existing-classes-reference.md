# Existing Luma LESS Classes Reference — AlpineCommerce/LumaCheckout

This document lists every CSS class that **already exists** in the Luma theme (inherited via `Magento/blank` parent chain from `Magento/luma`) so you know what to target in `_extend.less`.

**Inheritance chain:** `Magento/blank` → `Magento/luma` → `AlpineCommerce/LumaCheckout`

Source files scanned:
- `vendor/magento/theme-frontend-blank/web/css/source/_*.less`
- `vendor/magento/theme-frontend-blank/Magento_Theme/web/css/source/_module.less`
- `vendor/magento/theme-frontend-luma/web/css/source/_theme.less`
- `vendor/magento/theme-frontend-luma/Magento_Theme/web/css/source/_module.less`
- `vendor/magento/theme-frontend-luma/Magento_Checkout/web/css/source/module/checkout/_*.less`

---

## 1. Page Structure / Layout

### `.page-wrapper`
The outermost page container. Stretches content area for sticky footer (min-height: 100vh). On desktop, children like `.breadcrumbs`, `.top-container`, `.widget` get `box-sizing: border-box; width: 100%`.

### `.page-main`
Main content area. `flex-grow: 1` so it fills remaining space. Contains `.page-title-wrapper`, `.columns`, etc.

### `.columns`
Two-column layout wrapper. Contains `.column.main` (primary content) and `.sidebar-main` / `.sidebar-additional` (sidebars). On desktop uses flexbox; on mobile becomes block.

### `.column.main`
The primary content column.

### `.sidebar-main`
Left sidebar column. On 2columns-right, gets `padding-left` instead of `padding-right`.

### `.sidebar-additional`
Right/additional sidebar.

### `.page-title-wrapper`
Wraps `.page-title`. On desktop, `.page-title` is `display: inline-block`.

### `.page-title`
The page heading. On desktop, inline-block; next to it a `.action` floats right.

### `.page-header`
Entire header block. Gets `border-bottom: 1px solid @border-color__base`.

### `.page-footer`
Entire footer block. On desktop, `margin-top: auto` for sticky footer.

### `.copyright`
Footer copyright bar. Full-width background `@copyright__background-color` (gray-middle4), white text.

---

## 2. Header

### `.header.panel`
The top header panel (above logo). Contains `.header.links` and `.switcher` (store/language switcher).

### `.header.links`
Inline list of header links (My Account, Register, Sign In, etc.). On mobile, hidden; on desktop shown inline. List items are `> li` with inline margins.

### `.panel.header`
Same element as `.header.panel` — parent of `.links` and `.switcher`.

### `.header.content`
The main header content area (logo + nav). Extends `.abs-add-clearfix`.

### `.logo`
Site logo. Floats left, max 50% width. Contains `<img>`. On desktop, centered (`margin: 0 auto 25px 0`).

### `.customer-welcome`
The customer name dropdown. On desktop uses `.lib-dropdown` mixin. Has `.customer-name`, `.customer-menu`, `.greet`.

### `.customer-menu`
Dropdown menu inside `.customer-welcome`. Hidden by default; shown when `.customer-welcome` has `.active`.

### `.action.switch`
The toggle button inside `.customer-welcome`. Reset button style, white text on desktop. Shows `@icon-down` / `@icon-up` icons.

### `.greet`
The "Welcome, [name]" text. Hidden on desktop (inside `.customer-welcome`).

### `.customer-name`
Customer name display text. Hidden on mobile.

### `.switcher`
Generic switcher element (store/language switcher, navigation). On mobile: font-size 1.6rem, clickable header. Positioned in `.page-header .panel.wrapper` and `.page-footer`.

### `.switcher .options`
The dropdown options container. Uses `.lib-dropdown()`. Contains `ul.dropdown`.

### `.switcher .options ul.dropdown`
Dropdown list of options. Each `a` is a block link with padding.

### `.switcher .label`
Switcher label. Visually hidden.

### `.widget`
CMS widget block. Clear both. `.block-title` extends `.abs-block-widget-title`.

### `.widget.block`
Widget block inside header/footer. Margin `@indent__base 0`.

### `.sidebar .widget.block`
Sidebar widget blocks. `:not(:last-child)` gets bottom margin `@indent__xl`.

### `.no-display`
Element with no display. Extends `.abs-no-display`.

---

## 3. Navigation (Main Menu)

### `.nav-sections`
Container for the main navigation. Has background `@navigation__background` (gray94).

### `.nav-toggle`
The hamburger menu icon on mobile. Hidden on desktop. `position: absolute`, top 15px, left 15px.

### `.nav-sections-items`
Container for nav section items. Uses clearfix.

### `.nav-sections-item-title`
Each navigation section title (mobile). On mobile: `background: darken(@navigation__background, 5%)`, border, height 71px. Active state gets transparent background.

### `.nav-sections-item-content`
Dropdown content area. Hidden by default on mobile; shown when parent `.switcher-options` has `.active`.

### `.switcher`
Generic switcher element (store switcher, navigation sections). On mobile: font-size 1.6rem, clickable header.

### `.switcher-trigger`
The clickable part of a switcher. Contains a `<strong>` label with dropdown icon.

### `.switcher-options`
The dropdown container. `.active` class shows `.switcher-dropdown`.

### `.switcher-dropdown`
The dropdown list. Hidden by default; shown when parent has `.active`.

### `.navigation`
The actual menu navigation tree. Uses `.lib-main-navigation()` (mobile) and `.lib-main-navigation-desktop()` (desktop).

### `.header.links` (inside nav)
On mobile nav, header links are shown as a list with bold font, borders between items.

---

## 4. Breadcrumbs

### `.breadcrumbs`
Uses `.lib-breadcrumbs()` mixin. Standard breadcrumb list with `>` separators.

---

## 5. Buttons / Actions

### `.action`
Base class for all action links/buttons. Can be combined:

### `.action.primary` / `.action-primary`
Primary button style. Uses `.lib-button-primary()` mixin. This is the main CTA button (e.g., "Place Order", "Add to Cart").

### `.action.back`
Back action link. Hidden by default (`.actions-toolbar .secondary .action.back { display: none }`).

### `.action.skip`
Skip-to-content link. Visually hidden unless focused (screen readers).

### `.action.skip-wrapper`
Container for skip link.

### `button`
Native `<button>` elements. `:active` gets `box-shadow` inset highlight.

### `a.action.primary`
Links styled as primary buttons. Uses `.lib-link-as-button()` mixin.

### `.action-toggle`
Toggle action (used in payment option titles). Shows expand/collapse icon.

### `.action-close`
Close action (used in modals). In checkout modals, hidden on desktop.

### `.action-save-address`
Save address button in checkout modals. On mobile: `width: 100%`. On desktop: floats right.

### `.action-hide-popup`
Hide popup action. Styled as link.

---

## 6. Forms / Fields

### `.fieldset`
Fieldset container. Uses `.lib-form-fieldset()` mixin. On desktop, `.legend` extends `.abs-margin-for-forms-desktop`.

### `.field`
Individual form field. Uses `.lib-form-field()` mixin.

### `.field.no-label`
Field without visible label — label is visually hidden.

### `.field.choice`
Checkbox/radio choice field. Label is `display: inline`, normal weight.

### `.field.date`
Date field. Extends `.abs-field-date`. Contains `.time-picker`.

### `.field._error`
Field with error state. Input/select/textarea gets `@checkout-field-validation__border-error` border.

### `.field._with-tooltip`
Field with tooltip. Uses `.abs-field-tooltip`.

### `.control`
Wrapper around the actual input element(s).

### `.label`
Field label. On desktop: `@font-weight__semibold`; in sidebar forms: `text-align: left`, margin bottom.

### `.legend`
Fieldset legend. Contains `<strong>`.

### `.note`
Form field note/helper text. In checkout shipping: `font-size: @font-size__base`, `margin-top: @indent__s`.

### `.fields`
Container for grouped fields. Children are `.field`.

### `.input-text`
Text input elements. Standard form text input.

### `select` / `.select`
Dropdown selects. On desktop: has select arrow background image, 32px height.

### `.input.focus`
Focus state on inputs.

### `.tooltip`
Form field tooltip. Uses `.lib-tooltip(right)` mixin.

### `.tooltip-content`
Tooltip content. Min-width 200px, normal white-space.

### `._has-datepicker`
Input with date picker. The calendar trigger button gets calendar icon.

### `.cald` / `.calendar`
Calendar/date picker popup.

---

## 7. Checkout — Page Structure

### `.checkout-index-index`
**Body class on the checkout page.** All checkout-specific styles are scoped under this. This is your entry point in `_extend.less`.

### `.checkout-container`
Outer checkout wrapper.

### `.checkout-onepage-success`
Checkout success page (`.checkout-onepage-success`).

---

## 8. Checkout — Steps & Progress

### `.opc-wrapper`
One-page checkout steps wrapper.

### `.opc`
Steps list. Extends `.abs-reset-list`.

### `.step-title`
Step title (e.g., "Shipping Address", "Payment Method"). Extends `.abs-checkout-title`. Has `border-bottom` using `@checkout-step-title__border`.

### `.step-content`
Step content container.

### `.checkout-shipping-method`
Shipping method section. `.step-title` gets `margin-bottom: 0`.

### `.checkout-payment-method`
Payment method section. `.step-title` removes border/margin.

### `.no-quotes-block`
Fallback block when no shipping methods available.

### `.checkout-shipping-address`
Shipping address form container.

### `.checkout-billing-address`
Billing address section.

### `.checkout-billing-address-details`
Display of saved billing address (non-editable view). Line-height @checkout-billing-address-details__line-height, padding `0 0 0 23px`.

### `.billing-address-same-as-shipping-block`
Checkbox container for "billing same as shipping".

### `.payment-method-note`
Note above billing address form.

### `.no-payments-block`
Message shown when no payment methods available.

### `.payments`
Payments container. Contains `.legend` (visually hidden).

---

## 9. Checkout — Order Summary (Right Sidebar)

### `.opc-block-summary`
The order summary block. Background `@checkout-summary__background-color` (white-smoke), padding `22px @indent__l`.

### `.opc-summary-wrapper`
Wrapper containing `.modal-header` on mobile (modal version). On desktop, hides `.action-close` in modal header.

### `.opc-block-summary > .title`
Summary block title. Extends `.abs-checkout-title`, `display: block`.

### `.table-totals`
Totals table inside summary. Extends `.abs-sidebar-totals`.

### `.opc-block-summary .mark`
"Label" column in totals. `.value` gets color `@checkout-summary-mark-value__color` (gray40), `display: block`.

### `.grand.incl` / `.grand.excl`
Grand total rows. Excl gets border-top, font-size 14, normal weight.

### `.not-calculated`
Text for not-calculated totals. Italic, normal white-space.

### `.items-in-cart`
Cart items title in summary. Clickable (cursor: pointer) with expandable icon. `.active` shows up icon.

### `.minicart-items-wrapper`
Cart items list inside summary. Has max-height 370px, padding 15px.

### `.product-item`
Each product row in summary. Contains `.product-item-details` and `.product-item-inner`.

### `.product-item-details`
Product details area. Extends clearfix.

### `.product-item-inner`
Inner table-like layout for product name + price. Display table.

### `.product-item-name-block`
Product name column. Block display.

### `.subtotal`
Product subtotal. Block display, left-aligned.

### `.price`
Price display. Font-size 16px (desktop: 14px for excluding-tax), regular weight.

### `.price-including-tax` / `.price-excluding-tax`
Tax-included/excluded price. Excluding-tax gets font-size 10px.

### `.message`
Message inside product item (e.g., "no options").

### `.actions-toolbar` (in summary)
Actions toolbar at bottom of summary. `.secondary` gets top border, block display.

### `.column.main .product-item`
Inside `.opc-block-summary .column.main`, removes margin/padding from product items.

---

## 10. Checkout — Shipping Address

### `.form-shipping-address`
Shipping address form. Gets `margin-top/bottom @checkout-shipping-address__margin-top` (28px). Max-width 500px on desktop.

### `.form-login`
Login form on checkout. Same styles as shipping address form.

### `.shipping-address-items`
Container for saved address items (radio selection). `font-size: 0`.

### `.shipping-address-item`
Each saved address. Border 2px transparent, padded `@indent__base`, width 1/2 on tablet, 1/3 on desktop, 100% on mobile.

### `.shipping-address-item.selected-item`
Selected address. Border-color = `@active__color`, gets checkmark icon after pseudo-element.

### `.action-select-shipping-item`
Radio button for selecting address. Floats right, hidden on desktop for selected items.

### `.action-show-popup`
Button to show "add new address" popup. Margin bottom.

### `.edit-address-link`
Edit address link. Styled as action button (`.abs-action-button-as-link`).

### `.table-checkout-shipping-method`
Shipping methods table. Header hidden, rows have border-top on cells.

### `.methods-shipping`
Shipping methods container. `.actions-toolbar .action.primary` extends `.abs-button-l`.

### `.col-price`
Price column in shipping table. Semibold weight.

### `.row-error`
Error row in shipping table. No border-top, reduced padding.

---

## 11. Checkout — Payments

### `.payment-method`
Each payment method block. `.payment-method-title` is clickable header.

### `.payment-method-title`
Payment method title. Padding using `@checkout-payment-method-title__padding`. Contains `.payment-icon` and `.action-help`.

### `.payment-icon`
Payment method icon image. Inline-block, right margin.

### `.action-help`
Help icon next to payment method title.

### `.payment-method-content`
The payment form/content. Hidden by default (`display: none`); shown when parent `.payment-method` has `._active`.

### `.payment-method._active`
Active payment method. Child `.payment-method-content` becomes `display: block`.

### `.payment-group`
Group of payment methods. Consecutive groups get top margin on `.step-title`.

### `.field-select-billing`
Billing address selector dropdown. Label visually hidden.

### `.billing-address-form`
Billing address form. Max-width = `@checkout-shipping-address__max-width`.

### `.fieldset` (in payment-method-content)
Fieldset within payment content. `:not(:last-child)` gets bottom margin.

### `.payment-option`
Individual payment option (e.g., credit card fields). `._active` shows content. `._collapsible` has clickable title.

### `.payment-option-title`
Payment option title. Has border-top, padding. Contains `.action-toggle`.

### `.payment-option-content`
Payment option content. Hidden by default. Extends `.abs-discount-code`.

### `.action-apply`
Apply coupon/discount action.

### `.payment-option-inner`
Inner wrapper for payment option fields.

### `.credit-card-types`
Credit card type selector. List of icons. `.item._active` shows in color, `._inactive` at 40% opacity.

### `.ccard`
Credit card form container. Has `.fields` (`.year` with left padding, `.select` with padding).

### `.ccard .month .select`
Month dropdown. Width 140px.

### `.ccard .year .select`
Year dropdown. Width 80px.

### `.ccard .captcha, .ccard .number .input-text`
Card number input. Width 225px.

### `.field.cvv`
CVV field. `.control` is inline-block with right padding.

### `.cvv .label` / `.cvv .input-text`
CVV label (block display), input width 55px.

### `.fieldset.group.group-2 .field`
In CCV form, group-2 fields get `width: auto !important`.

---

## 12. Checkout — Progress Bar

### `.checkout-progress-bar`
The step progress indicator. Uses `.lib-progress-bar()` mixin from the UI library.

---

## 13. Checkout — Modals (Address Forms)

### `.modal-popup` (within `.checkout-index-index`)
Modal windows on checkout. Contains `.fieldset` and `.modal-footer`.

### `.modal-header`
Modal header. On desktop, `.action-close` is hidden.

### `.modal-footer`
Modal footer. Contains `.action-save-address` and `.action-hide-popup`.

### `.form-shipping-address` (within modal)
Same form styles as above but inside modal, max-width on desktop.

---

## 14. Checkout — Estimated Total / Minicart

### `.opc-estimated-wrapper`
Estimated total wrapper (mobile only). Gets background `@checkout-step-content-mobile__background`, top/bottom borders. Desktop: hidden via `.abs-no-display-desktop`.

### `.estimated-block`
The "estimated total" label. Floats left, bold.

### `.estimated-label`
Label within estimated block.

### `.minicart-wrapper`
Minicart wrapper inside estimated total. `.action.showcart` gets reset button style, primary color icon.

---

## 15. Cart

### `.cart-table`
Cart page table. Extends `.lib-table-bordered()` with special cart styling.

### `.cart`
Cart container.

### `.minicart-wrapper`
Minicart dropdown wrapper.

---

## 16. Minicart (Header)

### `.minicart-wrapper`
Wrapper around minicart content.

### `.minicart-items`
Minicart items list.

### `.minicart-items-wrapper`
Scrollable wrapper inside minicart.

### `.product-item` (in minicart)
Each product row.

### `.product-item-details`
Product details in minicart.

### `.product-item-info`
Product image/link container.

### `.product-item-name`
Product name link.

### `.product-item-price`
Price display.

### `.product-item-actions`
Action buttons (edit, delete).

### `.action.delete`
Delete/remove button.

### `.action.edit`
Edit button.

### `.btn-slide`
Slide toggle button for minicart.

### `.counter`
Quantity counter.

### `.counter-number`
The number inside counter.

### `.counter-label`
Label text in counter.

> See section **25. Minicart (Header Dropdown)** for the full detailed minicart reference with variables and mixin details.

---

## 17. Messages / Alerts

### `.message`
Base message class. Types:

### `.message.info` / `.message.error` / `.message.warning` / `.message.notice` / `.message.success`
Different message types. Each gets appropriate icon via `.lib-message-icon-inner()`.

### `.message.global`
Global message (cookie notice, demo store). `.noscript` and `.cookie` get note styling. `.cookie` is fixed at bottom. `.demo` gets caution styling.

### `.messages`
Container for multiple messages.

---

## 18. Tables

### `.table-wrapper`
Table wrapper. Margin-bottom.

### `.table`
Base table class. Not `.cart` or `.totals`: gets bordered styling (`.lib-table-bordered()` light variant).

### `.table.totals`
Totals table. Gets special sidebar totals styling.

### `.table.cart`
Cart table. Excluded from general table styling.

### `.table.table-comparison`
Product comparison table.

### `.data-table-definition-list`
Responsive definition list table (mobile: collapses).

---

## 19. Pager (Pagination)

### `.pages`
Pager container. Uses `.lib-pager()`.

### `.action.previous` / `.action.next`
Previous/next page arrows. Width 34px, side margins.

### `.pages.item`
Each page number item.

### `.pages.items`
Items container.

---

## 20. Block / Widget

### `.block`
Generic block container.

### `.block-title`
Block title. Uses `.abs-block-title` / `.abs-block-widget-title`.

### `.block-content`
Block content area.

### `.switcher-store`
Store switcher.

---

## 21. Collapsible Navigation (Sidebar)

### `.block-collapsible`
Collapsible block.

### `.switch.close`
Close state of collapsible.

### `.switch.active`
Active state of collapsible.

---

## 22. Common Helper Classes (extends used throughout)

These are abstract classes extended into selectors. You can target them too:

| Class | Purpose |
|---|---|
| `.abs-button-primary all` | Primary button styling |
| `.abs-button-l all` | Large button |
| `.abs-button-reset()` | Button reset (no styles) |
| `.abs-action-button-as-link all` | Link as button (text-only) |
| `.abs-action-button-as-linkDesktop all` | Desktop link-as-button |
| `.abs-button-responsive all` | Responsive button (mobile full-width) |
| `.abs-button-l all` | Large button base |
| `.abs-no-display all` / `.abs-no-display-s all` / `.abs-no-display-desktop all` | Display none at respective sizes |
| `.abs-visually-hidden all` | Screen reader only |
| `.abs-add-clearfix all` / `.abs-add-clearfix-desktop all` | Clearfix |
| `.abs-reset-list all` | Reset list styles |
| `.abs-margin-for-forms-desktop all` | Margin for form fields on desktop |
| `.abs-checkout-title all` | Checkout step/title styling |
| `.abs-sidebar-totals all` / `.abs-sidebar-totals-mobile all` | Sidebar totals table |
| `.abs-field-tooltip all` | Field with tooltip |
| `.abs-field-date all` | Date field |
| `.abs-product-options-list all` | Product options list |
| `.abs-checkout-tooltip-content-position-top all` | Tooltip content position |
| `.abs-discount-code all` | Discount code styling |
| `.abs-block-title all` / `.abs-block-widget-title all` | Block/widget title |
| `.abs-block-content all` | Block content |
| `.abs-add-box-sizing all` / `.abs-add-box-sizing-desktop all` | Box-sizing border-box |

---

## 23. Variables Already Overridden by Luma

These are defined in `theme-frontend-luma/web/css/source/_theme.less` (Luma's variable overrides). Your theme inherits these. Key ones relevant to checkout:

| Variable | Luma Value | Role |
|---|---|---|
| `@primary__color` | *(inherited from Blank)* | Primary brand color — active borders, links |
| `@active__color` | *(inherited from Blank)* | Active/selected color (shipping address border) |
| `@checkout-step-title__border` | `@border-width__base solid @color-gray80` | Step title bottom border |
| `@checkout-step-title__font-size` | `26px` | Step title font size |
| `@checkout-step-title__font-weight` | `@font-weight__light` | Step title weight |
| `@checkout-step-content-mobile__background` | `@color-gray-light01` | Mobile step content background |
| `@checkout-summary__background-color` | `@color-white-smoke` | Order summary background |
| `@checkout-summary__padding` | `22px @indent__l` | Order summary padding |
| `@checkout-summary-mark-value__color` | `@color-gray40` | Summary mark value color |
| `@checkout-summary-items__max-height` | `370px` | Max height of summary items |
| `@checkout-summary-items__padding` | `15px` | Summary items padding |
| `@checkout-shipping-address__max-width` | `500px` | Max width of address form |
| `@checkout-shipping-address__margin-top` | `28px` | Top margin on address form |
| `@checkout-shipping-item__border` | `2px solid transparent` | Shipping address item border |
| `@checkout-shipping-item__active__border-color` | `@active__color` | Active address border |
| `@checkout-shipping-method__border` | `@checkout-step-title__border` | Shipping method row border |
| `@checkout-shipping-method__padding` | `@indent__base` | Shipping method row padding |
| `@checkout-payment-method-title__border` | `@checkout-shipping-method__border` | Payment title border |
| `@checkout-payment-method-title__padding` | `@checkout-shipping-method__padding` | Payment title padding |
| `@checkout-modal-popup__width` | `800px` | Modal popup width |
| `@header__background-color` | `false` (transparent) | Header background |
| `@header-panel__background-color` | `@color-gray-middle4` | Top panel background |
| `@header-panel__text-color` | `@color-white` | Top panel text color |
| `@header-icons-color` | `@color-gray46` | Header icon color |
| `@header-icons-color-hover` | `@color-gray20` | Header icon hover |
| `@footer__background-color` | `@color-gray-light01` | Footer background |
| `@copyright__background-color` | `@color-gray-middle4` | Copyright bar bg |

---

## 24. How to Use This in `_extend.less`

Your current `_extend.less` already targets `.checkout-index-index`. Here are common override patterns:

```less
// Override existing class styles (later in cascade wins)
.checkout-index-index {
    .opc-wrapper {
        .step-title {
            // Already exists — border-bottom, color, font-size, padding
            border-color: #your-color;
        }
    }

    .opc-block-summary {
        // Already exists — background, padding
        border-top: 3px solid #006bb4;
    }

    .shipping-address-item {
        // Already exists — border, padding, width
        border-color: #your-color;
    }

    .payment-method-title {
        // Already exists — padding, border
        background: #your-color;
    }

    .action.primary {
        // Already exists — primary button (.lib-button-primary)
        // Uses @primary__color from theme variables
    }
}

// Page-wide header/footer (visible on all Luma fallback pages)
.page-header {
    .header.panel {
        background: #your-color;
    }
}

.page-footer {
    .copyright {
        background: #your-color;
    }
}
```

**Key principle:** Your `_extend.less` is loaded **after** all parent theme styles via `@magento_import`, so any class you define here **overrides** the existing Luma styles for that class.

---

## 25. Minicart (Header Dropdown)

### `.minicart-wrapper`
Wrapper around the minicart dropdown. Uses `.lib-dropdown()` mixin. Floats right. On desktop, `.block-minicart` is 390px wide.

### `.block-minicart`
The minicart dropdown panel. Padding `25px @minicart__padding-horizontal`.

### `.block-minicart .block-title`
Block title — hidden (`display: none`).

### `.minicart-items`
Minicart items list. Uses `.lib-list-reset-styles()`. Contains `.product-item`.

### `.minicart-items-wrapper`
Scrollable wrapper. Border 1px solid `@minicart__border-color`, overflow-x: auto, padding 15px.

### `.product-item` (in minicart)
Each cart item. Padded `@indent__base 0`; first-child gets `padding-top: 0`. Has border-top on non-first items.

### `.product-item > .product`
Clearfix container.

### `.product-item-details`
Product details. Padding-left 88px (to make room for image).

### `.product-item-price`
Price area.

### `.price` (in product-item-details)
Bold font-weight.

### `.price-including-tax` / `.price-excluding-tax`
Margin `@indent__xs 0`.

### `.product-item-name`
Product name. Font-weight regular, margin bottom `@indent__s`. Link inherits `@link__color`.

### `.product-image-wrapper`
Image wrapper. Extends `.abs-reset-image-wrapper`.

### `.action.showcart`
Cart icon button (toggles minicart). Uses cart icon `@icon-cart`. `.text` is visually hidden.

### `.counter.qty`
Quantity counter badge. Background `@active__color`, color `@page__background-color`, height 24px, border-radius 2px.

### `.counter-number`
The number text inside counter. Has text-shadow.

### `.counter-label`
"Items" label. Visually hidden.

### `.action.close`
Close button on minicart. Top-right corner, remove icon.

### `.action.edit` / `.action.delete`
Edit (pencil icon) and delete (trash icon) actions. Icon buttons using `.lib-icon-font()`.

### `.items-total`
Total items count. Floats left. `.count` is bold.

### `.subtotal`
Cart subtotal text. Floats right. `.label` uses `.abs-colon`.

### `.amount .price-wrapper:first-child .price`
Subtotal price. Large bold font.

### `.message` (in minicart)
Margin adjustments.

### `.details-qty`
Qty details.

### `.update-cart-item`
Update qty text.

### `.toggle`
Expand/collapse product options. Uses `.abs-toggling-title`.

### `.product.options.list`
Product options list. Extends `.abs-product-options-list` and `.abs-add-clearfix`.

### `.weee[data-label]`
WELL fee display. Font-size 11px. `.label` hidden.

### `.product.pricing`
Pricing wrapper. Margin-top 3px.

### `.block-content > .actions`
Actions at bottom of minicart. `.primary .action.primary` extends `.abs-button-l`, full width.

---

## 26. Cart Page (`checkout-cart-index`)

### `.checkout-cart-index`
Body class for cart page.

### `.cart-container`
Cart wrapper.

### `.form-cart`
Cart form. Extends `.abs-shopping-cart-items`.

### `.cart-summary`
Order summary block on cart page. Background `@sidebar__background-color`.

### `.cart-summary > .title`
Summary title. Hidden on desktop (`display: none`).

### `.cart-totals`
Totals table. Extends `.abs-sidebar-totals`.

### `.cart`
Cart table container.

### `.cart.table-wrapper .cart`
The actual cart table.

### `.cart .cart thead tr th.col`
Table headers. Border-bottom, padding.

### `.cart .cart > .item`
Each cart item row. Border-bottom.

### `.cart .col.item`
Item cell. Position relative, padding.

### `.cart .col.price / .col.subtotal / .col.msrp / .col.qty`
Column cells. Centered text on desktop, 33% width on mobile.

### `.cart .col.qty .input-text`
Quantity input. Width 60px, height 36px.

### `.cart .item-actions`
Action buttons area. Contains `.action-edit` and `.action-delete`.

### `.cart .product-item-photo`
Product image. Position absolute, max-width 65px.

### `.cart .product-item-name`
Product name link. Font-size 18px.

### `.cart .item-options`
Product options. Extends `.abs-product-options-list` and `.abs-add-clearfix`.

### `.cart .cart-tax-total`
Tax total. Extends `.abs-tax-total`.

### `.cart-discount`
Discount coupon block. Extends `.abs-discount-block`.

### `.cart-empty`
Empty cart message.

### `.checkout-methods-items`
Checkout button list. Extends `.abs-reset-list`. `.action.primary.checkout` extends `.abs-button-l`, full width.

### `.cart-products-toolbar`
Toolbar above/below cart products.

---

## 27. Key LESS Variables (Blank/Luma)

These are defined in `lib/web/css/source/lib/variables/` and overridden in Luma's `_theme.less`. You can override them in your own `_theme.less` (copy Luma's first). Key ones to know:

### Color variables
| Variable | Default | Role |
|---|---|---|
| `@primary__color` | `#006bb4` (Luma) | Primary brand (links, active states, buttons) |
| `@secondary__color` | `#f5f5f5` (Luma, gray-gray-light1) | Secondary brand |
| `@link__color` | `@theme__color__primary-alt` | Link color |
| `@link__hover__color` | *(darker)* | Link hover |
| `@border-color__base` | `@color-gray80` | Base border color |
| `@page__background-color` | `@color-gray-light0` | Page background |
| `@color-gray-light0` | `#faf8f7` | Light gray bg |
| `@color-gray-light1` | `#f5f5f5` | Light gray bg |
| `@color-gray94` | `#f5f5f5` | Navigation bg |
| `@color-gray80` | `#d9d6d6` | Border color |

### Spacing variables
| Variable | Value | Role |
|---|---|---|
| `@indent__base` | `15px` | Default indent/gutter |
| `@indent__s` | `10px` | Small indent |
| `@indent__xs` | `5px` | Extra small indent |
| `@indent__l` | `20px` | Large indent |
| `@indent__xl` | `30px` | Extra large indent |

### Breakpoint variables
| Variable | Value | Role |
|---|---|---|
| `@screen__s` | `480px` | Small screen max |
| `@screen__m` | `768px` | Medium (desktop threshold) |
| `@screen__l` | `1024px` | Large |

---

## 28. File Loading & Override Priority

When `@media-common = true` (default, includes all devices), styles load first. Then mobile (`max-width: @screen__s`) and desktop (`min-width: @screen__m`) media queries are applied.

**Override order in your `_extend.less`:**
1. Blank theme styles (base)
2. Luma theme overrides (on top of Blank)
3. Your `AlpineCommerce/LumaCheckout/_extend.less` (loaded via `@magento_import`, after everything else)

This means your rules in `_extend.less` **always win** over Luma's defaults (assuming equal specificity). If you need to win against higher specificity, just add more specificity — e.g., `.checkout-index-index .step-title` instead of just `.step-title`.

**To deploy changes:**
```bash
rm -rf pub/static/frontend/AlpineCommerce/LumaCheckout/* var/view_preprocessed/*
bin/magento setup:static-content:deploy -f -t AlpineCommerce/LumaCheckout <locale>
bin/magento cache:flush
```
