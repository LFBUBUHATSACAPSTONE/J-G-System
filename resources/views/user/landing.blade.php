<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<style>
  .glass-header {
    background-color: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    /* Safari support */
  }
</style>

<body class="overflow-hidden">
  @include('includes.navigation');
</body>

</html>