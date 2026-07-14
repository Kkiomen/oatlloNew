---
title: "Routing"
slug: routing
seo_title: "Laravel Routing Basics"
seo_description: "Define your first routes in Laravel and return a response."
---

## Your first route

Routes live in `routes/web.php`. A basic route returns a response for a given URL:

```php
use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return 'Hello, Laravel!';
});
```

Visit `/hello` in the browser and you'll see the response. In the next chapters
we'll connect routes to controllers and Eloquent models.
