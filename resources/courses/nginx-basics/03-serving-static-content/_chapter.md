---
title: "Serving static content"
slug: serving-static-content
description: "Serve HTML, CSS, images and files with nginx: server blocks, listen and server_name, root and index, a first location, MIME types, custom error pages and directory listings."
---

This chapter turns nginx into a working file server. You will build a server block, point it at a folder with `root` and `index`, host several domains from one nginx, add your first `location`, learn how MIME types set the Content-Type, serve custom 404 and 50x pages, and switch on a directory listing with `autoindex`.
