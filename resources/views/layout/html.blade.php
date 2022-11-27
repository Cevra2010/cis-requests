<!DOCTYPE html>
<html lang="en" class="@stack("html_class") h-screen">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield("title") | CisRequests</title>


        <!-- Fonts -->
        <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
            @stack("pure_css")
        </style>
        @livewireStyles()

    @vite(['resources/js/app.js'])
</head>
<body class="@stack("body_class") h-full">

    @yield("body")
    @livewireScripts()
</body>
</html>
