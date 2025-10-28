<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
        <link rel="stylesheet" href="{{ asset("css/sanitize.css") }}" />
        <link rel="stylesheet" href="{{ asset("css/common.css") }}" />
        @stack("styles")
    </head>
    <body>
        @include("layouts.header")
        <main>
            @if (session("message"))
                <div class="session-message session-message--success" role="status" aria-live="polite">
                    {{ session("message") }}
                </div>
            @endif

            @if (session("error"))
                <div class="session-message session-message--error" role="alert">
                    {{ session("error") }}
                </div>
            @endif

            @yield("content")
        </main>
        @stack("scripts")
    </body>
</html>
