---
name: "Writing Effective Prompts for Code Generation"
slug: prompts-for-code-generation
short_description: "How to write prompts for code generation that produce usable code: give context, specify edge cases, ask for tests, and verify the output."
language: en
published_at: 2026-10-02 09:00:00
is_published: true
tags: [ai, prompting, productivity, code-generation]
---

I've been pairing with coding assistants every working day for a couple of years now, and the single biggest jump in output quality had nothing to do with which tool I picked. It was learning to write better prompts for code generation. Same model, same editor, wildly different results depending on how I framed the request. This post is the set of habits I actually use, not a theory of prompt engineering.

The short version: a vague ask gets you plausible-looking code that breaks on the second edge case. A precise ask gets you something you can review in two minutes and ship. Let me show you the difference.

## Why most prompts fail

The failure mode is almost always the same. You type something like "write a function to parse dates" and hit enter. The assistant has no idea what language you're in half the time, what format the dates arrive in, what timezone rules matter, or what should happen when the input is garbage. So it guesses. It picks the most statistically common answer, which is usually a naive parser that assumes ISO strings and throws on everything else.

That guess isn't the model being dumb. It's the model doing exactly what you asked, which was "not much."

Here's the same request, before and after.

```text
Bad prompt:
write a function to parse dates
```

```text
Good prompt:
Write a PHP 8.2 function that parses a user-supplied date string
into a DateTimeImmutable.

Context:
- Inputs come from a web form, so expect messy data.
- Accepted formats: "Y-m-d", "d/m/Y", and "d.m.Y".
- Timezone should be Europe/Warsaw.

Requirements:
- Return DateTimeImmutable on success.
- Throw InvalidArgumentException with a clear message on failure.
- Do NOT silently coerce ambiguous input.

Show me one example call and the exception path.
```

The second one produces code I can drop in. The first one produces a conversation.

## Give context, always

If I could teach only one habit, it's this. Models don't know your stack unless you tell them, and they won't ask often enough. So front-load the boring facts:

- **Language and version**: "Python 3.12", "TypeScript 5.4", "PHP 8.3". Version matters more than people expect; match expressions, enums, and readonly properties didn't always exist.
- **Framework and its version**: Laravel 11 routes look nothing like Laravel 8. React with hooks is a different world from class components.
- **Constraints you actually have**: "no new dependencies", "must run on PHP-FPM", "this is a hot path, avoid allocations in the loop".
- **The surrounding code style**: paste a nearby function so the output matches your conventions instead of introducing a fourth way of naming things.

I keep a little mental template: what language, what framework, what am I not allowed to do. Thirty seconds of typing saves a round trip.

One thing I learned the hard way: if your project has a house style (say, you always return DTOs instead of arrays, or you use a specific Result type), the assistant will not infer it from thin air. Show it once and it usually holds the pattern for the rest of the session.

## Specify inputs, outputs, and edge cases

Vague specs are where hallucinated behavior sneaks in. Nail down three things:

1. What goes in (types, shapes, ranges, "can this be null?").
2. What comes out (a value? a thrown exception? a Result object?).
3. What happens at the boundaries.

That third one is where the value is. Empty input, a list of one, a list of ten thousand, a negative number, a duplicate key, a network timeout. If you don't name the edge case, the generated code won't handle it, and you'll find out in production instead of in review.

I'll often literally write "Edge cases to handle:" as a bullet list in the prompt. It reads like a mini spec, and honestly it helps me think too. Half the time I discover an edge case I hadn't decided on yet.

## Provide an example of the style you want

Assistants are excellent mimics. If you give them one worked example, they'll extend the pattern far more reliably than if you describe it in prose.

```text
Here's how we write repository methods in this project:

public function findActiveByEmail(string $email): ?User
{
    return $this->model
        ->where('email', $email)
        ->where('is_active', true)
        ->first();
}

Now write findActiveById(int $id) in the same style.
```

That's a trivial example, but the principle scales. Paste a test you like, and ask for more tests in that shape. Paste a controller, and ask for a sibling that follows the same structure. Showing beats telling almost every time.

## Ask for tests, on purpose

I request tests in the same prompt as the feature, not afterward. Two reasons. First, it forces the model to commit to a concrete understanding of the behavior, which surfaces disagreements early. If the generated test asserts something I didn't mean, I caught a misunderstanding for free. Second, the tests document the edge cases I asked for, so future me knows they were intentional.

If you're working in PHP and want your code to be pleasant to test in the first place, structuring it well up front matters — I've got a separate write-up on [writing testable PHP code](/blog/testable-php-code) that pairs nicely with this workflow.

A prompt I use constantly:

