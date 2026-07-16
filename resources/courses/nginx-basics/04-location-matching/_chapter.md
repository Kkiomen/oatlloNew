---
title: "Location matching"
slug: location-matching
description: "How nginx picks which location block handles a request, plus try_files, redirects, and rewrites."
---

A single request can match several `location` blocks. This chapter shows the exact rules nginx uses to pick one, and the tools you use inside a block to check files, redirect paths, and rewrite URLs.
