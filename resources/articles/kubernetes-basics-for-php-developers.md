---
name: "Kubernetes Basics for PHP Developers"
slug: kubernetes-basics-for-php-developers
short_description: "The handful of Kubernetes objects a Laravel app actually needs, how php-fpm, queues, and the scheduler map onto them, and the migration trap."
language: en
published_at: 2027-05-03 09:00:00
is_published: true
tags: [devops, laravel, php, kubernetes]
---

The first time I moved a Laravel app to Kubernetes, migrations ran four times. Not because the code was wrong, but because I had scaled the web Deployment to four replicas and stuck `php artisan migrate` in the container startup command. Four pods, four boots, four races against the same database. One of them dropped a column another was still reading. That afternoon taught me more about Kubernetes than any tutorial had.

This is the mental model I wish someone had handed me: the small set of objects a PHP developer actually touches, how the moving parts of a Laravel app map onto them, and the two or three things that will bite you in production if you skip them. No cluster administration, no service mesh, no CRDs. Just the parts you need to run your app.

## First, be honest: you probably don't need this yet

Kubernetes solves a specific problem — running many containers across many machines, keeping the right number alive, and rescheduling them when a node dies. If you're running one Laravel app on one server, a single VPS with `nginx`, `php-fpm`, and a Supervisor-managed queue worker will serve real traffic for years. Laravel Forge or a plain Docker Compose file will take you astonishingly far.

You start earning Kubernetes when you have several services that need to scale independently, a team that deploys often enough to want zero-downtime rollouts for free, or a traffic pattern where you genuinely need to add and remove capacity on a schedule. If none of that is true, the operational cost — YAML sprawl, a control plane to babysit, a whole new failure surface — outweighs the benefit. I've watched two-person teams spend more time on their cluster than on their product.

Read the rest so you understand it. Adopt it when the pain it removes is bigger than the pain it adds.

## The five objects you'll actually use

Kubernetes has dozens of resource types. For a web app you can get productive with five.

A **Pod** is one or more containers that share a network address and lifecycle. It's the smallest deployable unit, but you almost never create one directly — pods are cattle, not pets, and they get replaced constantly.

A **Deployment** manages a set of identical pods. You tell it "I want 3 replicas of this image" and it keeps 3 running, replacing any that crash. When you push a new image, it rolls pods over gradually. This is where your app lives.

A **Service** gives a stable internal DNS name and virtual IP to a set of pods. Pods come and go with changing IPs; the Service is the fixed address other things talk to. Think of it as an internal load balancer.

A **ConfigMap** holds non-secret configuration as key/value pairs. A **Secret** does the same for sensitive values — API keys, database passwords. Both get injected into pods as environment variables or mounted files.

An **Ingress** routes outside HTTP traffic to Services based on hostname and path. It's how `yourdomain.com` reaches the right pod. You need an ingress controller (nginx-ingress, Traefik) installed in the cluster for it to do anything.

That's the whole vocabulary for a first deployment. Everything below is built from these.

## How a Laravel app maps onto them

A Laravel app is not one process. It's a web tier serving HTTP, background workers draining a queue, and a scheduler firing periodic tasks. On a single server, Supervisor and cron paper over the differences. On Kubernetes you make the split explicit, and honestly it's clearer this way.

### The web tier

Laravel needs `php-fpm` to run PHP and `nginx` to serve static files and proxy `.php` requests to FPM. You have two reasonable shapes.

**One pod, two containers.** Put `nginx` and `php-fpm` in the same pod so they share `localhost` and a volume for the built app. They scale together and always sit on the same node. This is the common choice and the one I reach for first.

**A single image running FrankenPHP or a PHP built-in worker.** Tools like FrankenPHP or RoadRunner collapse the web server and PHP runtime into one process. Fewer moving parts, one container per pod. Great if you're already using them; not a reason to switch mid-migration.

Here's a two-container web Deployment plus its Service:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: web
spec:
  replicas: 3
  selector:
    matchLabels: { app: web }
  template:
    metadata:
      labels: { app: web }
    spec:
      containers:
        - name: nginx
          image: registry.example.com/myapp-nginx:1.4.2
          ports:
            - containerPort: 80
        - name: php-fpm
          image: registry.example.com/myapp-php:1.4.2
          envFrom:
            - configMapRef: { name: app-config }
            - secretRef: { name: app-secrets }
          # probes go here — see below
---
apiVersion: v1
kind: Service
metadata:
  name: web
spec:
  selector: { app: web }
  ports:
    - port: 80
      targetPort: 80
