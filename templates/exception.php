<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
        <title>Server error</title>
        <link href="https://fonts.googleapis.com/css?family=Titillium+Web" rel="stylesheet">
        <style>
            body { font-family: 'Titillium Web', sans-serif; }
            small { color: #555; }
            div.container { margin: 0 auto; }
            ul { margin: 0; padding: 0; list-style: none; }
            li { margin: 1em 0; padding: 0.5em; background-color: #f0f8ff; border: 1px solid #80c3ff; }
            p { margin: 0 0 0.5em 0; }
            pre { max-height: 200px; margin: 0.5em 0; white-space: pre-wrap; overflow-y: auto; color: #555; }
            @media print { div.container { margin: 0 1em; padding-bottom: 0; } }
            @media screen and (max-width: 960px) { div.container { width: 100%; } }
            @media screen and (min-width: 960px) and (max-width: 1224px) { div.container { width: 80%; } }
            @media screen and (min-width: 1224px) { div.container { width: 60%; } }
        </style>
    </head>
    <body>
        <?php
            $stack = function ($e) {
                $stack = [];
                while ($e) { $stack[] = $e; $e = $e->getPrevious() ?? false; }
                return array_reverse($stack);
            }
        ?>
        <div class="container">
            <?php if (isset($e)): ?>
            <h1>Uncaught exception</h1>
            <ul>
                <?php foreach ($stack($e) as $exception): ?>
                <li>
                    <p>
                        <strong><?= get_class($exception) ?></strong>:
                        <?= $exception->getMessage() ?>
                        in
                        <?= $exception->getFile() ?>:<?= $exception->getLine() ?>
                    </p>
                    <pre><?= $exception->getTraceAsString() ?></pre>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <?php if (isset($exs)): ?>
            <?php if (is_array($exs)): ?>
            <?php for ($i = 0; count($exs) > $i; $i++): ?>
            <h2>Thrown from exception handler #<?= $i + 1 ?></h2>
            <ul>
                <?php foreach ($stack($exs[$i]) as $exception): ?>
                <li>
                    <p>
                        <strong><?= get_class($exception) ?></strong>:
                        <?= $exception->getMessage() ?>
                        in
                        <?= $exception->getFile() ?>:<?= $exception->getLine() ?>
                    </p>
                    <pre><?= $exception->getTraceAsString() ?></pre>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endfor; ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </body>
</html>
