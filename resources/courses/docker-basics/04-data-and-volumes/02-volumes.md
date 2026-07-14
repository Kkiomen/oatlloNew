---
title: "Volumes"
slug: volumes
seo_title: "Docker volumes: persist data across containers"
seo_description: "Learn how to create and use Docker volumes with docker volume and -v to keep data, like a database, safe even when containers are removed."
---

## What is a volume?

A **volume** is a storage area that Docker manages for you, living outside any single
container. You attach a volume to a container, and anything written there is saved in
the volume - not in the container. Remove the container, and the volume (and its data)
stays. Attach it to a new container, and the data is right there.

Volumes are the recommended way to store data that must survive, like databases.

## Create and use a Docker volume

You can create a volume explicitly:

```bash
docker volume create mydata
```

Then attach it to a container with `-v`, giving the volume name and the path inside
the container where it should appear:

```bash
docker run -it -v mydata:/root ubuntu bash
```

The `-v mydata:/root` part means "mount the `mydata` volume at `/root` inside the
container". Now anything written to `/root` goes into the volume. Try it:

```bash
echo "this will survive" > /root/notes.txt
exit
```

Start a brand-new container with the same volume:

```bash
docker run -it -v mydata:/root ubuntu bash
cat /root/notes.txt
```

This time the file is there. The data lived in the volume, so the new container sees
it.

## A real example: a database

This is exactly how you keep a database's data safe. For example, running MySQL and
storing its data in a volume:

```bash
docker run -d --name db \
  -e MYSQL_ROOT_PASSWORD=secret \
  -v db-data:/var/lib/mysql \
  mysql:8
```

MySQL stores its files in `/var/lib/mysql` inside the container, and we've pointed
that at the `db-data` volume. Now you can stop and remove the container, start a new
one with the same volume, and all your tables and rows are still there.

## Manage volumes with docker volume

List your volumes:

```bash
docker volume ls
```

Remove one you no longer need (this deletes its data, so be careful):

```bash
docker volume rm mydata
```

Volumes are perfect for data Docker should manage. But during development you often
want to share your own project folder with a container - that's what
[**bind mounts**](/course/docker-basics/data-and-volumes/bind-mounts) are for, coming up next.

## A safety habit: named volumes over anonymous ones

When you attach a volume, give it a **name** (`-v db-data:/var/lib/mysql`) rather than
letting Docker create an anonymous one. Anonymous volumes get random IDs, are easy to
lose track of, and pile up as junk you can't identify later. A named volume is easy to
find with `docker volume ls`, easy to reuse, and easy to back up. Future-you will thank
present-you.

## FAQ

### What is the difference between a volume and a bind mount?

A **volume** is storage Docker manages internally - you refer to it by name and don't
pick a folder on your disk. A **bind mount** points at a specific folder on your
computer. Volumes suit persistent app data; bind mounts suit sharing source code in
development (next lesson).

### Where does Docker store volume data?

In an area Docker manages on the host (you don't normally browse it directly). That's
the point - you interact with the volume by name via `docker volume`, and Docker handles
where the bytes live.

### Does removing a container delete its volume?

No. That's the whole benefit - the volume and its data outlive the container. A volume
is only deleted when you remove it explicitly with `docker volume rm`.
