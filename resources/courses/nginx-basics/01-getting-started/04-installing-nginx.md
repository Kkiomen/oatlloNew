---
title: "Installing nginx"
slug: installing-nginx
seo_title: "How to Install Nginx on Ubuntu (apt) and Verify It Runs"
seo_description: "Install nginx on Ubuntu or Debian with apt, plus Docker and WSL notes for Windows and Mac. Verify the install works in your browser."
keys_link:
  - install nginx on ubuntu
  - install nginx with apt
  - run nginx on windows wsl
  - nginx docker container
---

Time to install nginx on a real machine. It runs best on Linux, so we install nginx on Ubuntu here with `apt`, the standard package manager. On Windows or Mac you follow along through WSL or Docker - the note below covers both. Either way, you finish this lesson with nginx running and a browser confirming it.

## Install nginx on Ubuntu or Debian with apt

On Ubuntu or Debian Linux, nginx is in the standard package repositories. Two commands do it.

First, update the package list so you get current versions:

```bash
sudo apt update
```

Then install nginx:

```bash
sudo apt install nginx
```

`sudo` runs the command as an administrator, which installing software requires. `apt` is the package manager on Ubuntu and Debian. That is it - nginx is installed and, on most systems, already started. The apt package also sets nginx to start automatically on boot, so you will not have to launch it by hand after a reboot.

## Check the nginx version

Confirm it is there by asking for the version:

```bash
nginx -v
```

You should see something like `nginx version: nginx/1.24.0`. If you get "command not found", the install did not complete, so run the steps above again.

## Verify nginx runs in your browser

The best check is to open it. On the same machine, visit:

```text
http://localhost
```

`localhost` means "this computer". If nginx is running, you will see its **Welcome to nginx!** page. That page is the default site, and we will edit it in a later lesson.

If nothing loads, do not worry yet. The next lesson covers starting nginx and checking its status.

## How to run nginx on Windows (WSL) or Mac (Docker)

Nginx has a native Windows build, but it is limited and not how it is used in the real world. Instead:

- **Windows**: install **WSL** (Windows Subsystem for Linux) to get a real Ubuntu inside Windows, then follow the apt steps above.
- **Mac or Windows**: use **[Docker](/course/docker-basics)** to run nginx in a container. One command downloads and runs it:

```bash
docker run --name web -p 8080:80 nginx
```

This starts nginx and maps it to port 8080, so you open `http://localhost:8080` in your browser. Docker is a clean way to try nginx without installing it system-wide.

Ports come up again soon. Sometimes you want several sites on a single server, and a common way to keep them apart is to run each one on its own port - one site on 80, another on 8080, and so on. You just met that idea here: the container answers on 8080 instead of the usual 80. A later chapter shows [how nginx decides which site a request belongs to](/course/nginx-basics/serving-static-content/listen-and-server-name), so for now just notice that the port is part of the address.

Whichever route you pick, the config and commands in this course are the same, because underneath you are running nginx on Linux.

## Common mistake to avoid

Do not skip `sudo apt update` before installing. Without it, apt may try to install an old cached version or fail to find the package. Update first, then install.

## FAQ

### Do I need a server to learn nginx?

No. Your own laptop is enough. You run nginx locally and visit `http://localhost`, exactly as shown above. A real server is only needed when you want the public to reach your site.

### Nginx installed but localhost does not load. Why?

Most often nginx is not running, or something else is using the port. The next lesson, on [start, stop, and reload](/course/nginx-basics/getting-started/start-stop-reload), shows how to check its status and start it.

### Should I install nginx on Windows directly?

Prefer WSL or Docker. The native Windows build misses features and is rarely used in production, so what you learn there would not fully transfer.
