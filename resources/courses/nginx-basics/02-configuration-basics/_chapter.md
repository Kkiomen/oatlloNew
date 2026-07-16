---
title: "Configuration basics"
slug: configuration-basics
description: "How nginx config files are structured, how directives and contexts work, and how to test config safely and read the logs."
---

This chapter opens up the nginx configuration files. You will learn where `nginx.conf` lives and how it is laid out, what directives and contexts are, how `include` splits config into `conf.d/` and `sites-available`, how to test a config with `nginx -t` before every reload, and how to read the access and error logs when something breaks.
