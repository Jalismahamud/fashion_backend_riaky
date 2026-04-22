@extends('emails.masterEmailLayout')

@section('content')
  <h1>Verify your Forgot OTP</h1>
  <div class="verification-box">
    <div class="code-label">Your Forgot otp code</div>
    <div class="otp">{{ $otp }}</div>
    <div class="note">This code will expire in 5 minutes.</div>
  </div>
@endsection
