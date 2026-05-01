# Current Router Specification

## Overview

The current `op-unit-router` implementation determines the endpoint to execute from the request URL.

At the class level, `Router.class.php` itself is very small.

The class does not contain the full routing algorithm directly.

Instead, it initializes and stores a route table that is calculated by:

- `asset/unit/router/include/CalcRoute2018.php`

Its role is to build a route result containing:

- `args`
- `end-point`

The router itself does not render the response. It decides what should run next.

## Responsibility Boundary

The Router unit is responsible for:

- resolving the endpoint
- resolving router arguments
- deciding what should execute next

The Router unit is not responsible for:

- executing the endpoint itself
- buffering output
- applying layout
- rendering the final HTML wrapper

At the class boundary, `Router.class.php` itself is responsible for:

- loading the current route calculation include
- storing the calculated route table in `$this->_route`
- exposing that stored state through `EndPoint()`, `Args()`, and `Table()`

The actual path calculation logic belongs to `CalcRoute2018.php`.

For the detailed As-Is behavior of the current route calculator, see:

- `calc-route-2018.md`

## Current Class Shape

The current `Router.class.php` is effectively a thin wrapper around the route calculation include.

Its public surface is very small:

- `__construct()`
- `EndPoint()`
- `Args()`
- `Table()`

The constructor runs:

- `include(__DIR__.'/include/CalcRoute2018.php')`

and stores the returned route table.

The getter methods then return values from that stored table.

So, in the current As-Is design:

- `Router.class.php` is a route-table holder
- `CalcRoute2018.php` is the actual route calculator

## Route Result

The router returns a route table with this structure:

```text
{
  "args": [],
  "end-point": "/full/path/to/endpoint"
}
```

## Request Source

The current implementation uses:

- `$_SERVER['REQUEST_URI']` for HTTP requests
- `$_SERVER['argv'][1]` for shell usage

It then converts the request into a full path under the application root.

## Asset Path Restriction

If the resolved path points into `asset/`, the router does not allow it as a normal endpoint path.

Instead, it rewrites the target to the `404` path.

This prevents direct endpoint resolution inside the application asset area.

## Current Pass-Through Behavior

If the resolved request path already exists as a file, the router checks its extension.

The current implementation treats the following extensions as pass-through candidates:

- `html`
- `css`
- `js`
- `txt`
- `png`
- `ico`

If the file exists and its extension matches one of those entries, the router returns that file itself as the endpoint.

In that case, the file is executed or handled directly by the framework flow instead of searching for a separate controller-style `index.php`.

## Index Resolution Behavior

If the request is not returned by the pass-through path, the router searches for an `index.php` endpoint.

It does this by walking upward from the requested path.

Example:

```text
/foo/bar/baz
```

The router searches in this kind of order:

```text
/foo/bar/baz/index.php
/foo/bar/index.php
/foo/index.php
```

When it moves up one level, the removed path segment is added to `args`.

As a result:

- the nearest existing `index.php` becomes the endpoint
- the remaining path parts become router arguments

## Directory and File Handling

The current behavior can be summarized like this:

- existing pass-through file -> that file becomes the endpoint
- non-pass-through path -> search nearest `index.php`
- `asset/` path -> redirect internally to `404`

## Relationship to the Current Framework Flow

After the router returns the endpoint:

1. the App unit receives the endpoint
2. the endpoint is executed through `OP()->Template()`
3. the output is captured
4. the framework decides whether layout should be applied

## Important Note

This document describes the **current implementation** of `op-unit-router`.

It does not claim that the current name, documentation, and implementation are perfectly aligned in terminology.

In particular, the historical term `HTML Pass-Through` is narrower than the current extension list handled by the router.

## [DOC-GAP] Pass-Through Extension Policy

The current pass-through extension list is hard-coded inside the route calculator.

That is a current implementation convenience, not the ideal long-term policy boundary.

The target extension policy should belong to configuration rather than being fixed inside `CalcRoute2018.php`.

## Small-Class Character

An important implementation characteristic is that `Router.class.php` intentionally stays small.

It does not try to absorb:

- path normalization logic
- pass-through detection logic
- index search logic
- argument extraction logic

Those behaviors currently live in the included route calculator file.

This means the current Router class should be understood more as:

- the holder of routing state
- the public access point for route results

than as a large self-contained routing engine class.
