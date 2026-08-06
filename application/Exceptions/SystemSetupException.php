<?php

namespace Application\Exceptions;

/** Internal configuration/schema problem. Never expose its technical message. */
class SystemSetupException extends \RuntimeException {}
