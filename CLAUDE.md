# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

This is a **Claude Design handoff bundle** (see [README.md](README.md)), not an application. A user mocked up a UI in Claude Design (claude.ai/design) and exported it so a coding agent can reimplement it for real. There is **no build system, no test suite, no package manager, and no backend yet** — the repo currently contains only the design prototype and its runtime.

The working directory sits under XAMPP's `htdocs` (`C:\xampp\htdocs\AIFIN`), so the eventual target stack is likely PHP served by Apache — but nothing has been built yet. **Confirm the target stack with the user before implementing.**

### Your job (per README)

Recreate the design **pixel-perfectly** in whatever technology fits the target codebase. Match the visual output; do **not** copy the prototype's internal structure (the DC framework below) unless it happens to fit. Read the HTML/CSS directly rather than rendering it — everything (dimensions, colors, layout) is spelled out inline. Only render in a browser if the user explicitly asks.

## Files

- `project/AI Pro Contracts.dc.html` — **the primary design.** Read it in full before implementing.
- `project/support.js` — the Claude Design ("DC") React-based runtime that makes the `.dc.html` file render. This is vendored tooling; you will **not** port it — you reimplement the *design*, not the runtime.
- `project/uploads/` — image assets referenced by the design.

## How the `.dc.html` prototype works (so you can read it)

A `.dc.html` file is a self-contained React app rendered at runtime by `support.js` (which lazy-loads React/ReactDOM UMD from a CDN). Structure:

- `<x-dc>` wraps the template. `support.js` walks it and builds a React tree.
- **Custom directives** interpreted by the runtime:
  - `<sc-if value="{{ expr }}">` — conditional render.
  - `<sc-for list="{{ arr }}" as="x">` — repeat per item; `hint-placeholder-count` is editor-only.
  - `{{ expr }}` — interpolates a value from `renderVals()` (attribute values, inline `style` objects, `onClick` handlers, text).
- A single `<script type="text/x-dc" data-dc-script>` defines `class Component extends DCLogic` (a React.Component subclass). Its `renderVals()` returns the object whose keys back every `{{ }}` in the template; `state`/`setState` drive navigation. `data-props` on that script declares editor-tunable props (theme colors, toggles).

So to understand any screen: find its `<sc-if>` block in the template, then find the matching data arrays (`plans`, `kpis`, `contracts`, `ledger`, `queue`, etc.) built in `renderVals()` near the bottom of the file.

## The product being designed

"AIPRO Contracts" (Thai-language UI) — a system for **selling AI Pro access as prepaid contracts**. Core domain model, which the whole design encodes:

- **Unit "M"**: 1 M = 30 days of AI Pro access, always (the 30-day value is fixed system-wide; only the per-unit *price* varies by package/promo).
- **Contract**: 1-year term, extendable by up to +6 months total. Customers buy units up front (price locked at purchase) into an account "wallet," then later **redeem** units — binding them to a specific email — which queues a vendor to provision real AI Pro access.

The design has two top-level views (`state.view`):
- **Landing** (`isLanding`): marketing page — hero, how-it-works, rules, pricing tiers, FAQ.
- **Admin** (`isAdmin`): sidebar app with pages (`state.page`) — Dashboard, Contracts list, Contract detail (unit ledger + extension quota + bound seats), Customer wallets, Redeem queue, Extension requests, Packages & promotions.

Preserve this domain model and the Thai copy when reimplementing.

## Theming

The design ships a full light/dark token system as CSS custom properties on `:root` (with `prefers-color-scheme` plus an explicit `html[data-theme="light|dark"]` override). Fonts: IBM Plex Sans Thai (UI) and JetBrains Mono (numbers/IDs). Carry these tokens and the three-way theme cycle (system → light → dark) into the real implementation.
