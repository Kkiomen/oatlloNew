---
slug: eloquent-n1-explainer
title: "The N+1 query that's killing your Laravel app"
topic: laravel
voice: narrator_en
source: eloquent-n1-query-problem
music: none
platforms: [tiktok, shorts, reels]
caption: |
  Your Laravel app fires 101 queries when it should fire 2. Here's the N+1
  problem and the one-line fix.

  Full breakdown on oatllo.com.
hashtags: [laravel, php, webdev, eloquent, database]
---

<!-- scene -->
type: title
narration: This one line of Laravel code fires a hundred database queries. You probably wrote it today.
text: "N+1 is killing your app"

<!-- scene -->
type: code-reveal
narration: >
  It looks innocent. Load the users, loop over them, print each post count.
  One query, right?
lang: php
highlight: [1]
code: |
  $users = User::all();

  foreach ($users as $user) {
      echo $user->posts->count();
  }

<!-- scene -->
type: statement
narration: Wrong. It fires one query for the users, then one more for every single user's posts.
text: "1 + 100 = 101 queries"

<!-- scene -->
type: bullets
narration: That is the N plus one problem. One query to get the list, N queries to get each relation.
items:
  - "1 query for the list"
  - "N queries for the relations"
  - "It scales with your data"

<!-- scene -->
type: code-reveal
narration: The fix is eager loading. Tell Eloquent to grab the posts up front, with the with method.
lang: php
highlight: [1]
code: |
  $users = User::with('posts')->get();

  foreach ($users as $user) {
      echo $user->posts->count();
  }

<!-- scene -->
type: callout
narration: Now it is two queries. Total. No matter how many users you have.
text: "101 to 2"

<!-- scene -->
type: outro
narration: Full breakdown, with how to catch this automatically, on oatllo dot com. Follow for more.
cta: "oatllo.com"
