# Collection

A drop-in for WordPress sites and themes to make managing collections of data and entities easier. Accessing top-level collections in WordPress are easy, but subsets of those can be more difficult. _Collection_ fixes that.

## What is a _Collection_?

A _Collection_ is... well, pretty much anything: IDs, colors, keys, text snippets, images... anything in WordPress. I frequently needed to access a subset of posts, pages, products, events, etc., and creating a _Collection_ keeps those subsets readily available. Think of it as a fancy array of data.

## Features
- registration via `register_collection()` or `Collection::register()`
- easily accessible using `get_collection()` or `Collection::get()`
- implements caching using WordPress cache (within prequest and via transients)
- set lifetime of items easily
- items are directly accessible as an array, iterable, and countable
- searchable
- plenty of actions and filters
- access log (track use)
- duplication detection with `WP_DEBUG`