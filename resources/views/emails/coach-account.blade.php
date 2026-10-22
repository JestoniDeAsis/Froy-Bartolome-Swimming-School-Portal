<!DOCTYPE html>
<html lang="en">
<body style="margin: 0; padding: 20px; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #212529; line-height: 1.5;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h2 style="color: #0093E5; margin-top: 0;">FBSS Portal</h2>

        <p>Hi {{ $name }},</p>

        <p>
            A coach account has been created for you on the Froy Bartolome Swimming School (FBSS) Portal.
            You can use the login details below to access your coach dashboard.
        </p>

        <div style="background: #f4f8fc; border-left: 4px solid #0093E5; border-radius: 4px; padding: 16px; margin: 20px 0;">
            <strong>Username:</strong> {{ $username }}<br>
            <strong>Temporary password:</strong> {{ $password }}
        </div>

        <p style="text-align: center; margin: 24px 0;">
            <a href="{{ route('login') }}"
               style="background: #0093E5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; display: inline-block;">
                Log in to FBSS Portal
            </a>
        </p>

        <p style="font-size: 14px;">
            For your security, please change your password after your first login and do not share it with anyone.
        </p>

        <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 24px 0;">

        <p style="color: #777; font-size: 13px; margin: 0;">
            If the button doesn't work, copy this link into your browser:<br>
            <a href="{{ route('login') }}" style="color: #045199;">{{ route('login') }}</a><br><br>
            Froy Bartolome Swimming School
        </p>
    </div>
</body>
</html>