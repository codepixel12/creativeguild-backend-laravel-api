<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>    
</head>

<body style="background:rgb(254, 199, 135); background: linear-gradient(63deg, rgba(254, 199, 135, 1) 0%, rgba(255, 143, 135, 1) 100%); height:100vh; margin:0px; padding:45px 25px; box-sizing:border-box; font-family: 'Poppins', sans-serif; font-weight:400; font-size:16px;">

<div style="background:#fff; max-width:600px; padding:35px 15px; border-radius:5px; margin:0px auto; text-align:center;">
	<img src="https://creativeguild-user-profile.s3.ca-central-1.amazonaws.com/creativeguild-logo.png" alt="Logo" width="115px" />
	<h1 style="margin:0px; padding:0px;">Forgot Your Password</h1>
	<p style="margin:0px; padding:5px 0px 0px; font-size:14px;">If you requested to reset your password.<br>please continue with the code below.</p>
	
	<p style="margin:15px 0px; padding:0px;"><strong style="font-size:25px;">{{$data['verificationCode']}}</strong></p>
	
	<a href="{{$data['url']}}" style="display:inline-block; background:rgb(255 144 135); padding:10px 25px; border-radius:4px; text-decoration:none; color:#fff;">Reset Passowrd</a>
	
	
	<p style="margin:20px 0px 0px; padding:0px; font-size:12px;">This link will expire after 24h.</p>
</div>
</body>
</html>