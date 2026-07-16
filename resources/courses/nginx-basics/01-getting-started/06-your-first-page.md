---
title: "Your first page"
slug: your-first-page
seo_title: "Edit the Default Nginx Page in /var/www/html"
seo_description: "Edit the default nginx page: find the web root in /var/www/html, change index.html, and see your first nginx page live in the browser."
keys_link:
  - edit default nginx page
  - nginx web root var www html
  - change nginx index html
  - where nginx stores web files
---

Nginx is installed and running, so let's make it serve *your* content. To edit the default nginx page you only need to find one file in `/var/www/html`, change it, and refresh. That folder is the web root, and by the end of this lesson you will have replaced the welcome screen with a page you wrote. This is the moment nginx stops being abstract.

## Where the default nginx page lives on disk

When you opened `http://localhost` after installing, you saw the **Welcome to nginx!** page. That page is a real file sitting on your machine. Nginx read it from disk and sent it to your browser, exactly like the request cycle in [how a web request works](/course/nginx-basics/getting-started/how-a-web-request-works).

On Ubuntu and Debian, that file is here:

```text
/var/www/html/index.html
```

The folder `/var/www/html` is the default place nginx looks for web files. This folder is called the *web root*. The file `index.html` is the default page served when you visit the site with no specific file named.

## Look inside the web root folder

List what is in there:

```bash
ls /var/www/html
```

You will see `index.nginx-debian.html` (the welcome page) or `index.html`, depending on your setup. That single file is the whole default site. If your welcome page is named `index.nginx-debian.html`, creating a plain `index.html` next to it takes over, because nginx prefers `index.html` when choosing which file to serve for the folder.

## Edit the default index.html

Open the file in a text editor. `nano` is beginner friendly:

```bash
sudo nano /var/www/html/index.html
```

We need `sudo` because that folder is owned by the system. Delete what is there and put in something simple:

```html
<!DOCTYPE html>
<html>
  <head>
    <title>My first nginx page</title>
  </head>
  <body>
    <h1>Hello from nginx!</h1>
    <p>I edited this page myself.</p>
  </body>
</html>
```

In nano, save with `Ctrl+O` then `Enter`, and exit with `Ctrl+X`.

## See your change live in the browser

Go back to your browser and refresh `http://localhost`. Your new page appears right away.

Notice something: you did **not** reload nginx. For static files, nginx reads the file fresh on every request, so editing the HTML is enough. Reloading is only needed when you change nginx's *configuration*, as we saw in [start, stop, and reload nginx](/course/nginx-basics/getting-started/start-stop-reload).

## Common mistake to avoid

Editing the file but refreshing the wrong address, or seeing an old copy from the browser cache. Make sure you are visiting `http://localhost` (or your Docker port like `http://localhost:8080`), and do a hard refresh with `Ctrl+Shift+R` if the page looks unchanged.

## FAQ

### Where does nginx keep its web files?

By default on Ubuntu and Debian, in `/var/www/html`. That folder is the web root, and `index.html` inside it is served when you visit the site without naming a file. You can change this location later with configuration.

### Why did my edit show up without reloading nginx?

Because the page is a static file. Nginx reads it from disk on each request, so a saved edit appears on the next refresh. Reload is only for changes to nginx's config files, not to your HTML.

### Can I add more pages?

Yes. Drop another file like `about.html` into `/var/www/html`, then visit `http://localhost/about.html`. How nginx picks files and folders for different addresses is what we build on in the next chapters.
