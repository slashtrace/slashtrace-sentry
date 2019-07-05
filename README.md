# SlashTrace - Awesome error handler - Sentry handler

This is the [Sentry](https://sentry.io/) handler for [SlashTrace](https://github.com/slashtrace/slashtrace). 
Use it to send your errors and exceptions to your Sentry account.

## Usage

1. Install using Composer:

   ```
   composer require slashtrace/slashtrace-sentry
   ```
   
2. Hook it into SlashTrace:

   ```PHP
   use SlashTrace\SlashTrace;
   use SlashTrace\Sentry\SentryHandler;

   $handler = new SentryHandler("https://abcdefghijklmnopqrstuvwxyz123456@sentry.io/123456"); // <- Your Sentry DSN. Get it from your projects settings on sentry.io
    
   $slashtrace = new SlashTrace();
   $slashtrace->addHandler($handler);
   ```
   
   Alternatively, you can pass in a pre-configured Sentry client when you instantiate the handler:
   
   ```
   $client = Sentry\ClientBuilder::create(["dsn" => "..."]);
   $handler = new SentryHandler($client);
   
   $slashtrace->addHandler($handler);
   ```
   
Read the [SlashTrace](https://github.com/slashtrace/slashtrace) docs to see how to capture errors and exceptions, and how to attach additional data to your events.
