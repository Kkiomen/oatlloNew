---
title: "Nginx and PHP"
slug: nginx-and-php
description: "How nginx serves PHP through PHP-FPM over FastCGI, from a plain PHP page to a full Laravel server block."
---

Nginx cannot run PHP by itself. In this chapter you'll learn how it hands PHP requests to PHP-FPM over FastCGI, then build a working PHP site and the canonical Laravel server block, line by line.
