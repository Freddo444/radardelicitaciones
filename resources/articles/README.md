# Blog articles

Each `.md` file in this folder becomes a published article at `/blog/{slug}`.

## Format

Every article starts with a YAML front matter block, then the body in Markdown:

```markdown
---
title: "Your article title"
description: "Meta description for SEO (~150 chars). Shows in Google results."
author: Frederick López
published_at: 2026-05-28
updated_at: 2026-05-28
slug: my-explicit-slug   # optional — defaults to filename without .md
excerpt: "Short summary for blog index cards (~200 chars)"
tags: [DGCP, Construcción, Pliegos]
draft: false             # set true to hide from /blog
cover: /images/blog/x.jpg # optional OG image
---

# Markdown body here

Use **bold**, *italic*, [links](https://example.com), bullet lists, tables, code, blockquotes — full GitHub-Flavored Markdown is supported.
```

## Workflow

1. Create a new file: `resources/articles/my-slug.md`
2. Add front matter + body
3. Set `draft: true` while drafting, flip to `false` when ready to publish
4. `git commit && git push` — deploy picks it up automatically
5. The new article appears on `/blog`, has its own page at `/blog/my-slug`, gets indexed in the sitemap, and shows in the RSS feed.

## Tips

- Keep titles under 60 chars (Google truncates after that in search results)
- Descriptions: 140-160 chars sweet spot
- Use `## H2` subheadings — they help readers scan and help Google understand structure
- First-person experience > generic explainer. The whole point is content competitors can't copy.
