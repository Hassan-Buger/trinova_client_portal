<?php

// Public diagnostics previously exposed server paths, database identifiers and
// raw connection errors. Diagnostics now live only in protected server logs.
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not Found';
