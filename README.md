## Getting Started

This project is written entirely in PHP and uses composer for dependency management.

### Installation

#### Install dependencies

``` composer install ```

#### Start PHP server

```php -S localhost:8000```

# OVERVIEW

## High-Level Overview

Within index, routes are added to an Api object either directly or through the use of a custom router. Our Api object simply allows one to addRoutes to it, holds all the routes, and contains the method to compare the current URI to the routes. If the URI matches a route, the corresponding route's function will be called. The functions within these routes can make use of a variety of middleware classes like dbconnect for interacting with our MysQL database or jwt for authentication.

Our Current Routes Include:

### /auth


## Files

### index.php

This is our main file that all URLS beginning with /api are directed to. It contains our API object where we hold and add all of our Routes. We add routes by either one of two ways: adding it directly to our api defining the method, url, and function (ex. GET, /merch, function () { echo $merch }) or adding a Router which has its own array of predefined routes. The latter allows us to seperate different routes like /merch from /auth into seperate files to better organize our code (see router.php for more details).

### router.php