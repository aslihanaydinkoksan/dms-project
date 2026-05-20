<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Bakımda | Köksan DMS</title>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* Glassmorphism Base */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 3rem 2rem;
            text-align: center;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(56, 189, 248, 0.1);
            margin-bottom: 1.5rem;
            color: #38bdf8;
        }

        .spin {
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            letter-spacing: -0.025em;
        }

        p {
            color: #94a3b8;
            font-size: 1rem;
            line-height: 1.5;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="glass-card">
        <div class="icon-container">
            <i data-lucide="settings" class="spin" width="40" height="40"></i>
        </div>
        <h1>Kısa Bir Bakım Molası ☕</h1>
        <p>Size daha iyi hizmet verebilmek için sistemimizi güncelliyoruz. Lütfen kısa bir süre sonra tekrar deneyin.
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
