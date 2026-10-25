<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>New device login</title></head>
<body style="margin:0;padding:24px;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#212529;">
    <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
        <h2 style="margin:0 0 12px;font-size:20px;">New device login</h2>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">
            Someone entered your username and password from a browser we don't recognize.
            If this was you, enter this code on the login page:
        </p>

        <p style="margin:0 0 16px;font-size:32px;font-weight:700;letter-spacing:8px;text-align:center;color:#045199;">
            {{ $code }}
        </p>

        <p style="margin:0 0 16px;font-size:13px;color:#495057;">
            The code expires in {{ $minutes }} minutes.
        </p>

        <p style="margin:0 0 4px;font-size:12px;color:#667085;">Browser: {{ $browser }}</p>
        <p style="margin:0 0 16px;font-size:12px;color:#667085;">IP address: {{ $ipAddress }}</p>

        <p style="margin:0;font-size:13px;line-height:1.5;color:#b42318;">
            If this wasn't you, don't share this code. Your password may be known to someone else,
            so please change it and contact the administrator.
        </p>
    </div>
</body>
</html>