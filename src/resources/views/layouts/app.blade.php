<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="stylesheet" href="{{ asset("css/sanitize.css") }}" />
        <link rel="stylesheet" href="{{ asset("css/common.css") }}" />
        @stack("styles")
    </head>
    <body>
        @include("layouts.header")
        <main>
            @if (session("message"))
                <div class="session-message" role="status" aria-live="polite">
                    {{ session("message") }}
                </div>
            @endif
            @yield("content")
        </main>
    </body>
</html>