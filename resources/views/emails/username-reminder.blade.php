<!DOCTYPE html>
<html lang="en">
<body style="margin: 0; padding: 20px; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #212529; line-height: 1.5;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h2 style="color: #0093E5; margin-top: 0;">FBSS Portal</h2>

        <p style="font-size: 18px; font-weight: bold; margin-bottom: 8px;">Your username reminder</p>

        <p>Hi {{ $name }},</p>

        <p>
            We received a request to send the username linked to this email address on the
            Froy Bartolome Swimming School (FBSS) Portal. Here it is:
        </p>

        <div style="background: #f4f8fc; border-left: 4px solid #0093E5; border-radius: 4px; padding: 16px; margin: 20px 0;">
            <span style="color: #777; font-size: 14px;">Your username</span><br>
            <span style="font-size: 22px; font-weight: bold; color: #045199;">{{ $username }}</span>
        </div>

        <p style="text-align: center; margin: 24px 0;">
            <a href="{{ route('login') }}"
               style="background: #0093E5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; display: inline-block;">
                Log in to FBSS Portal
            </a>
        </p>

        <p style="font-size: 14px;">
            Forgot your password too? You can
            <a href="{{ url('forgot-password') }}" style="color: #045199;">reset it here</a>.
            For your security, we never send passwords by email.
        </p>

        <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 24px 0;">

        <p style="color: #777; font-size: 13px; margin: 0;">
            Didn't request this? You can safely ignore this email. No changes were made to your account.
        </p>
    </div>
</body>
</html>