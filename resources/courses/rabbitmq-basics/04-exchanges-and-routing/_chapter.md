---
title: "Exchanges and routing"
slug: exchanges-and-routing
description: "How RabbitMQ decides where a message goes: the default, direct, fanout, topic and headers exchanges, plus dead-letter routing."
---

So far every message you published landed in one queue. Real systems need to send the
same message to many places, or pick a destination from the message itself. That job
belongs to **exchanges**. This chapter walks through each exchange type, from the
nameless default you've already been using to topic patterns and dead-letter routing.