```

The `selector` on the Service matches the `labels` on the pod template. That label match is the entire wiring — get it wrong and the Service points at nothing, returning connection refused with no obvious error.

### Queue workers are a separate Deployment

Queue workers don't serve HTTP and shouldn't share the web tier's scaling. Give them their own Deployment running `php artisan queue:work`:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: queue-worker
spec:
  replicas: 2
  selector:
    matchLabels: { app: queue-worker }
  template:
    metadata:
      labels: { app: queue-worker }
    spec:
      containers:
        - name: worker
          image: registry.example.com/myapp-php:1.4.2
          command: ["php", "artisan", "queue:work", "--max-time=3600", "--tries=3"]
          envFrom:
            - configMapRef: { name: app-config }
            - secretRef: { name: app-secrets }
```

Two details matter here. Use `queue:work`, not `queue:listen` — `work` keeps the framework booted between jobs and is far faster. And set `--max-time=3600` so the worker exits cleanly every hour; Kubernetes restarts it, which sidesteps the classic problem of a long-lived PHP process leaking memory. You want workers that die on purpose.

Because it's a separate Deployment, you scale workers by changing one number without touching web capacity. That separation is the actual payoff.

### The scheduler is a CronJob, and only one of them

Laravel's scheduler is a single `php artisan schedule:run` that must fire once a minute. On one server that's a cron line. On Kubernetes the trap is obvious once you say it out loud: if you put `schedule:run` in the web Deployment, every replica runs it, and your "daily report" email goes out three times.

Run it as a Kubernetes CronJob instead — exactly one invocation per minute, cluster-wide:

```yaml
apiVersion: batch/v1
kind: CronJob
metadata:
  name: scheduler
spec:
  schedule: "* * * * *"
  concurrencyPolicy: Forbid
  jobTemplate:
    spec:
      template:
        spec:
          restartPolicy: Never
          containers:
            - name: scheduler
              image: registry.example.com/myapp-php:1.4.2
              command: ["php", "artisan", "schedule:run"]
              envFrom:
                - configMapRef: { name: app-config }
                - secretRef: { name: app-secrets }
```

`concurrencyPolicy: Forbid` stops a new run from starting if the previous one is still going — useful when a scheduled task occasionally runs long. Laravel already has `withoutOverlapping()` for individual tasks; this is the cluster-level equivalent.

## Environment and secrets

Laravel reads config from environment variables, which is exactly what Kubernetes is good at injecting. Put non-secret settings in a ConfigMap:

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: app-config
data:
  APP_ENV: production
  APP_URL: https://myapp.example.com
  QUEUE_CONNECTION: redis
  CACHE_STORE: redis
  LOG_CHANNEL: stderr
```

`LOG_CHANNEL: stderr` matters more than it looks. In a cluster you don't `tail` a log file — pods are ephemeral and their filesystem vanishes on restart. Write logs to stdout/stderr and let the cluster's logging stack collect them. Laravel ships a `stderr` channel for exactly this.

Secrets go in a Secret. In real setups you don't commit these; you generate them from a secrets manager or a tool like Sealed Secrets. The manifest shape is:

```yaml
apiVersion: v1
kind: Secret
metadata:
  name: app-secrets
type: Opaque
stringData:
  APP_KEY: base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
  DB_PASSWORD: super-secret
```

One thing that trips people up: a Secret in a plain cluster is only base64-encoded, not encrypted at rest unless you've turned on encryption. Base64 is not a security boundary. Treat access to the namespace as access to the secrets, and enable encryption at rest if you're storing anything real.

## Probes, and why they need graceful shutdown

Probes are how Kubernetes decides whether your pod is alive and whether it's ready for traffic. Get them wrong and you either serve requests to a booting pod or kill a healthy one mid-request.

A **liveness probe** answers "is this process wedged?" If it fails repeatedly, Kubernetes restarts the pod. A **readiness probe** answers "should this pod receive traffic right now?" If it fails, the pod is pulled out of the Service's rotation but not killed. During a rolling deploy, readiness is what gives you zero downtime — new pods take traffic only once they report ready.

Add a lightweight health route to Laravel. Laravel 11 ships one at `/up` out of the box:

```yaml
          livenessProbe:
            httpGet: { path: /up, port: 80 }
            initialDelaySeconds: 10
            periodSeconds: 15
          readinessProbe:
            httpGet: { path: /up, port: 80 }
            initialDelaySeconds: 5
            periodSeconds: 5
