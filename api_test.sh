#!/bin/zsh

# ==============================================================================
# Mini Wallet API Test Script
#
# This script automates testing of the backend API endpoints.
# It simulates a user logging in, checking their balance, and making transfers.
#
# Usage:
# 1. Make sure your Laravel server is running (`php artisan serve`).
# 2. Make the script executable: `chmod +x docs/api_test.sh`
# 3. Run the script from the project root: `./docs/api_test.sh`
#
# All output (verbose curl logs, headers, and JSON responses) will be saved to
# `docs/output.txt`, overwriting the file on each execution.
# ==============================================================================

# Get the directory where the script is located to save output.txt there.
SCRIPT_DIR=$(dirname "$0")
OUTPUT_FILE="$SCRIPT_DIR/api-output.txt"

# Redirect all stdout and stderr to the output file.
exec &> "$OUTPUT_FILE"

echo "===== Mini Wallet API Test Script ====="
echo "Start Time: $(date)"
echo "======================================="
echo

# --- Configuration ---
BASE_URL="http://127.0.0.1:8000"
SENDER_EMAIL="user_a@email.com"
SENDER_PASSWORD="password"
RECEIVER_EMAIL="user_b@email.com"
COOKIE_JAR="$SCRIPT_DIR/cookies.txt"

# --- Cleanup from previous runs ---
rm -f "$COOKIE_JAR"

# --- Step 1: Get CSRF Cookie ---
echo "\n----- [Step 1] Getting CSRF Cookie -----"
curl -v -c "$COOKIE_JAR" "$BASE_URL/sanctum/csrf-cookie"
echo "\n----- [Step 1] Complete -----"

# --- Step 2: Authenticate (Log in) ---
# Extract the XSRF-TOKEN from the cookie jar to use in the login header.
# We need to URL-decode the token value that curl saves.
TOKEN_RAW=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | cut -f7)
XSRF_TOKEN=$(echo -n "$TOKEN_RAW" | perl -MURI::Escape -ne 'print uri_unescape($_)')

echo "\n\n----- [Step 2] Authenticating as Sender ($SENDER_EMAIL) -----"
curl -v -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H "X-XSRF-TOKEN: $XSRF_TOKEN" \
-H "Referer: $BASE_URL" \
-X POST "$BASE_URL/api/login" \
-d '{
  "email": "'"$SENDER_EMAIL"'",
  "password": "'"$SENDER_PASSWORD"'"
}' # We remove "| jq ." because a successful login now returns 204 No Content
echo "\n----- [Step 2] Complete -----"

# Check if login was successful by looking for the session cookie
if ! grep -q "wallet-app-session" "$COOKIE_JAR"; then
    echo "\n\nCRITICAL: Login failed. Session cookie not found. Aborting."
    exit 1
fi

# Re-extract the XSRF-TOKEN in case it was rotated during login, for subsequent requests.
# We need to URL-decode the token value that curl saves.
TOKEN_RAW=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | cut -f7)
XSRF_TOKEN=$(echo -n "$TOKEN_RAW" | perl -MURI::Escape -ne 'print uri_unescape($_)')

# --- Step 3: Fetch Transactions and Balance ---
echo "\n\n----- [Step 3] Fetching Transactions & Balance -----"
curl -v -b "$COOKIE_JAR" \
-H "Accept: application/json" \
-H "X-XSRF-TOKEN: $XSRF_TOKEN" \
-H "Referer: $BASE_URL" \
-X GET "$BASE_URL/api/transactions" | jq .
echo "\n----- [Step 3] Complete -----"

# --- Step 4: Execute Transfers ---
echo "\n\n----- [Step 4.1] Testing: Successful Transfer -----"
curl -v -b "$COOKIE_JAR" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H "X-XSRF-TOKEN: $XSRF_TOKEN" \
-H "Referer: $BASE_URL" \
-X POST "$BASE_URL/api/transactions" \
-d '{
  "receiver_email": "'"$RECEIVER_EMAIL"'",
  "amount": "100"
}' | jq .

echo "\n\n----- [Step 4.2] Testing: Insufficient Funds -----"
curl -v -b "$COOKIE_JAR" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H "X-XSRF-TOKEN: $XSRF_TOKEN" \
-H "Referer: $BASE_URL" \
-X POST "$BASE_URL/api/transactions" \
-d '{
  "receiver_email": "'"$RECEIVER_EMAIL"'",
  "amount": "9999999"
}' | jq .

echo "\n\n----- [Step 4.3] Testing: Invalid Recipient -----"
curl -v -b "$COOKIE_JAR" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H "X-XSRF-TOKEN: $XSRF_TOKEN" \
-H "Referer: $BASE_URL" \
-X POST "$BASE_URL/api/transactions" \
-d '{
  "receiver_email": "no-one@nowhere.com",
  "amount": "50"
}' | jq .
echo "\n----- [Step 4] Complete -----"

# --- Step 5: Cleanup ---
echo "\n\n----- [Step 5] Cleaning up session files -----"
rm -f "$COOKIE_JAR"
echo "Removed $COOKIE_JAR"
echo "\n----- [Step 5] Complete -----"

echo "\n======================================="
echo "Script Finished: $(date)"
echo "Output saved to $OUTPUT_FILE"
echo "======================================="