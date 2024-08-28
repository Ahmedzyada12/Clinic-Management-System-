<!DOCTYPE html>
<html>
<head>
    <title>Stolenmob.com</title>
</head>
<body>
    <h3>New Message From: {{ $details['name'] }}</h3>
    <p>You have a new message from: {{ $details['name'] }}, Email-Address: {{ $details['email'] }}</p>
    <h4>Message: </h4>
    <p>{{ $details['body'] }}</p>
   
    <p>Thank you</p>
</body>
</html>