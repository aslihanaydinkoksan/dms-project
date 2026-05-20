<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Sunucu Hatası | Köksan DMS</title>
    <script src="https://unpkg.com/lucide@latest"></script>
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
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(225, 29, 72, 0.2);
            border-radius: 1.5rem;
            padding: 3rem 2rem;
            text-align: center;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(225, 29, 72, 0.15);
        }
        .icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(225, 29, 72, 0.1);
            margin-bottom: 1.5rem;
            color: #f43f5e;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
        }
        p {
            color: #94a3b8;
            font-size: 1rem;
            line-height: 1.5;
            margin: 0 0 2rem 0;
        }
        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            color: #f8fafc;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body>
    <div class="glass-card">
        <div class="icon-container">
            <i data-lucide="alert-triangle" width="40" height="40"></i>
        </div>
        <h1>500 - Sunucu Hatası</h1>
        <p>Sistemde geçici bir arıza meydana geldi. Teknik ekibimiz bilgilendirildi ve sorunu çözmek için çalışıyor.</p>
        <a href="/" class="btn-outline">
            <i data-lucide="refresh-cw" width="18" height="18"></i>
            Sayfayı Yenile
        </a>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>