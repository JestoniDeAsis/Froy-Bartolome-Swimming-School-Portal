<!DOCTYPE html>
<html lang="en">
<body style="margin: 0; padding: 20px; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #212529; line-height: 1.5;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h2 style="color: #0093E5; margin-top: 0;">FBSS Portal</h2>

        <p style="font-size: 18px; font-weight: bold; margin-bottom: 8px;">Verify your email address</p>

        <p>
            Thank you for registering with the Froy Bartolome Swimming School (FBSS) Portal.
            Enter the code below on the registration page to confirm that this email address
            belongs to you and to finish creating your account.
        </p>

        <p style="margin-bottom: 4px; color: #777; font-size: 14px;">Your verification code</p>
        <p style="font-size: 32px; font-weight: bold; letter-spacing: 4px; color: #045199; margin-top: 0;">
            {{ $code }}
        </p>

        <p style="font-size: 14px;">
            This code expires in 10 minutes. For your security, do not share it with anyone.
            FBSS staff will never ask you for it.
        </p>

        <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 24px 0;">

        <p style="color: #777; font-size: 13px; margin: 0;">
            Didn't try to register? You can safely ignore this email. No account will be created
            without this code.
        </p>
    </div>
</body>
</html>