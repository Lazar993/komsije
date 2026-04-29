<meta name="application-name" content="Komšije">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $firebaseWeb = config('services.fcm.web');
    $firebasePublicConfig = $firebaseWeb && ! empty($firebaseWeb['api_key']) ? [
        'apiKey' => $firebaseWeb['api_key'],
        'authDomain' => $firebaseWeb['auth_domain'],
        'projectId' => $firebaseWeb['project_id'],
        'messagingSenderId' => $firebaseWeb['messaging_sender_id'],
        'appId' => $firebaseWeb['app_id'],
        'vapidKey' => $firebaseWeb['vapid_key'],
    ] : null;
@endphp
@auth
    @if ($firebasePublicConfig)
        <meta name="firebase-config" content='@json($firebasePublicConfig)'>
    @endif
@endauth
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Komšije">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#2563EB">
<meta name="description" content="{{ __('Sve u vezi zgrade, na jednom mestu.') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}?v=4">
<link rel="icon" type="image/svg+xml" sizes="any" href="{{ asset('icons/logo-icon-v2.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32-v4.png') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon-v4.png') }}">