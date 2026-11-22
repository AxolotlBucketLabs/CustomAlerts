<?php

declare(strict_types=1);

namespace AxolotlBucketLabs\CustomAlerts\catalog;

enum TranslationSide : string{
	case Server = "server";
	case Client = "client";
}
