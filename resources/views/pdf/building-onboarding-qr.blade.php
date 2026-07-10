<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Komšije QR onboarding</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 0; color: #0f172a; }
        .page { padding: 48px 52px; }
        .logo { text-align: center; font-size: 28px; font-weight: 700; letter-spacing: 0.4px; margin-bottom: 10px; }
        .building { text-align: center; font-size: 20px; margin-bottom: 14px; }
        .title { text-align: center; font-size: 34px; font-weight: 700; margin: 10px 0 8px; }
        .subtitle { text-align: center; font-size: 17px; color: #334155; margin-bottom: 22px; }
        .qr-wrap { text-align: center; margin: 18px 0 22px; }
        .qr-wrap img { width: 340px; height: 340px; }
        .url { text-align: center; font-size: 12px; color: #475569; margin-bottom: 18px; }
        .steps { text-align: center; font-size: 15px; color: #0f172a; line-height: 1.6; max-width: 520px; margin: 0 auto 24px; }
        .benefits { margin: 0 auto; max-width: 530px; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px 20px; background: #f8fafc; }
        .benefit { font-size: 14px; margin: 8px 0; color: #0f172a; }
        .footer { margin-top: 38px; text-align: center; color: #475569; font-size: 12px; }
    </style>
</head>
<body>
<div class="page">
    <div class="logo">Komšije</div>
    <div class="building">{{ $building->name }}</div>

    <div class="title">Pridružite se vašoj zgradi</div>
    <div class="subtitle">Skenirajte QR kod kamerom telefona.</div>

    <div class="qr-wrap">
        <img src="{{ $qrDataUri }}" alt="QR kod">
    </div>

    <div class="steps">
        Popunite kratku prijavu.<br>
        Nakon odobrenja upravnika dobićete e-mail za pristup aplikaciji.
    </div>

    <div class="benefits">
        <div class="benefit">✓ traje manje od jednog minuta</div>
        <div class="benefit">✓ potpuno besplatno za stanare</div>
        <div class="benefit">✓ radi na Android i iPhone uređajima</div>
    </div>

    <div class="url">{{ $joinUrl }}</div>

    <div class="footer">
        Powered by Komšije<br>
        T&amp;B Solutions
    </div>
</div>
</body>
</html>
