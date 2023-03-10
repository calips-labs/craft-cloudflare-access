<?php

namespace calips\cfaccess\verification;

class VerificationResult
{
    const FAILURE_WRONG_AUD = 'wrong_aud';
    const FAILURE_WRONG_ISSUER = 'wrong_issuer';
    const FAILURE_NO_EMAIL = 'no_email';
    const FAILURE_INVALID_KEY = 'invalid_key';
    const FAILURE_INVALID_JWT = 'invalid_jwt';
    const FAILURE_EXPIRED = 'expired';
    const FAILURE_NO_KEYS = 'no_keys';
    const FAILURE_NOT_CONFIGURED = 'not_configured';

    public bool $valid = false;
    public ?string $username = null;
    public ?string $failureReason = null;
}
