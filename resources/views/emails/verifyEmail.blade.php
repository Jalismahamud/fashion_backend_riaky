@extends('emails.masterEmailLayout')

@section('content')
  <h1>Verify Your OTP</h1>
  <p>Thanks for signing up! Please enter the code below in the app to verify your email address.</p>

  <div class="verification-box">
      <div class="code-label">Your OTP Code</div>
      <div class="otp">{{ $otp }}</div>
      <div class="note">This code will expire in 5 minutes.</div>
  </div>
@endsection
