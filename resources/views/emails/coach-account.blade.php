<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #212529; line-height: 1.5;">
    <p>Hi {{ $name }},</p>

    <p>A coach account has been created for you on the FBSS Portal.</p>

    <p>
        <strong>Username:</strong> {{ $username }}<br>
        <strong>Password:</strong> {{ $password }}
    </p>

    <p>You can log in at <a href="{{ route('login') }}">{{ route('login') }}</a>. Please change your password after your first login.</p>

    <p>Froy Bartolome Swimming School</p>
</body>
</html>