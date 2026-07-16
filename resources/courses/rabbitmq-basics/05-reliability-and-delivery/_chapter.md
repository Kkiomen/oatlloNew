---
title: "Reliability and delivery"
slug: reliability-and-delivery
description: "Make RabbitMQ reliable: manual acknowledgements, publisher confirms, TTL, retries with dead-letter queues, delivery guarantees, and delayed messages."
---

A message that arrives is not the same as a message that gets done. In this chapter we
close the gaps: how consumers confirm work with acknowledgements, how producers learn the
broker actually stored a message, how to expire and retry messages, and what "delivery
guarantee" really means once you accept that duplicates happen.
