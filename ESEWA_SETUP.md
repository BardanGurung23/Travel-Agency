# eSewa payment setup

## Sandbox

Start the application:

```sh
./start.sh
```

Open `http://127.0.0.1:8080`, sign in, open an unpaid booking, and select
**Pay with eSewa**.

Sandbox credentials:

- eSewa ID: `9711111111`, `9711111112`, or `9711111113`
- Password: `Nepal@123`
- MPIN (mobile application only): `1122`
- Verification token: `123456`

The application signs every request, verifies the signed callback, checks the
eSewa transaction-status API, and reconciles delayed UAT settlement before
marking a booking paid.

## Production

Set `ESEWA_PRODUCT_CODE`, `ESEWA_SECRET_KEY`, `ESEWA_PAYMENT_URL`,
`ESEWA_STATUS_URL`, and the public HTTPS `APP_URL` using credentials and URLs
provided by eSewa.