```

The subtle part is shutdown. When Kubernetes removes a pod it sends `SIGTERM`, waits for the pod's `terminationGracePeriodSeconds` (default 30), then sends `SIGKILL`. For the web tier, `php-fpm` needs to finish in-flight requests before exiting. For queue workers, `queue:work` catches `SIGTERM` and stops after the current job rather than mid-job — Laravel supports this, but only if the signal actually reaches the process, so make sure your container's entrypoint uses exec form (`command: ["php", ...]`) rather than a shell wrapper that swallows the signal.

Readiness and graceful shutdown attack the same problem from opposite ends: readiness stops new traffic reaching a pod that's on its way out, graceful shutdown lets the work already in flight finish. Skip either one and your rollouts quietly drop requests — the kind of bug that shows up as a handful of 502s during every deploy and gets shrugged off as "the network."

## The migration trap

Back to the story I opened with. Where do migrations run? The tempting answer is "in the container startup, before the app boots." That's the wrong answer, because every replica boots and every replica migrates.

Run migrations as a one-shot Kubernetes Job, or as an init container that runs before the app containers. A Job runs to completion exactly once per invocation:

```yaml
apiVersion: batch/v1
kind: Job
metadata:
  name: migrate-1.4.2
spec:
  backoffLimit: 3
  template:
    spec:
      restartPolicy: Never
      containers:
        - name: migrate
          image: registry.example.com/myapp-php:1.4.2
          command: ["php", "artisan", "migrate", "--force"]
          envFrom:
            - configMapRef: { name: app-config }
            - secretRef: { name: app-secrets }
```

`--force` is required because `migrate` refuses to run in production without it. Name the Job after the image tag so each release gets its own Job and you can see which migration ran for which version.

The workflow is: apply the migration Job, wait for it to complete, then roll out the new Deployment. Most CI pipelines chain these — `kubectl apply` the Job, `kubectl wait --for=condition=complete`, then update the Deployment image. Init containers are the alternative, but they run once per pod, so you're back to a race unless the migration is genuinely idempotent. A single Job is the cleaner mental model: one release, one migration, one place it ran.

There's a deeper discipline underneath this that Kubernetes only makes louder: your migrations should be backward-compatible with the currently-running code. During a rolling deploy, old and new pods run side by side against the same schema. Drop a column the old code still selects and you get errors until the rollout finishes. Add columns in one release, remove old ones in a later release once no running code references them. This is good practice everywhere; a cluster just punishes you faster for ignoring it.

## Scaling horizontally

The whole reason you tolerate this machinery is scaling. Manually, it's one command:

```bash
kubectl scale deployment/web --replicas=6
```

Automatically, a HorizontalPodAutoscaler watches a metric — usually CPU — and adjusts replica count between bounds:

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: web
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: web
  minReplicas: 3
  maxReplicas: 12
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 70
```

For a PHP app the thing that makes horizontal scaling actually work is statelessness. Sessions, cache, and the queue must live outside the pod — Redis or the database, never local files. If a user's session is on the pod's filesystem, adding replicas logs half your users out at random because the load balancer sends them to a pod that's never seen them. Set `SESSION_DRIVER=redis` and `CACHE_STORE=redis` and the web tier becomes truly disposable, which is the property every autoscaling decision depends on.

Autoscaling on CPU is a fine default and a bad universal answer. A queue worker's real signal is queue depth, not CPU — a worker waiting on an external API is idle by CPU but very much needed. Scaling workers on queue length usually means a custom metric or a tool like KEDA, which is worth reaching for once your queues matter.

## FAQ

**Do I need separate Docker images for web, workers, and the scheduler?**
No — one PHP image is enough. The queue worker, scheduler, and migration Job all run the same application code; they only differ in the command Kubernetes starts. The web tier adds an `nginx` container, but that's a separate lightweight image, not a separate copy of your app. Fewer images means one build to keep in sync.

**Where should I run `php artisan config:cache` and `route:cache`?**
Bake them into the Docker image at build time, not at container start. Caching config at boot means every pod does redundant work and, worse, a config cache built with the wrong environment can poison the pod. Build once in your Dockerfile, ship an immutable image.

**How do queue workers pick up new code after a deploy?**
They don't reload on their own — a running `queue:work` process holds the old code in memory. When you roll out a new image, Kubernetes replaces the worker pods, and the new pods run the new code. If you ever restart workers manually, `php artisan queue:restart` tells them to exit after the current job, but under Kubernetes the Deployment rollout already does this for you.

**Can I run the database inside Kubernetes too?**
You can, but for most teams a managed database (RDS, Cloud SQL) is the saner default. Stateful workloads need StatefulSets, persistent volumes, and backup strategy — a whole discipline beyond running stateless PHP. Keep the hard, stateful part managed until you have a strong reason not to.

## Where to go from here

Start smaller than a cluster. Get your app running as containers with Docker Compose first — web, worker, scheduler, all reading config from environment variables and logging to stdout. If it runs cleanly there, the move to Kubernetes is mostly translating that Compose file into the five objects above, plus probes and a migration Job.

The objects are simple. The discipline they demand — stateless pods, backward-compatible migrations, graceful shutdown, one place the migration runs — is the actual lesson, and it makes your app better even on the day you decide you didn't need Kubernetes after all.
