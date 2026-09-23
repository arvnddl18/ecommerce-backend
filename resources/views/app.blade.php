<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Tags -->
    <title>FOLD. — Shop clothing</title>
    <meta name="description" content="A quietly confident collection of everyday apparel made to move with you. Curated independent labels from around the world.">
    <meta name="keywords" content="clothing, shop clothing, fashion archive, independent labels, curated apparel">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="FOLD. — Shop clothing">
    <meta property="og:description" content="Fresh pieces, ready to wear. Updated weekly with curated independent labels.">
    <meta property="og:image" content="https://images.unsplash.com/photo-1544957992-20514f595d6f?w=1200&q=80">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="MAISON COLLECTIVE — Multi-Vendor Apparel Archive">
    <meta name="twitter:description" content="Curated apparel and footwear from independent verified ateliers with atomic Stripe Connect checkout.">
    <meta name="twitter:image" content="https://images.unsplash.com/photo-1544957992-20514f595d6f?w=1200&q=80">

    <!-- Google Fonts: Space Grotesk & DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">

    <!-- Schema.org JSON-LD Structured Data for SEO -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "OnlineStore",
      "name": "MAISON COLLECTIVE",
      "url": "{{ url('/') }}",
      "description": "Curated multi-vendor apparel boutique powered by Laravel 11 API, PostgreSQL, Redis, and Stripe Connect.",
      "potentialAction": {
        "@@type": "SearchAction",
        "target": "{{ url('/?search={search_term_string}') }}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <!-- Vite Scripts & Assets -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>
<body class="bg-[#FAFAF8] text-[#1A1A1A] antialiased min-h-screen">
    <div id="app"></div>
</body>
</html>
