---
title: "Start, stop, and reload nginx"
slug: start-stop-reload
seo_title: "Start, Stop, Reload Nginx: systemctl and nginx -s Commands"
seo_description: "Start, stop, and reload nginx with systemctl and nginx -s, check status, and learn the difference between reload and restart without downtime."
keys_link:
  - start stop reload nginx
  - nginx reload vs restart
  - systemctl nginx commands
  - check nginx status
---

Nginx is installed, so now you need to drive it: start it, stop it, and push new settings in. There are two ways to start and stop nginx, and knowing when to reach for each one saves a lot of head-scratching later. This lesson covers `systemctl` first, then nginx's own `-s` commands.

## Control nginx with systemctl

On modern Linux, `systemctl` manages background services, and nginx is one of them. These are the commands you will use most:

```bash
sudo systemctl start nginx
sudo systemctl stop nginx
sudo systemctl restart nginx
sudo systemctl reload nginx
sudo systemctl status nginx
```

Each does what it says:

- **start**: launches nginx if it is not running.
- **stop**: shuts it down completely.
- **restart**: stops and starts again (drops current connections).
- **reload**: applies new config without dropping connections.
- **status**: shows whether it is running.

## What is the difference between nginx reload and restart?

This is the one to get right. When you change nginx's configuration later in the course, you want it to pick up the change without knocking anyone off the site.

- **reload** tells nginx to re-read its config gracefully. Existing visitors are not cut off. Use this for config changes.
- **restart** fully stops and starts nginx. It works too, but it briefly drops connections.

The habit to build: **reload for config changes, restart only when you truly need a full stop and start.** There is a second reason to prefer reload that pays off later: if your new config has a mistake, reload keeps the old working config running instead of leaving nginx dead. A restart with a broken config can stop nginx and refuse to come back up.

## Check that nginx is running

Before anything else, confirm the status:

```bash
sudo systemctl status nginx
```

Look for the word **active (running)** in green. Press `q` to exit that screen. If it says `inactive` or `failed`, start it and check again.

You can also see the process directly:

```bash
ps aux | grep nginx
```

You will see a `master` process and one or more `worker` processes. That worker split is the event-driven model from [nginx vs Apache](/course/nginx-basics/getting-started/nginx-vs-apache).

## Control nginx with nginx -s signals

Nginx also has its own control commands using the `-s` (signal) flag:

```bash
sudo nginx -s reload
sudo nginx -s stop
sudo nginx -s quit
```

- `reload` re-reads the config, same idea as systemctl reload.
- `stop` shuts down fast, right away.
- `quit` shuts down gracefully, letting current requests finish first.

You will meet these inside Docker containers and setups without systemctl. On a normal Linux server, prefer `systemctl`; it is the standard.

## Common mistake to avoid

Changing the config and forgetting to reload. Nginx does not watch its files. Your edit does nothing until you run `reload`. If a change "did not work", reloading is the first thing to check. (We will also learn to [test config *before* reloading](/course/nginx-basics/configuration-basics/testing-config-safely) in a later chapter.)

## FAQ

### What is the difference between reload and restart?

Reload re-reads the config without dropping connections. Restart stops nginx and starts it again, briefly cutting off active requests. Use reload for config changes.

### Do I need sudo for these commands?

Usually yes. Nginx runs as a system service and listens on protected ports, so controlling it needs administrator rights. That is what `sudo` provides.

### Nginx will not start. What now?

Run `sudo systemctl status nginx` to see the error. A common cause is another program already using the port, or a typo in the config. Checking status points you at the reason.
