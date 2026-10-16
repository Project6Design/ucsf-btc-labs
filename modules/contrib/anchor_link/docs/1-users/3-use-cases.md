# Common Use Cases

Worked examples of anchors solving real editorial problems.

## Table of contents on a long page

A policy or handbook page with several sections benefits from a jump list at the
top.

**Set up the targets.** Place an anchor on each section heading:

- `introduction` on "Introduction"
- `eligibility` on "Who is eligible"
- `how-to-apply` on "How to apply"
- `contact` on "Contact us"

**Build the list.** At the top of the page, add a bulleted list where each item
links to one anchor:

```html
<ul>
  <li><a href="#introduction">Introduction</a></li>
  <li><a href="#eligibility">Who is eligible</a></li>
  <li><a href="#how-to-apply">How to apply</a></li>
  <li><a href="#contact">Contact us</a></li>
</ul>
```

Add a "Back to top" link at the end of each section, pointing at an anchor
placed at the very top of the body.

## FAQ page with deep links

On an FAQ page, each question gets an anchor named after it:
`faq-shipping-times`, `faq-returns`, `faq-warranty`.

Support staff can then send a customer straight to one answer:

```
https://example.com/faq#faq-returns
```

This is the main payoff of stable anchor names. Because the URL gets pasted into
emails and tickets, renaming `faq-returns` later breaks links you cannot see.

## Cross-referencing between pages

A "Terms of Service" page links to a specific clause of the "Privacy Policy":

```html
<a href="/privacy-policy#data-retention">data retention policy</a>
```

Anchor the clause on the privacy page first, then link to it. Keep a note of
which pages link into which anchors, so a future edit does not silently break
the reference.

## Footnotes and references

Use paired anchors to move the reader down to a note and back up again.

In the body:

```html
Recent figures show a sharp increase.<a href="#note-1" id="ref-1">[1]</a>
```

In the notes section:

```html
<p id="note-1">[1] Source: annual report, 2025. <a href="#ref-1">Back</a></p>
```

Note that the citation marker is both a link (`href`) and an anchor (`id`) on
the same element. That combination is supported.

## Skip links for accessibility

An anchor at the start of the main content gives keyboard users a way past the
navigation:

```html
<a id="main-content"></a>
```

Most themes provide a skip link already. Where the theme does not, or where a
long block of promotional content sits above the article, an empty anchor plus a
skip link fills the gap. Because the anchor is empty it renders nothing.

## Multi-step instructions

For a guide with numbered steps, anchor each one (`step-1`, `step-2`, ...) so
that:

- a prerequisite can point forward ("complete [step 3](#step-3) first");
- a troubleshooting section can point back to the step that failed;
- a support reply can link to the exact step the user is stuck on.

## Landing pages driven by campaign URLs

A long marketing page can serve several campaigns by sending each audience to a
different section:

```
/product#for-teams
/product#for-enterprise
/product#pricing
```

One page, several entry points, no duplicate content.

## What anchors are not for

- **Navigation between distinct topics.** If a section is substantial enough to
  deserve its own URL, make it its own page.
- **Hiding content.** An empty anchor renders nothing, but the content it sits
  next to is still fully public and indexable.
- **Layout.** Anchors are jump targets, not spacers.

## Next steps

- [Linking to anchors](2-linking-to-anchors.md)
- [FAQ](../faq.md)
