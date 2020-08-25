<?php

namespace App\Sources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ADP
{
    public function __construct() {
	$this->config=Config::get('sources.adp');
    }
}
