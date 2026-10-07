<?php
declare(strict_types=1);

/*
 * LensCraft configuration.
 * IMPORTANT: Move secrets to environment variables on production.
 */
const APP_NAME = 'LensCraft';
const APP_URL = 'http://localhost/photographsite'; // Change to your public HTTPS domain
const CURRENCY = 'LKR';

const PAYHERE_MODE = 'sandbox'; // sandbox | live
const PAYHERE_MERCHANT_ID = '1238478';
const PAYHERE_MERCHANT_SECRET = 'MTAzNzQyMzk3NzI0NTA0MzM0ODMxNzk5MTg0Nzg5MjcwODY2NDczNw==';

const DB_HOST = 'localhost';
const DB_NAME = 'lenscraft_db';
const DB_USER = 'root';
const DB_PASS = '';

date_default_timezone_set('Asia/Colombo');
