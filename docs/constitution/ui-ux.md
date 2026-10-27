# UI/UX — Project Design System

> **Status:** Approved by the human on 2026-10-26.

> **Hard rule for the agent:** before creating or modifying ANY interface, read this document
> and respect it. Do not improvise colors, spacing, or typography. If something is not defined
> here, use the baseline below; if the project needs another direction, override it in the
> [Project Override](#project-override) section — never mid-feature without approval.

This is an **opinionated baseline**. It exists so the UI is consistent and decent by default,
not to show off. A boring, coherent form is better than a "creative" irregular one.

---

## 0. Principles (priority order)

1. **Consistency > originality.** Same components, same spacing, everywhere.
2. **Clear hierarchy.** The eye should know in one second what matters on the screen.
3. **Generous whitespace.** Clutter is the #1 mistake in AI-generated UI.
4. **Accessible by default.** Contrast, visible focus, and touch targets are not optional.
5. **Complete states.** Every data view defines: loading, empty, error, and populated.
6. **Less is more.** Do not add shadows, borders, gradients, or icons "just because".

---

## 1. Color tokens (roles, not names)

Use **semantic roles**, never raw colors (`#3B82F6`) inside components. Define the tokens once
(CSS variables / Tailwind config / theme) and reference the role.

| Role | Use | Light baseline | Dark baseline |
|---|---|---|---|
| `--bg` | App background | `#FFFFFF` | `#0B0F17` |
| `--surface` | Cards, panels | `#F7F8FA` | `#131A24` |
| `--border` | Borders, dividers | `#E4E7EC` | `#26303D` |
| `--text` | Primary text | `#111827` | `#E6EAF0` |
| `--text-muted` | Secondary text | `#6B7280` | `#9AA5B4` |
| `--primary` | Primary action, links | `#2563EB` | `#3B82F6` |
| `--primary-contrast` | Text on `--primary` | `#FFFFFF` | `#FFFFFF` |
| `--success` | Confirmations | `#16A34A` | `#22C55E` |
| `--warning` | Warnings | `#D97706` | `#F59E0B` |
| `--danger` | Errors, destructive | `#DC2626` | `#EF4444` |

Rules:
- **One primary color.** Do not introduce a second brand color without approval.
- States (success/warning/danger) are for feedback only, never decoration.
- Text/background contrast minimum **4.5:1** (WCAG AA). Verify it; do not assume it.
- If dark mode exists, define both columns or use `prefers-color-scheme`.

---

## 2. Spacing (4px scale)

One system. Arbitrary values (`13px`, `27px`) are forbidden.

```
4 · 8 · 12 · 16 · 24 · 32 · 48 · 64
```

- Internal control padding: `8–12`. Card padding: `16–24`.
- Spacing between form fields: `16`.
- Spacing between sections: `32–48`.
- Maximum reading width: **~70 characters** (`max-width` ~640–720px).
- Page container: centered, with `16` lateral padding (mobile) / `24–32` (desktop).

---

## 3. Typography

- **One family** for UI (system stack or a single webfont). At most a second for display.
- Scale (rem): `0.75 · 0.875 · 1 · 1.125 · 1.25 · 1.5 · 2 · 2.5`.
- Base body: `1rem` (16px), `line-height` **1.5**. Headings `line-height` 1.2.
- Weights: 400 body, 500–600 emphasis/labels, 700 headings. Do not use 5 different weights.
- Numbers in tables/amounts: tabular (`font-variant-numeric: tabular-nums`).

---

## 4. Hierarchy and layout

- Every screen has **one** `h1` and a clear primary action (max one per view).
- Actions: **1 primary** (solid button) + secondary (outline/ghost). Not 4 solid buttons.
- Group content in cards/sections with headings. Do not put everything on a flat wall.
- Consistent alignment: forms with labels above the field, left-aligned.
- Responsive grid: 1 column on mobile, expand at `sm/md/lg`. No horizontal scroll.

---

## 5. Components (minimum rules)

**Buttons** — minimum height `40px` (touch target `44px`), consistent `border-radius`
(`6–8px`), defined `hover/active/focus/disabled` states, verb text ("Save changes", not "OK").
Destructive button in `--danger` with confirmation.

**Inputs / forms** — label always visible (not placeholder-only), error states with a message
below in `--danger`, visible focus outline, width matching the data (a postal code does not
fill the row). Clear, specific validation ("The email is invalid", not "Error").

**Tables / lists** — sticky headers when scrolling, numeric alignment to the right, row `hover`,
comfortable density (no 60px or 20px rows). Pagination or scroll; never dump 10,000 rows.

**Feedback** — toasts for ephemeral confirmations, inline banners for section errors, never
browser `alert()` in production.

**Modals** — only for focused decisions. Trapped focus, Esc and outside-click close, visible
close button. Do not nest modals.

---

## 6. The 4 mandatory states

Any view that displays remote data MUST implement all four. A missing state makes the feature
incomplete:

1. **Loading** — skeleton or spinner, never a blank screen.
2. **Empty** — useful message + action ("You have no books yet. Create the first one").
3. **Error** — clear message + retry option. Never leave the UI hanging.
4. **Populated** — the happy path.

---

## 7. Accessibility (non-negotiable minimums)

- AA contrast (4.5:1 normal text, 3:1 large text and control borders).
- **Visible focus** on every interactive element (do not remove the outline without a replacement).
- Keyboard navigable (logical tab order, Esc closes overlays).
- `alt` on informative images; icon buttons with `aria-label`.
- Do not communicate state with color alone (add an icon or text).
- Touch targets ≥ `44×44px`.

---

## 8. Motion

- Subtle transitions (`150–200ms`, `ease-out`) for hover/appearance. No bounces.
- Respect `prefers-reduced-motion`.
- No animations that block interaction.

---

## 9. What NOT to do (typical AI-generated UI mistakes)

- Multicolor gradients, exaggerated shadows, "glassmorphism" without being requested.
- Emojis as UI icons in production.
- Mixing 3 border radii and 4 shades of gray.
- Light gray text on white (fails contrast).
- Buttons that only say "Submit" / "OK" without context.
- Forms without error or loading states.
- Inventing a second typeface or brand color on your own.

---

## Project Override

Everything above is the baseline. The project declares its differences here (filled in during
`/setup` or by hand). If this section contradicts the baseline, **this section wins**.

- **Visual direction / vibe:** `Not applicable — API-only deliverable; no UI in scope`
- **Brand primary color:** `N/A`
- **Typography:** `N/A`
- **Styling framework:** `N/A`
- **Component library (if applicable):** `N/A`
- **Dark mode:** `N/A`
- **Project-specific notes:** `If a UI is added later, the baseline applies and this override must be revisited before any UI work.`

> If the project uses a component library, respect it instead of reinventing controls, while
> keeping the principles in sections 0, 4, 6, and 7.
