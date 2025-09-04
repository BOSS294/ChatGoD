<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <script type="module">
    import ChatWidget from '/activator.js';
    ChatWidget.init({ templateUrl: '/main.php', processorUrl: '/processor.php' });
    </script>

</body>
</html>