```text
Add PHPUnit tests for the parser above.
Cover: valid input for each accepted format, an empty string,
a malformed string, and a date in an unsupported format.
Use data providers where it reduces duplication.
```

## Iterate in small steps

The temptation with a capable assistant is to ask for the whole feature at once. "Build me the checkout flow." Resist it. Big asks produce big blobs that are hard to review and harder to trust.

I work in slices: get the data model right, then one service method, then its tests, then wire it to a controller. Each step is small enough that I can actually read the output and catch a wrong turn before it compounds. When I skip this and ask for everything, I usually spend more time untangling the result than I saved.

Small steps also keep the context focused. A conversation that's drifted through six unrelated tasks starts producing muddier answers. When that happens I just start fresh and paste in only what's relevant.

## Ask it to explain itself

When the code does something I don't immediately understand — a regex, a bitwise trick, an unfamiliar API call — I ask "why did you do it this way?" before I accept it. Two outcomes, both useful. Either I learn something legitimate, or the explanation is hand-wavy and I've found a spot to distrust.

This is also how I catch the confident-but-wrong answers. A model that invented an API will often produce a beautifully reasoned justification for a method that doesn't exist. Reading the explanation with a skeptical eye is cheap insurance.

## Review and verify — never trust blindly

This is the part I refuse to compromise on. Generated code is a draft, not a delivery.

The failure I see most is **hallucinated APIs**. The assistant confidently calls a helper, a config flag, or a library method that simply isn't real, or that existed in a different version. It looks completely legitimate. The fix is boring but non-negotiable: check the method against the actual docs or your IDE's autocomplete before you believe it. If your editor can't resolve it, it probably doesn't exist.

My actual review checklist, roughly in order:

- Does it compile and do the types line up?
- Are the APIs and library calls real? (This is where I catch the most bugs.)
- Does it handle the edge cases I asked for, and the ones I forgot to?
- Is there a security problem: unescaped input, a query built by string concatenation, a secret in the code, a missing authorization check?
- Do the tests actually assert meaningful things, or are they asserting `true === true`?

That security pass deserves its own line. Assistants happily generate SQL string interpolation, skip CSRF checks, log sensitive data, or trust user input that should be validated. They're pattern-matching on the millions of insecure examples on the internet as much as the secure ones. I read every DB query and every place user input crosses a boundary with the assumption that it's wrong until I've confirmed otherwise.

## Common pitfalls

- **Asking for too much at once.** Big prompts, big blobs, big review burden. Slice it.
- **Assuming the model knows your versions.** It doesn't. State them.
- **Accepting code you can't explain.** If you can't defend it in review, don't merge it.
- **Trusting unfamiliar API calls.** Verify against real docs; hallucinated methods look identical to real ones.
- **Skipping the security read.** Generated code is not security-reviewed by default. You are the reviewer.
- **Letting one chat run forever.** Stale context degrades answers. Start fresh when the thread wanders.
- **Forgetting to ask for tests.** They cost one extra sentence and catch misunderstandings immediately.

## FAQ

### How do I write prompts for code generation that actually work?

Give the model context (language, framework, versions, constraints), specify the exact inputs and outputs, name the edge cases explicitly, show an example of the style you want, and ask for tests in the same request. Then review the result. Never merge it unread.

### Why do AI assistants invent functions and APIs that don't exist?

They generate the most statistically likely continuation of your prompt, and a plausible-sounding method name is often more likely than admitting uncertainty. This is why hallucinated APIs look so convincing. Always confirm a call against real documentation or your IDE before trusting it.

### Should I trust code an AI assistant generates?

Treat it as a first draft from a fast but unreliable junior. Read it, verify the APIs are real, check the edge cases, and do a security pass on anything touching user input or the database. The productivity gain comes from writing drafts faster, not from skipping review.

### Does the specific tool matter — Claude, Copilot, Cursor, something else?

Less than you'd think. The habits in this post apply to any assistant. Different models have different strengths, and there are several Claude models aimed at coding, but a good prompt outperforms a better model with a lazy prompt almost every time. Fix your prompting before you shop for tools.

## Wrapping up

Better prompts for code generation aren't a trick or a magic phrase. They're just clear communication: say what you're building, in what stack, with what constraints, and what "done" looks like — then verify what comes back. If you're calling an LLM from your own app rather than an editor, the same discipline applies to your system prompts; I keep a practical guide on wiring up the [Claude API in PHP](/blog/claude-api-php) for that.

Start with one change this week. Before you send your next request, add three lines: the language and version, the edge cases, and "include tests." That alone will change what you get back. The review discipline is what keeps you safe; the context is what makes the code good.