<?php

namespace App\Exceptions;

/**
 * DGCP refused us rather than failed: a 403 (from 2026-11-13, a Cloudflare
 * challenge) or a 429 that persisted. More requests only prolong it.
 */
class DgcpBlockedException extends DgcpApiException {}
