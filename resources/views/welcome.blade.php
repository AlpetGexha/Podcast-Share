<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Let's Listen Together</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=figtree:400,600|aleo:300,500,700|annie-use-your-telescope:400&display=swap"
        rel="stylesheet"/>

    <!-- Styles -->
    <wireui:scripts/>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-emerald-50">
<h1 class="mt-8 text-3xl text-center font-cursive text-slate-800">🫶 </h1>
<livewire:dashboard/>
@livewireScripts

</body>
